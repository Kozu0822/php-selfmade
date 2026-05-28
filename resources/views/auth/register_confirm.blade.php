@extends('layouts.app')

@section('title', '登録内容確認')

@section('content')
<main class="main auth-main">
    <section class="panel form-panel auth-panel">
        <h1 class="page-title">登録内容確認</h1>

        <table class="table">
            <tr><th>お名前</th><td>{{ $data['name'] }}</td></tr>
            <tr><th>メールアドレス</th><td>{{ $data['email'] }}</td></tr>
            <tr><th>郵便番号</th><td>{{ $data['postal_code'] }}</td></tr>
            <tr><th>住所</th><td>{{ $data['address'] }}</td></tr>
            <tr><th>電話番号</th><td>{{ $data['phone_number'] }}</td></tr>
        </table>

        <form action="{{ route('register.complete') }}" method="POST">
            @csrf
            @foreach ($data as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach

            <div class="button-row">
                <button type="submit" name="action" value="complete" class="btn btn-primary">登録する</button>
                <button type="submit" name="action" value="back" class="btn btn-secondary">修正する</button>
            </div>
        </form>
    </section>
</main>
@endsection
