@extends('layouts.app')

@section('title', '予約確認')

@section('content')
<main class="reservation-main">
    <section class="panel reservation-panel">
        <h1 class="reservation-title">下記の通りに予約を作成してよろしいでしょうか</h1>

        @error('parts')<div class="error">{{ $message }}</div>@enderror

        <div class="confirm-list">
            <div class="confirm-row">
                <div>機種</div>
                <div>{{ $device->name }}</div>
            </div>
            <div class="confirm-row">
                <div>症状</div>
                <div>{{ $symptom->name }}</div>
            </div>
            <div class="confirm-row">
                <div>予約時間</div>
                <div>{{ $timeSlot->slot_at->format('Y年n月j日 H:i') }}</div>
            </div>
            <div class="confirm-row">
                <div>必要部品</div>
                <div>
                    @if ($parts->isEmpty())
                        なし
                    @else
                        <div class="stacked-text">
                            @foreach ($parts as $part)
                                <span>{{ $part->name }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <form action="{{ route('reservations.store') }}" method="POST">
            @csrf
            <div class="button-row" style="justify-content: center;">
                <button type="submit" class="btn btn-primary" style="min-width: 240px;">送信</button>
                <a href="{{ route('reservations.time') }}" class="btn btn-muted" style="min-width: 240px;">戻る</a>
            </div>
        </form>
    </section>
</main>
@endsection
