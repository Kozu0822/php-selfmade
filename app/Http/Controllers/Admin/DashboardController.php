<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Part;
use App\Models\Reservation;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $this->closeExpiredTimeSlots();
        $this->markNoShowReservations();

        $tab = $request->query('tab', 'reservations');

        if (!in_array($tab, ['reservations', 'time_slots', 'parts'])) {
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
        $parts = Part::with('device')
            ->orderBy('id')
            ->paginate(10, ['*'], 'parts_page')
            ->appends(['tab' => 'parts']);
        $selectedReservation = null;

        if ($request->query('reservation_id')) {
            $selectedReservation = Reservation::with(['user', 'device', 'symptom', 'timeSlot'])
                ->find($request->query('reservation_id'));
        }

        return view('admin.dashboard', compact('tab', 'reservations', 'timeSlots', 'parts', 'selectedReservation'));
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

    private function closeExpiredTimeSlots(): void
    {
        TimeSlot::where('slot_at', '<=', now())
            ->where('is_reserved', false)
            ->update(['is_open' => false]);
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
