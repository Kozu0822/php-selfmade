<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;

class MypageController extends Controller
{
    public function index()
    {
        $this->markNoShowReservations();

        $reservations = Auth::user()
            ->reservations()
            ->with(['device', 'symptom', 'timeSlot'])
            ->latest()
            ->get();

        return view('mypage.index', compact('reservations'));
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
