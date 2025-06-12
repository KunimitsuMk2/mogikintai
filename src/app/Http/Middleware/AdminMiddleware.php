<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // ログインしていない場合は管理者ログインページへリダイレクト
        if (!Auth::check()) {
            return redirect()->route('admin.login');
        }
        
        // ログインしているが管理者でない場合は403エラー
        if (!Auth::user()->isAdmin()) {
            abort(403, 'このページにアクセスする権限がありません。');
        }
        
        // 管理者の場合は次の処理に進む
        return $next($request);
    }
}