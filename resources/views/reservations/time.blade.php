@extends('layouts.app')

@section('title', '日時選択')

@section('content')
<main class="reservation-main">
    <section class="panel reservation-panel">
        <h1 class="reservation-title">
            {{ $device->name }}の{{ $symptom->name }}のご相談ですね<br>
            ご都合の良い日時を選択してください
        </h1>

        @if (session('message'))
            <div class="success">{{ session('message') }}</div>
        @endif

        <form action="{{ route('reservations.time.store') }}" method="POST">
            @csrf

            <select name="time_slot_id" class="select-box">
                <option value="">予約時間をご選択ください</option>
                @foreach ($timeSlots as $timeSlot)
                    <option value="{{ $timeSlot->id }}">{{ $timeSlot->slot_at->format('Y年n月j日 H:i') }}</option>
                @endforeach
            </select>
            @error('time_slot_id')<div class="error">{{ $message }}</div>@enderror

            <div class="reservation-actions">
                <a href="{{ route('reservations.symptom') }}" class="btn btn-muted">戻る</a>
                <button type="submit" class="btn btn-primary">予約確認画面へ</button>
            </div>
        </form>
    </section>
</main>
@endsection
