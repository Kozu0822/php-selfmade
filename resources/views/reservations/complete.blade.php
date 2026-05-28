@extends('layouts.app')

@section('title', '予約完了')

@section('content')
<main class="reservation-main">
    <section class="panel reservation-panel">
        <div class="complete-message">
            <div class="check-mark">✓</div>
            <div>
                <h1 class="page-title" style="margin-bottom: 8px;">ご予約ありがとうございました！</h1>
                <p style="margin: 0;">詳細の確認・キャンセルはマイページで出来ます！</p>
            </div>
        </div>

        <div class="button-row" style="justify-content: center;">
            <a href="{{ route('mypage.index') }}" class="btn btn-primary" style="min-width: 240px;">マイページへ</a>
            <a href="{{ route('top') }}" class="btn btn-muted" style="min-width: 240px;">ホーム画面に戻る</a>
        </div>
    </section>
</main>
@endsection
