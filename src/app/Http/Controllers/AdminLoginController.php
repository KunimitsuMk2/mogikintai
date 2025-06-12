<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Auth;

class AdminLoginController extends Controller
{
    //
    public function showLoginForm()
    {

        return view('admin.login');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email','password');

        
        

        if(Auth::attempt($credentials)){

            $request->session()->regenerate();

            return redirect()->intended(route('admin.attendance.list'));

        }
        return back()->withErrors([
            'email'=>'ログイン情報が登録されていません',
        ])->onlyInput('email');
    }

    //ログアウト
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}
