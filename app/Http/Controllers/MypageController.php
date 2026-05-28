<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MypageController extends Controller
{
    public function index(Request $request)
    {
        $this->markNoShowReservations();

        $reservations = Auth::user()
            ->reservations()
            ->with(['device', 'symptom', 'timeSlot'])
            ->latest()
            ->get();
        $selectedReservation = null;

        if ($request->query('reservation_id')) {
            $selectedReservation = Auth::user()
                ->reservations()
                ->with(['user', 'device', 'symptom', 'timeSlot'])
                ->find($request->query('reservation_id'));
        }

        return view('mypage.index', compact('reservations', 'selectedReservation'));
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
