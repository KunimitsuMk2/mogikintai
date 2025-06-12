@extends('layouts.app')

@section('title', 'ログイン')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">
@endsection

@section('content')
<div class="login-form__content">
    <h1 class="login-form__heading">ログイン</h1>

    <form class="form" method="POST" action="{{ route('login') }}">
        @csrf

        <div class="form__group">
            <div class="form__group-title">
                <label for="email">メールアドレス</label>
            </div>
            <div class="form__input--text">
                <input type="email" id="email" name="email" value="{{ old('email') }}" class="@error('email') is-invalid @enderror" required>
            </div>
            @error('email')
                <p class="form__error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form__group">
            <div class="form__group-title">
                <label for="password">パスワード</label>
            </div>
            <div class="form__input--text">
                <input type="password" id="password" name="password" class="@error('password') is-invalid @enderror" required>
            </div>
            @error('password')
                <p class="form__error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form__button">
            <button type="submit" class="form__button-submit">ログイン</button>
        </div>
    </form>

    <div class="register__link">
        <a href="{{ route('register') }}">会員登録はこちら</a>
    </div>
</div>
@endsection
