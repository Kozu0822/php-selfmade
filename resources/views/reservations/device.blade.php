@extends('layouts.app')

@section('title', '端末選択')

@section('content')
<main class="reservation-main">
    <section class="panel reservation-panel">
        <h1 class="reservation-title">予約を作成しましょう</h1>

        <form action="{{ route('reservations.device.store') }}" method="POST">
            @csrf

            <select name="device_id" class="select-box">
                <option value="">相談をご希望の機種をお選びください</option>
                @foreach ($devices as $device)
                    <option value="{{ $device->id }}">{{ $device->name }}</option>
                @endforeach
            </select>
            @error('device_id')<div class="error">{{ $message }}</div>@enderror

            <div class="reservation-actions reservation-actions-right">
                <button type="submit" class="btn btn-primary">症状選択画面へ</button>
            </div>
        </form>
    </section>
</main>
@endsection
