@extends('layouts.app')

@section('title', '管理者ログイン')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/login.css') }}">
@endsection

@section('content')
<div class="admin-login__content">
    <div class="admin-login__container">
        <h1 class="admin-login__heading">管理者ログイン</h1>
        
        <form action="{{ route('admin.login') }}" method="POST" class="admin-login__form">
            @csrf
            
            <!-- メールアドレス -->
            <div class="admin-login__form-group">
                <label for="email" class="admin-login__label">メールアドレス</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       class="admin-login__input @error('email') admin-login__input--error @enderror"
                       value="{{ old('email') }}"
                       autocomplete="email">
                @error('email')
                <div class="admin-login__error">{{ $message }}</div>
                @enderror
            </div>
            
            <!-- パスワード -->
            <div class="admin-login__form-group">
                <label for="password" class="admin-login__label">パスワード</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       class="admin-login__input @error('password') admin-login__input--error @enderror"
                       autocomplete="current-password">
                @error('password')
                <div class="admin-login__error">{{ $message }}</div>
                @enderror
            </div>
            
            <!-- ログインボタン -->
            <div class="admin-login__button-container">
                <button type="submit" class="admin-login__button">管理者ログインする</button>
            </div>
        </form>
    </div>
</div>
@endsection