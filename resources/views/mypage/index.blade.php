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
                                @if ($reservation->canBeCancelledByUser())
                                    <form action="{{ route('reservations.cancel', $reservation) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-cancel">キャンセル</button>
                                    </form>
                                @else
                                    <button type="button" class="btn btn-muted btn-cancel" disabled>キャンセル</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</main>
@endsection
