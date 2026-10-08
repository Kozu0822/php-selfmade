<?php

namespace App\Http\Controllers;

use App\Exceptions\ReservationConflictException;
use App\Models\Device;
use App\Models\Part;
use App\Models\Reservation;
use App\Models\Symptom;
use App\Models\TimeSlot;
use App\Services\SymptomAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function create()
    {
        $devices = Device::orderBy('id')->get();

        return view('reservations.device', compact('devices'));
    }

    public function storeDevice(Request $request)
    {
        $data = $request->validate([
            'device_id' => ['required', 'exists:devices,id'],
        ], [
            'device_id.required' => '機種を選択してください。',
        ]);

        session(['reservation.device_id' => $data['device_id']]);

        return redirect()->route('reservations.symptom');
    }

    public function symptom()
    {
        $device = $this->selectedDevice();
        if (!$device) {
            return redirect()->route('reservations.create');
        }

        $symptoms = $device->symptoms()->orderBy('symptoms.id')->get();
        $outOfStockSymptomIds = [];

        foreach ($symptoms as $symptom) {
            if (!$this->hasEnoughParts($device, $symptom)) {
                $outOfStockSymptomIds[] = $symptom->id;
            }
        }

        return view('reservations.symptom', compact('device', 'symptoms', 'outOfStockSymptomIds'));
    }

    public function storeSymptom(Request $request)
    {
        $device = $this->selectedDevice();
        if (!$device) {
            return redirect()->route('reservations.create');
        }

        $data = $request->validate([
            'symptom_id' => ['required', 'exists:symptoms,id'],
        ], [
            'symptom_id.required' => '症状を選択してください。',
        ]);

        if (!$device->symptoms()->where('symptoms.id', $data['symptom_id'])->exists()) {
            return redirect()
                ->route('reservations.symptom')
                ->withErrors(['symptom_id' => 'この機種では選択できない症状です。']);
        }

        $symptom = Symptom::find($data['symptom_id']);
        if (!$this->hasEnoughParts($device, $symptom)) {
            return redirect()
                ->route('reservations.symptom')
                ->withErrors(['symptom_id' => '必要な部品の在庫が不足しています。別の症状を選択してください。']);
        }

        session(['reservation.symptom_id' => $data['symptom_id']]);
        session()->forget('reservation.symptom_text');

        return redirect()->route('reservations.time');
    }

    public function suggestSymptom(Request $request, SymptomAiService $aiService)
    {
        $device = $this->selectedDevice();
        if (!$device) {
            return redirect()->route('reservations.create');
        }

        $data = $request->validate([
            'symptom_text' => [
                'required', 'string', 'max:500',
                'regex:/(?:\p{Han}|\p{Hiragana}|\p{Katakana}|[A-Za-z]){2,}/u',
            ],
        ], [
            'symptom_text.required' => '症状の詳細を入力してください。',
            'symptom_text.max' => '症状の詳細は500文字以内で入力してください。',
            'symptom_text.regex' => '症状の内容を文章で入力してください。',
        ]);

        $apiKey = config('services.gemini.api_key');
        if (!$apiKey) {
            return redirect()
                ->route('reservations.symptom')
                ->withInput()
                ->withErrors(['symptom_text' => 'AI診断は現在利用できません。症状一覧から選択してください。']);
        }

        $symptoms = $device->symptoms()->orderBy('symptoms.id')->get();
        $availableSymptoms = $symptoms->filter(fn ($symptom) => $this->hasEnoughParts($device, $symptom));

        if ($availableSymptoms->isEmpty()) {
            return redirect()
                ->route('reservations.symptom')
                ->withInput()
                ->withErrors(['symptom_text' => '現在選択できる症状がありません。']);
        }

        $aiResult = $aiService->suggest($device, $availableSymptoms, $data['symptom_text']);
        $symptomId = $aiResult['symptom_id'];
        $advice = $aiResult['advice'];
        $symptom = $symptomId ? Symptom::find($symptomId) : null;

        if (
            !$symptom ||
            !$device->symptoms()->where('symptoms.id', $symptom->id)->exists() ||
            !$this->hasEnoughParts($device, $symptom)
        ) {
            return redirect()
                ->route('reservations.symptom')
                ->withInput()
                ->withErrors(['symptom_text' => 'AIで候補を特定できませんでした。症状一覧から選択してください。']);
        }

        if ($advice) {
            return redirect()
                ->route('reservations.symptom')
                ->withInput([
                    'symptom_id' => $symptom->id,
                    'symptom_text' => $data['symptom_text'],
                ])
                ->with('ai_advice', $advice)
                ->with('ai_recommendation', '必要であれば「'.$symptom->name.'」として予約できます。');
        }

        session([
            'reservation.symptom_id' => $symptom->id,
            'reservation.symptom_text' => $data['symptom_text'],
        ]);

        return redirect()
            ->route('reservations.time')
            ->with('message', 'AI診断で「'.$symptom->name.'」を選択しました。');
    }

    public function time()
    {
        $this->closeExpiredTimeSlots();

        $device = $this->selectedDevice();
        $symptom = $this->selectedSymptom();

        if (!$device || !$symptom) {
            return redirect()->route('reservations.create');
        }

        $timeSlots = TimeSlot::where('is_open', true)
            ->where('is_reserved', false)
            ->where('slot_at', '>', now())
            ->orderBy('slot_at')
            ->get();

        return view('reservations.time', compact('device', 'symptom', 'timeSlots'));
    }

    public function storeTime(Request $request)
    {
        $this->closeExpiredTimeSlots();

        $data = $request->validate([
            'time_slot_id' => ['required', 'exists:time_slots,id'],
        ], [
            'time_slot_id.required' => '予約時間を選択してください。',
        ]);

        $timeSlot = TimeSlot::find($data['time_slot_id']);
        if (!$this->isAvailableTimeSlot($timeSlot)) {
            return redirect()
                ->route('reservations.time')
                ->withErrors(['time_slot_id' => '選択した予約時間は利用できません。']);
        }

        session([
            'reservation.time_slot_id' => $timeSlot->id,
            'reservation.time_slot_version' => $timeSlot->version,
        ]);

        return redirect()->route('reservations.confirm');
    }

    public function confirm()
    {
        $device = $this->selectedDevice();
        $symptom = $this->selectedSymptom();
        $timeSlot = $this->selectedTimeSlot();
        $timeSlotVersion = $this->selectedTimeSlotVersion();

        if (!$device || !$symptom || !$timeSlot || $timeSlotVersion === null) {
            return redirect()->route('reservations.create');
        }

        $parts = $this->partsFor($device, $symptom);

        return view('reservations.confirm', compact('device', 'symptom', 'timeSlot', 'parts'));
    }

    public function store()
    {
        $this->closeExpiredTimeSlots();

        $device = $this->selectedDevice();
        $symptom = $this->selectedSymptom();
        $timeSlot = $this->selectedTimeSlot();
        $timeSlotVersion = $this->selectedTimeSlotVersion();

        if (!$device || !$symptom || !$timeSlot || $timeSlotVersion === null) {
            return redirect()->route('reservations.create');
        }

        $parts = $this->partsFor($device, $symptom);

        if ($parts->contains(fn ($part) => $part->stock < 1)) {
            return redirect()
                ->route('reservations.confirm')
                ->withErrors(['parts' => '必要な部品の在庫が不足しています。']);
        }

        if (!$this->isAvailableTimeSlot($timeSlot)) {
            return redirect()
                ->route('reservations.time')
                ->withErrors(['time_slot_id' => '選択した予約時間は利用できません。']);
        }

        try {
            DB::transaction(function () use ($device, $symptom, $timeSlot, $timeSlotVersion, $parts) {
                $reserved = TimeSlot::where('id', $timeSlot->id)
                    ->where('version', $timeSlotVersion)
                    ->where('is_open', true)
                    ->where('is_reserved', false)
                    ->where('slot_at', '>', now())
                    ->update([
                        'is_reserved' => true,
                        'version' => $timeSlotVersion + 1,
                    ]);

                if ($reserved === 0) {
                    throw new ReservationConflictException('time_slot');
                }

                $reservation = Reservation::create([
                    'user_id' => Auth::id(),
                    'device_id' => $device->id,
                    'symptom_id' => $symptom->id,
                    'time_slot_id' => $timeSlot->id,
                    'status' => 'pending',
                    'symptom_text' => session('reservation.symptom_text'),
                ]);

                foreach ($parts as $part) {
                    $decremented = Part::where('id', $part->id)
                        ->where('stock', '>=', 1)
                        ->decrement('stock');

                    if ($decremented === 0) {
                        throw new ReservationConflictException('stock');
                    }

                    $reservation->parts()->attach($part->id);
                }
            });
        } catch (ReservationConflictException $exception) {
            if ($exception->reason === 'time_slot') {
                return redirect()
                    ->route('reservations.time')
                    ->withErrors(['time_slot_id' => '選択した予約時間は他の予約で埋まったため利用できません。別の時間を選択してください。']);
            }

            return redirect()
                ->route('reservations.confirm')
                ->withErrors(['parts' => '必要な部品の在庫が不足しています。']);
        }

        session()->forget('reservation');

        return redirect()->route('reservations.complete');
    }

    public function complete()
    {
        return view('reservations.complete');
    }

    public function cancel(Reservation $reservation)
    {
        if ($reservation->user_id !== Auth::id() || !$reservation->canBeCancelledByUser()) {
            return redirect()->route('mypage.index');
        }

        DB::transaction(function () use ($reservation) {
            $reservation->load('parts', 'timeSlot');

            $cancelled = Reservation::where('id', $reservation->id)
                ->where('user_id', Auth::id())
                ->whereIn('status', ['pending', 'no_show'])
                ->update([
                    'status' => 'cancelled_by_user',
                    'cancelled_at' => now(),
                ]);

            if ($cancelled === 0) {
                return;
            }

            foreach ($reservation->parts as $part) {
                Part::where('id', $part->id)->increment('stock');
            }

            TimeSlot::where('id', $reservation->timeSlot->id)->update([
                'is_reserved' => false,
                'version' => DB::raw('version + 1'),
            ]);
        });

        return redirect()->route('mypage.index');
    }

    private function selectedDevice(): ?Device
    {
        return Device::find(session('reservation.device_id'));
    }

    private function selectedSymptom(): ?Symptom
    {
        return Symptom::find(session('reservation.symptom_id'));
    }

    private function selectedTimeSlot(): ?TimeSlot
    {
        return TimeSlot::find(session('reservation.time_slot_id'));
    }

    private function selectedTimeSlotVersion(): ?int
    {
        $version = session('reservation.time_slot_version');

        return $version === null ? null : (int) $version;
    }

    private function closeExpiredTimeSlots(): void
    {
        TimeSlot::where('slot_at', '<=', now())
            ->where('is_open', true)
            ->where('is_reserved', false)
            ->update([
                'is_open' => false,
                'version' => DB::raw('version + 1'),
            ]);
    }

    private function isAvailableTimeSlot(?TimeSlot $timeSlot): bool
    {
        return $timeSlot &&
            $timeSlot->is_open &&
            !$timeSlot->is_reserved &&
            $timeSlot->slot_at->isFuture();
    }

    private function partsFor(Device $device, Symptom $symptom)
    {
        return $symptom->parts()
            ->where(function ($query) use ($device) {
                $query->whereNull('device_id')
                    ->orWhere('device_id', $device->id);
            })
            ->get();
    }

    private function hasEnoughParts(Device $device, Symptom $symptom): bool
    {
        $parts = $this->partsFor($device, $symptom);

        if ($parts->isEmpty()) {
            return $symptom->name === '来店相談';
        }

        return !$parts->contains(fn ($part) => $part->stock < 1);
    }
}
