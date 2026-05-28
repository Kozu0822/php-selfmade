@extends('layouts.app')

@section('title', '新規登録')

@section('content')
<main class="main auth-main">
    <section class="panel form-panel auth-panel">
        <h1 class="page-title">新規登録</h1>

        <form action="{{ route('register.confirm') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="name">
                    <span>お名前</span>
                    @error('name')<span class="error">{{ $message }}</span>@enderror
                </label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}">
            </div>

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

            <div class="form-group">
                <label for="password_confirmation">
                    <span>パスワード確認</span>
                    @error('password_confirmation')<span class="error">{{ $message }}</span>@enderror
                </label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
            </div>

            <div class="form-group">
                <label for="postal_code">
                    <span>郵便番号</span>
                    @error('postal_code')<span class="error">{{ $message }}</span>@enderror
                </label>
                <input type="text" name="postal_code" id="postal_code" class="form-control" value="{{ old('postal_code') }}" placeholder="100-0001">
            </div>

            <div class="form-group">
                <label for="address">
                    <span>住所</span>
                    @error('address')<span class="error">{{ $message }}</span>@enderror
                </label>
                <input type="text" name="address" id="address" class="form-control" value="{{ old('address') }}">
            </div>

            <div class="form-group">
                <label for="phone_number">
                    <span>電話番号</span>
                    @error('phone_number')<span class="error">{{ $message }}</span>@enderror
                </label>
                <input type="text" name="phone_number" id="phone_number" class="form-control" value="{{ old('phone_number') }}" placeholder="090-1234-5678">
            </div>

            <div class="button-row">
                <button type="submit" class="btn btn-primary">確認画面へ</button>
                <a href="{{ route('top') }}" class="btn btn-secondary">戻る</a>
            </div>
        </form>
    </section>
</main>
@endsection
