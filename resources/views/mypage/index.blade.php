@extends('layouts.app')

@section('title', 'マイページ')

@section('content')
<main class="main">
    <section class="panel">
        <h1 class="page-title">マイページ</h1>
        <p class="lead">予約状況を確認できます。</p>

        <div class="button-row">
            <a href="{{ route('reservations.create') }}" class="btn btn-primary">新規予約</a>
        </div>

        @if ($reservations->isEmpty())
            <div class="empty">現在、予約はありません。</div>
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>予約ID</th>
                        <th>端末</th>
                        <th>症状</th>
                        <th>予約日時</th>
                        <th>状態</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reservations as $reservation)
                        <tr>
                            <td>{{ $reservation->id }}</td>
                            <td>{{ $reservation->device->name }}</td>
                            <td>{{ $reservation->symptom->name }}</td>
                            <td>{{ $reservation->timeSlot->slot_at->format('Y年n月j日 H:i') }}</td>
                            <td>
                                <span class="{{ $reservation->statusClass() }}">{{ $reservation->statusLabel() }}</span>
                            </td>
                            <td>
                                <div class="inline-actions">
                                    <a href="{{ route('mypage.index', ['reservation_id' => $reservation->id]) }}" class="btn btn-secondary btn-small">詳細を見る</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</main>

@if ($selectedReservation)
    @include('reservations._detail_modal', [
        'reservation' => $selectedReservation,
        'showCustomer' => false,
        'canCancel' => $selectedReservation->canBeCancelledByUser(),
        'cancelUrl' => route('reservations.cancel', $selectedReservation),
        'closeUrl' => route('mypage.index'),
    ])
@endif
@endsection
