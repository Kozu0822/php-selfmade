<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '楽ペア')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="header @auth @if (Auth::user()->role === 1) header-admin @endif @endauth">
        <a href="{{ route('top') }}" class="brand">楽ペア</a>
        <input type="checkbox" id="nav-toggle" class="nav-toggle">
        <label for="nav-toggle" class="nav-menu-button" aria-label="メニュー">☰</label>
        <div class="header-nav">
            @auth
                @if (Auth::user()->role === 1)
                    <span>{{ Auth::user()->name }}さん、お疲れ様です</span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-secondary">ログアウト</button>
                    </form>
                @else
                    <span>{{ Auth::user()->name }}さん、ようこそ</span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-plain header-link">ログアウト</button>
                    </form>
                    <a href="{{ route('mypage.index') }}" class="header-link">マイページ</a>
                    <a href="{{ route('reservations.create') }}" class="btn btn-primary">新規予約</a>
                @endif
            @else
                <a href="{{ route('login') }}" class="header-link">ログイン</a>
                <a href="{{ route('register') }}" class="btn btn-primary">新規登録</a>
            @endauth
        </div>
    </header>

    @yield('content')

    <footer class="footer">
        &copy;2026楽ペア
    </footer>
</body>
</html>


