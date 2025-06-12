<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\adminLoginController;
use App\Http\Controllers\adminAttendanceController;


use Illuminate\Support\Facades\Route;

// ルートURLを打刻画面にリダイレクト（認証済みの場合）またはログイン画面にリダイレクト（未認証の場合）
Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'attendance.index' : 'login');
});

// 認証関連
Route::middleware('guest')->group(function () {
    // 一般ユーザーログイン
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    
    //管理者ログイン
    Route::get('/admin/login',[AdminLoginController::class,'showLoginForm'])->name('admin.login');
    Route::post('/admin/login',[AdminLoginController::class,'login']);

    // 会員登録
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

// ログアウト（認証済みユーザーのみ）
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');
//ログアウト（管理者ユーザー）
Route::post('/admin/logout',[AdminLoginController::class,'logout'])->middleware(['auth','admin'])->name('admin.logout');

// 勤怠管理ルート（認証済みユーザーのみアクセス可能）
Route::middleware(['auth'])->group(function () {
    // 勤怠打刻ページ
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    
    // 各種打刻処理
    Route::post('/attendance/start', [AttendanceController::class, 'startWork'])->name('attendance.start');
    Route::post('/attendance/break-start', [AttendanceController::class, 'startBreak'])->name('attendance.break-start');
    Route::post('/attendance/break-end', [AttendanceController::class, 'endBreak'])->name('attendance.break-end');
    Route::post('/attendance/end', [AttendanceController::class, 'endWork'])->name('attendance.end');
    // 勤怠一覧ルート
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/{attendance}',[AttendanceController::class,'show'])->name('attendance.show');
    Route::post('/attendance/{attendance}/update', [AttendanceController::class, 'update'])->name('attendance.update');
    Route::get('/stamp_correction_request/list',[AttendanceController::class,'correctionList'])->name('correction.list');

    //管理者用ルート
    Route::middleware(['auth','admin'])->group(function(){
        Route::get('/admin/attendance/list',[AdminAttendanceController::class,'list'])->name('admin.attendance.list');
        Route::get('/admin/staff/list',[AdminAttendanceController::class,'staffList'])->name('admin.staff.list');
        Route::get('/admin/attendance/staff/{user}',[AdminAttendanceController::class,'staffAttendance'])->name('admin.attendance.staff');
        Route::post('/admin/attendance/{attendance}/update',[AdminAttendanceController::class,'updateAttendance'])->name('admin.attendance.update');
        Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [AdminAttendanceController::class, 'showApprovalForm'])->name                  ('stamp_correction_request.approve');
        Route::post('/stamp_correction_request/approve/{attendance_correct_request}', [AdminAttendanceController::class, 'approveRequest'])->name('stamp_correction_request.approve.submit');
        //CSV出力
        Route::get('/admin/attendance/staff/{user}/csv',[AdminAttendanceController::class,'exportCsv'])->name('admin.attendance.staff.csv');
        
    });
});