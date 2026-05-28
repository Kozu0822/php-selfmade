@extends('layouts.app')

@section('title', '登録完了')

@section('content')
<main class="main auth-main">
    <section class="panel form-panel auth-panel">
        <h1 class="page-title">登録完了</h1>
        <p class="lead">ユーザー登録が完了しました。ログイン画面からログインしてください。</p>
        <div class="button-row">
            <a href="{{ route('login') }}" class="btn btn-primary">ログインへ</a>
        </div>
    </section>
</main>
@endsection
