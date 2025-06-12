<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    /**
     * 会員登録画面を表示する
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * 会員登録処理を行う
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user'
        ]);

        event(new Registered($user));

        Auth::login($user);

        // 登録後は直接打刻画面へリダイレクト 
        return redirect(RouteServiceProvider::HOME);
    }
}