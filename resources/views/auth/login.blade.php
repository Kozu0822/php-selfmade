@extends('layouts.app')

@section('title', 'ログイン')

@section('content')
<main class="main auth-main">
    <section class="panel form-panel auth-panel login-panel">
        <h1 class="page-title">ログイン</h1>

        <form action="{{ route('login.post') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="email">
                    <span>メールアドレス</span>
                    @error('email')<span class="error">{{ $message }}</span>@enderror
                </label>
                <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}">
            </div>

            <div class="form-group">
                <label for="password">
                    <span>パスワード</span>
                    @error('password')<span class="error">{{ $message }}</span>@enderror
                </label>
                <input type="password" name="password" id="password" class="form-control">
            </div>

            <div class="button-row">
                <button type="submit" class="btn btn-primary">ログイン</button>
                <a href="{{ route('register') }}" class="btn btn-secondary">新規登録</a>
            </div>
        </form>
    </section>
</main>
@endsection
