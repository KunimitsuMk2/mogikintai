<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 退勤ボタンが正しく機能する
     * 
     * テスト手順：
     * 1. ステータスが勤務中のユーザーにログインする
     * 2. 画面に「退勤」ボタンが表示されていることを確認する
     * 3. 退勤の処理を行う
     * 
     * 期待結果：画面上に「退勤」ボタンが表示され、処理後に画面上に表示されるステータスが「退勤済」になる
     */
    public function test_clock_out_button_works_correctly(): void
    {
        $user = User::factory()->create();
        $today = Carbon::today()->format('Y-m-d');
        
        // 出勤中の勤怠データを作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'status' => '出勤中',
            'start_time' => Carbon::now()->subHours(8)
        ]);

        // 1. 退勤ボタンが表示されていることを確認
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤中'); // ステータス確認
        $response->assertSee('退勤'); // 退勤ボタンテキスト確認

        // 2. 退勤の処理を行う
        $response = $this->actingAs($user)->post('/attendance/end');
        $response->assertRedirect('/attendance');

        // 3. 処理後にステータスが「退勤済」になり、メッセージが表示されることを確認
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('退勤済');
        $response->assertSee('お疲れ様でした。');
        
        // 他のボタンが表示されないことを確認
        $response->assertDontSee('出勤');
        $response->assertDontSee('休憩入');
        $response->assertDontSee('休憩戻');

        // データベースで退勤時刻が記録されていることを確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => $today,
            'status' => '退勤済'
        ]);
        
        $attendance = Attendance::where('user_id', $user->id)->where('date', $today)->first();
        $this->assertNotNull($attendance->end_time); // 退勤時刻が記録されている
    }

    /**
     * 退勤時刻が管理画面で確認できる
     * 
     * テスト手順：
     * 1. ステータスが勤務外のユーザーにログインする
     * 2. 出勤と退勤の処理を行う
     * 3. 管理画面から退勤の日付を確認する
     * 
     * 期待結果：管理画面に退勤時刻が正確に記録されている
     */
    public function test_clock_out_time_visible_in_admin_panel(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $today = Carbon::today()->format('Y-m-d');
        
        // 出勤中の勤怠データを作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'status' => '出勤中',
            'start_time' => Carbon::now()->subHours(8)
        ]);

        // 退勤の処理を行う
        $this->actingAs($user)->post('/attendance/end');

        // 管理画面から退勤時刻を確認
        $response = $this->actingAs($admin)->get('/admin/attendance/list');
        
        $response->assertStatus(200);
        $response->assertSee($user->name); // ユーザー名が表示される
        
        // 退勤時刻が記録されていることをデータベースで確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => $today,
            'status' => '退勤済'
        ]);
        
        $attendance = Attendance::where('user_id', $user->id)->where('date', $today)->first();
        $this->assertNotNull($attendance->start_time); // 出勤時刻が記録されている
        $this->assertNotNull($attendance->end_time); // 退勤時刻が記録されている
    }
}