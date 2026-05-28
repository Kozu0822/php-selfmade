<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    public function index()
    {
        return view('auth.register');
    }

    public function confirm(Request $request)
    {
        $rules = [
            'name' => ['required', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
            'postal_code' => ['required', 'max:8', 'regex:/^[0-9-]+$/'],
            'address' => ['required', 'max:255'],
            'phone_number' => ['required', 'max:20', 'regex:/^[0-9-]+$/'],
        ];

        $messages = [
            'name.required' => 'お名前は必須入力です。',
            'email.required' => 'メールアドレスは必須入力です。',
            'email.email' => 'メールアドレスの形式で入力してください。',
            'email.unique' => 'このメールアドレスは既に登録されています。',
            'password.required' => 'パスワードは必須入力です。',
            'password.min' => 'パスワードは8文字以上で入力してください。',
            'password.confirmed' => 'パスワードが一致しません。',
            'postal_code.required' => '郵便番号は必須入力です。',
            'postal_code.regex' => '郵便番号は半角数字で入力してください。',
            'address.required' => '住所は必須入力です。',
            'phone_number.required' => '電話番号は必須入力です。',
            'phone_number.regex' => '電話番号は半角数字で入力してください。',
        ];

        $data = $request->validate($rules, $messages);

        return view('auth.register_confirm', compact('data'));
    }

    public function complete(Request $request)
    {
        if ($request->input('action') === 'back') {
            return redirect()->route('register')->withInput($request->except('action'));
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
            'postal_code' => ['required', 'max:8', 'regex:/^[0-9-]+$/'],
            'address' => ['required', 'max:255'],
            'phone_number' => ['required', 'max:20', 'regex:/^[0-9-]+$/'],
        ], [
            'email.unique' => 'このメールアドレスは既に登録されています。',
            'postal_code.regex' => '郵便番号は半角数字で入力してください。',
            'phone_number.regex' => '電話番号は半角数字で入力してください。',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('register')
                ->withErrors($validator)
                ->withInput($request->except('password', 'password_confirmation', 'action'));
        }

        $data = $validator->validated();

        User::create([
            'role' => 0,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'postal_code' => $data['postal_code'],
            'address' => $data['address'],
            'phone_number' => $data['phone_number'],
        ]);

        return view('auth.register_complete');
    }
}
