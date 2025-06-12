<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤務外の場合、勤怠ステータスが正しく表示される
     * 
     * テスト手順：
     * 1. ステータスが勤務外のユーザーにログインする
     * 2. 勤怠打刻画面を開く
     * 3. 画面に表示されているステータスを確認する
     * 
     * 期待結果：画面上に表示されているステータスが「勤務外」となる
     */
    public function test_off_duty_status_displayed_correctly(): void
    {
        $user = User::factory()->create();
        
        // 勤務外の勤怠データを作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'status' => '勤務外'
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        // ステータステキストを直接確認（空白を含む正確なマッチング）
        $response->assertSee('勤務外');
        // 出勤ボタンが表示されることも確認
        $response->assertSee('出勤');
    }

    /**
     * 出勤中の場合、勤怠ステータスが正しく表示される
     * 
     * テスト手順：
     * 1. ステータスが出勤中のユーザーにログインする
     * 2. 勤怠打刻画面を開く
     * 3. 画面に表示されているステータスを確認する
     * 
     * 期待結果：画面上に表示されているステータスが「出勤中」となる
     */
    public function test_working_status_displayed_correctly(): void
    {
        $user = User::factory()->create();
        
        // 出勤中の勤怠データを作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'status' => '出勤中',
            'start_time' => Carbon::now()->subHours(2)
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        // ステータステキストを直接確認
        $response->assertSee('出勤中');
        // 退勤・休憩入ボタンが表示されることも確認
        $response->assertSee('退勤');
        $response->assertSee('休憩入');
    }

    /**
     * 休憩中の場合、勤怠ステータスが正しく表示される
     * 
     * テスト手順：
     * 1. ステータスが休憩中のユーザーにログインする
     * 2. 勤怠打刻画面を開く
     * 3. 画面に表示されているステータスを確認する
     * 
     * 期待結果：画面上に表示されているステータスが「休憩中」となる
     */
    public function test_break_status_displayed_correctly(): void
    {
        $user = User::factory()->create();
        
        // 休憩中の勤怠データを作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'status' => '休憩中',
            'start_time' => Carbon::now()->subHours(3)
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        // ステータステキストを直接確認
        $response->assertSee('休憩中');
        // 休憩戻ボタンが表示されることも確認
        $response->assertSee('休憩戻');
    }

    /**
     * 退勤済の場合、勤怠ステータスが正しく表示される
     * 
     * テスト手順：
     * 1. ステータスが退勤済のユーザーにログインする
     * 2. 勤怠打刻画面を開く
     * 3. 画面に表示されているステータスを確認する
     * 
     * 期待結果：画面上に表示されているステータスが「退勤済」となる
     */
    public function test_finished_status_displayed_correctly(): void
    {
        $user = User::factory()->create();
        
        // 退勤済の勤怠データを作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'status' => '退勤済',
            'start_time' => Carbon::now()->subHours(8),
            'end_time' => Carbon::now()->subHour()
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        // ステータステキストを直接確認
        $response->assertSee('退勤済');
        // お疲れ様メッセージが表示されることも確認
        $response->assertSee('お疲れ様でした。');
    }
}