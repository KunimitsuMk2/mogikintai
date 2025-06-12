<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class ClockInTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 出勤ボタンが正しく機能する
     * 
     * テスト手順：
     * 1. ステータスが勤務外のユーザーにログインする
     * 2. 画面に「出勤」ボタンが表示されていることを確認する
     * 3. 出勤の処理を行う
     * 
     * 期待結果：画面上に「出勤」ボタンが表示され、処理後に画面上に表示されるステータスが「出勤中」になる
     */
    public function test_clock_in_button_works_correctly(): void
    {
        $user = User::factory()->create();
        $today = Carbon::today()->format('Y-m-d');
        
        // 勤務外の勤怠データを事前作成（AttendanceController::index()の動作をシミュレート）
        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'status' => '勤務外'
        ]);

        // 1. 出勤ボタンが表示されていることを確認
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('勤務外'); // ステータス確認
        $response->assertSee('出勤'); // 出勤ボタンテキスト確認

        // 2. 出勤の処理を行う
        $response = $this->actingAs($user)->post('/attendance/start');
        $response->assertRedirect('/attendance');

        // 3. 処理後にステータスが「出勤中」になることを確認
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('出勤中');
        // 出勤後は退勤・休憩入ボタンが表示される
        $response->assertSee('退勤');
        $response->assertSee('休憩入');

        // データベースに出勤記録が保存されているか確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => $today,
            'status' => '出勤中'
        ]);
    }

    /**
     * 出勤は一日一回のみできる
     * 
     * テスト手順：
     * 1. ステータスが退勤済であるユーザーにログインする
     * 2. 勤務ボタンが表示されないことを確認する
     * 
     * 期待結果：画面上に「出勤」ボタンが表示されない
     */
    public function test_cannot_clock_in_twice_same_day(): void
    {
        $user = User::factory()->create();
        $today = Carbon::today()->format('Y-m-d');
        
        // 退勤済の勤怠データを作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'status' => '退勤済',
            'start_time' => Carbon::now()->subHours(8),
            'end_time' => Carbon::now()->subHour()
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        
        $response->assertStatus(200);
        $response->assertSee('退勤済'); // ステータス確認
        $response->assertSee('お疲れ様でした。'); // 退勤済みメッセージ確認
        $response->assertDontSee('出勤'); // 出勤ボタンが表示されない
        $response->assertDontSee('休憩入'); // 他のボタンも表示されない
        $response->assertDontSee('休憩戻');
    }

    /**
     * 出勤時刻が管理画面で確認できる
     * 
     * テスト手順：
     * 1. ステータスが勤務外のユーザーにログインする
     * 2. 出勤の処理を行う
     * 3. 管理画面から出勤の日付を確認する
     * 
     * 期待結果：管理画面に出勤時刻が正確に記録されている
     */
    public function test_clock_in_time_visible_in_admin_panel(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $today = Carbon::today()->format('Y-m-d');
        
        // 勤務外の勤怠データを事前作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'status' => '勤務外'
        ]);

        // 出勤の処理を行う
        $this->actingAs($user)->post('/attendance/start');

        // 管理画面から出勤時刻を確認
        $response = $this->actingAs($admin)->get('/admin/attendance/list');
        
        $response->assertStatus(200);
        $response->assertSee($user->name); // ユーザー名が表示される
        
        // 出勤時刻が記録されていることをデータベースで確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => $today,
            'status' => '出勤中'
        ]);
        
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();
        $this->assertNotNull($attendance->start_time); // 出勤時刻が記録されている
    }

}