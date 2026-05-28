<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8'],
        ], [
            'email.required' => 'メールアドレスは必須入力です。',
            'email.email' => 'メールアドレスの形式で入力してください。',
            'password.required' => 'パスワードは必須入力です。',
            'password.min' => 'パスワードは8文字以上で入力してください。',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            if (Auth::user()->role === 1) {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->route('mypage.index');
        }

        return back()
            ->withErrors(['email' => 'メールアドレスまたはパスワードが正しくありません。'])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('top');
    }
}
