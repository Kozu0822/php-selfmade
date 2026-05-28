@extends('layouts.app')

@section('title', '楽ペア')

@section('content')
<main class="top-main">
    <h1 class="top-copy">
        端末修理の予約を、かんたんに。<br>
        症状入力から予約確認まで、Web上でまとめて管理できます。
    </h1>

    <div class="top-action">
        @auth
            @if (Auth::user()->role === 0)
                <a href="{{ route('reservations.create') }}" class="btn btn-primary btn-large">予約はこちら</a>
            @else
                <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-large">管理画面へ</a>
            @endif
        @else
            <a href="{{ route('login') }}" class="btn btn-primary btn-large">予約はこちら</a>
        @endauth
    </div>
</main>
@endsection
