<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * ログイン画面を表示する
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * ログイン処理を行う
     */
    public function login(LoginRequest $request)
    {
        $request->authenticate();
        $request->session()->regenerate();

        // 一般ユーザーは打刻画面へリダイレクト
        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * ログアウト処理を行う
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}