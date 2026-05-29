<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Part;
use App\Models\Reservation;
use App\Models\Symptom;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $this->closeExpiredTimeSlots();
        $this->markNoShowReservations();

        $tab = $request->query('tab', 'reservations');

        if (!in_array($tab, ['reservations', 'time_slots', 'devices', 'symptoms', 'parts'])) {
            $tab = 'reservations';
        }

        $reservations = Reservation::with(['user', 'device', 'symptom', 'timeSlot'])
            ->join('time_slots', 'reservations.time_slot_id', '=', 'time_slots.id')
            ->select('reservations.*')
            ->orderByRaw("
                CASE
                    WHEN reservations.status = 'pending' THEN 1
                    WHEN reservations.status = 'no_show' THEN 2
                    ELSE 3
                END
            ")
            ->orderBy('time_slots.slot_at')
            ->paginate(10, ['*'], 'reservations_page')
            ->appends(['tab' => 'reservations']);
        $timeSlots = TimeSlot::with(['reservations.user', 'reservations.device', 'reservations.symptom'])
            ->orderBy('slot_at')
            ->paginate(10, ['*'], 'time_slots_page')
            ->appends(['tab' => 'time_slots']);
        $parts = Part::withTrashed()
            ->with(['device', 'symptoms'])
            ->withCount([
                'reservations as active_reservations_count' => function ($query) {
                    $query->whereIn('status', ['pending', 'no_show']);
                },
            ])
            ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('id')
            ->paginate(10, ['*'], 'parts_page')
            ->appends(['tab' => 'parts']);
        $devices = Device::withTrashed()
            ->withCount([
                'reservations as active_reservations_count' => function ($query) {
                    $query->whereIn('status', ['pending', 'no_show']);
                },
            ])
            ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('id')
            ->paginate(10, ['*'], 'devices_page')
            ->appends(['tab' => 'devices']);
        $allDevices = Device::orderBy('id')->get();
        $allSymptoms = Symptom::withTrashed()
            ->where('name', '!=', '来店相談')
            ->orderBy('id')
            ->get();
        $symptoms = Symptom::withTrashed()
            ->with('devices')
            ->withCount([
                'reservations as active_reservations_count' => function ($query) {
                    $query->whereIn('status', ['pending', 'no_show']);
                },
            ])
            ->orderBy('id')
            ->paginate(10, ['*'], 'symptoms_page')
            ->appends(['tab' => 'symptoms']);
        $selectedReservation = null;
        $selectedDevice = null;
        $selectedSymptom = null;
        $selectedPart = null;

        if ($request->query('reservation_id')) {
            $selectedReservation = Reservation::with(['user', 'device', 'symptom', 'timeSlot'])
                ->find($request->query('reservation_id'));
        }

        if ($request->query('edit_device')) {
            $selectedDevice = Device::find($request->query('edit_device'));
        }

        if ($request->query('edit_symptom')) {
            $selectedSymptom = Symptom::with('devices')->find($request->query('edit_symptom'));
        }

        if ($request->query('edit_part')) {
            $selectedPart = Part::with(['device', 'symptoms'])->find($request->query('edit_part'));
        }

        return view('admin.dashboard', compact('tab', 'reservations', 'timeSlots', 'parts', 'devices', 'allDevices', 'allSymptoms', 'symptoms', 'selectedReservation', 'selectedDevice', 'selectedSymptom', 'selectedPart'));
    }

    public function cancel(Reservation $reservation)
    {
        if (!$reservation->canBeCancelledByAdmin()) {
            return redirect()->route('admin.dashboard', ['tab' => 'reservations']);
        }

        DB::transaction(function () use ($reservation) {
            $reservation->load('parts', 'timeSlot');

            foreach ($reservation->parts as $part) {
                $part->increment('stock');
            }

            $reservation->timeSlot->update(['is_reserved' => false]);
            $reservation->update([
                'status' => 'cancelled_by_admin',
                'cancelled_at' => now(),
            ]);
        });

        return redirect()->route('admin.dashboard', ['tab' => 'reservations']);
    }

    public function storeTimeSlot(Request $request)
    {
        $data = $request->validate([
            'slot_date' => ['required', 'date'],
            'slot_time' => ['required', 'date_format:H:i'],
        ], [
            'slot_date.required' => '日付を入力してください。',
            'slot_time.required' => '時間を入力してください。',
        ]);

        $slotAt = Carbon::parse($data['slot_date'].' '.$data['slot_time']);

        if ($slotAt->lessThanOrEqualTo(now())) {
            return redirect()
                ->route('admin.dashboard', ['tab' => 'time_slots', 'add_time_slot' => 1])
                ->withErrors(['slot_at' => '現在時刻より後の日時を入力してください。'])
                ->withInput();
        }

        $startTime = Carbon::parse($data['slot_date'].' 09:30');
        $endTime = Carbon::parse($data['slot_date'].' 19:00');

        if ($slotAt->lt($startTime) || $slotAt->gt($endTime)) {
            return redirect()
                ->route('admin.dashboard', ['tab' => 'time_slots', 'add_time_slot' => 1])
                ->withErrors(['slot_at' => '予約枠は09:30から19:00までの時間で入力してください。'])
                ->withInput();
        }

        if (TimeSlot::where('slot_at', $slotAt)->exists()) {
            return redirect()
                ->route('admin.dashboard', ['tab' => 'time_slots', 'add_time_slot' => 1])
                ->withErrors(['slot_at' => '同じ日時の予約枠は既に登録されています。'])
                ->withInput();
        }

        TimeSlot::create([
            'slot_at' => $slotAt,
            'is_open' => true,
            'is_reserved' => false,
        ]);

        return redirect()->route('admin.dashboard', ['tab' => 'time_slots']);
    }

    public function toggleTimeSlot(TimeSlot $timeSlot)
    {
        if (!$timeSlot->is_reserved) {
            $timeSlot->update(['is_open' => !$timeSlot->is_open]);
        }

        return redirect()->route('admin.dashboard', ['tab' => 'time_slots']);
    }

    public function destroyTimeSlot(TimeSlot $timeSlot)
    {
        if (!$timeSlot->is_reserved) {
            $timeSlot->delete();
        }

        return redirect()->route('admin.dashboard', ['tab' => 'time_slots']);
    }

    public function storeDevice(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('devices', 'name')],
        ], [
            'name.required' => '端末名を入力してください。',
            'name.unique' => '同じ端末名は既に登録されています。',
        ]);

        Device::create($data);

        return redirect()->route('admin.dashboard', ['tab' => 'devices']);
    }

    public function updateDevice(Request $request, Device $device)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('devices', 'name')->ignore($device->id)],
        ], [
            'name.required' => '端末名を入力してください。',
            'name.unique' => '同じ端末名は既に登録されています。',
        ]);

        $device->update($data);

        return redirect()->route('admin.dashboard', ['tab' => 'devices']);
    }

    public function destroyDevice(Device $device)
    {
        if (!$device->canBeDeleted()) {
            return redirect()
                ->route('admin.dashboard', ['tab' => 'devices'])
                ->withErrors(['device' => '未対応の予約があるため削除できません。']);
        }

        $device->delete();

        return redirect()->route('admin.dashboard', ['tab' => 'devices']);
    }

    public function restoreDevice(int $deviceId)
    {
        $device = Device::withTrashed()->findOrFail($deviceId);
        $device->restore();

        return redirect()->route('admin.dashboard', ['tab' => 'devices']);
    }

    public function storeSymptom(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('symptoms', 'name')],
            'device_ids' => ['required', 'array'],
            'device_ids.*' => ['exists:devices,id'],
        ], [
            'name.required' => '症状名を入力してください。',
            'name.unique' => '同じ症状名は既に登録されています。',
            'device_ids.required' => '対応端末を選択してください。',
        ]);

        $symptom = Symptom::create([
            'name' => $data['name'],
        ]);
        $symptom->devices()->sync($data['device_ids']);

        return redirect()->route('admin.dashboard', ['tab' => 'symptoms']);
    }

    public function updateSymptom(Request $request, Symptom $symptom)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('symptoms', 'name')->ignore($symptom->id)],
            'device_ids' => ['required', 'array'],
            'device_ids.*' => ['exists:devices,id'],
        ], [
            'name.required' => '症状名を入力してください。',
            'name.unique' => '同じ症状名は既に登録されています。',
            'device_ids.required' => '対応端末を選択してください。',
        ]);

        $symptom->update([
            'name' => $data['name'],
        ]);
        $symptom->devices()->sync($data['device_ids']);

        return redirect()->route('admin.dashboard', ['tab' => 'symptoms']);
    }

    public function destroySymptom(Symptom $symptom)
    {
        if (!$symptom->canBeDeleted()) {
            return redirect()
                ->route('admin.dashboard', ['tab' => 'symptoms'])
                ->withErrors(['symptom' => '未対応の予約があるため削除できません。']);
        }

        $symptom->delete();

        return redirect()->route('admin.dashboard', ['tab' => 'symptoms']);
    }

    public function restoreSymptom(int $symptomId)
    {
        $symptom = Symptom::withTrashed()->findOrFail($symptomId);
        $symptom->restore();

        return redirect()->route('admin.dashboard', ['tab' => 'symptoms']);
    }

    public function storePart(Request $request)
    {
        $data = $request->validate([
            'device_id' => ['required', 'exists:devices,id'],
            'name' => ['required', 'string', 'max:100'],
            'stock' => ['required', 'integer', 'min:0'],
            'symptom_ids' => ['required', 'array'],
            'symptom_ids.*' => ['exists:symptoms,id'],
        ], [
            'device_id.required' => '対応端末を選択してください。',
            'name.required' => '部品名を入力してください。',
            'stock.required' => '在庫数を入力してください。',
            'stock.integer' => '在庫数は数字で入力してください。',
            'stock.min' => '在庫数は0以上で入力してください。',
            'symptom_ids.required' => '対応症状を選択してください。',
        ]);

        if ($this->containsVisitConsultation($data['symptom_ids'])) {
            return redirect()
                ->route('admin.dashboard', ['tab' => 'parts', 'add_part' => 1])
                ->withErrors(['symptom_ids' => '来店相談は部品を必要としないため選択できません。'])
                ->withInput();
        }

        $part = Part::create([
            'device_id' => $data['device_id'],
            'name' => $data['name'],
            'stock' => $data['stock'],
        ]);
        $part->symptoms()->sync($data['symptom_ids']);

        return redirect()->route('admin.dashboard', ['tab' => 'parts']);
    }

    public function updatePart(Request $request, Part $part)
    {
        $data = $request->validate([
            'device_id' => ['required', 'exists:devices,id'],
            'name' => ['required', 'string', 'max:100'],
            'stock' => ['required', 'integer', 'min:0'],
            'symptom_ids' => ['required', 'array'],
            'symptom_ids.*' => ['exists:symptoms,id'],
        ], [
            'device_id.required' => '対応端末を選択してください。',
            'name.required' => '部品名を入力してください。',
            'stock.required' => '在庫数を入力してください。',
            'stock.integer' => '在庫数は数字で入力してください。',
            'stock.min' => '在庫数は0以上で入力してください。',
            'symptom_ids.required' => '対応症状を選択してください。',
        ]);

        if ($this->containsVisitConsultation($data['symptom_ids'])) {
            return redirect()
                ->route('admin.dashboard', ['tab' => 'parts', 'edit_part' => $part->id])
                ->withErrors(['symptom_ids' => '来店相談は部品を必要としないため選択できません。'])
                ->withInput();
        }

        $part->update([
            'device_id' => $data['device_id'],
            'name' => $data['name'],
            'stock' => $data['stock'],
        ]);
        $part->symptoms()->sync($data['symptom_ids']);

        return redirect()->route('admin.dashboard', ['tab' => 'parts']);
    }

    public function destroyPart(Part $part)
    {
        if (!$part->canBeDeleted()) {
            return redirect()
                ->route('admin.dashboard', ['tab' => 'parts'])
                ->withErrors(['part' => '未対応の予約があるため削除できません。']);
        }

        $part->delete();

        return redirect()->route('admin.dashboard', ['tab' => 'parts']);
    }

    public function restorePart(int $partId)
    {
        $part = Part::withTrashed()->findOrFail($partId);
        $part->restore();

        return redirect()->route('admin.dashboard', ['tab' => 'parts']);
    }

    private function closeExpiredTimeSlots(): void
    {
        TimeSlot::where('slot_at', '<=', now())
            ->where('is_reserved', false)
            ->update(['is_open' => false]);
    }

    private function containsVisitConsultation(array $symptomIds): bool
    {
        return Symptom::whereIn('id', $symptomIds)
            ->where('name', '来店相談')
            ->exists();
    }

    private function markNoShowReservations(): void
    {
        Reservation::where('status', 'pending')
            ->whereHas('timeSlot', function ($query) {
                $query->where('slot_at', '<=', now());
            })
            ->update(['status' => 'no_show']);
    }
}
