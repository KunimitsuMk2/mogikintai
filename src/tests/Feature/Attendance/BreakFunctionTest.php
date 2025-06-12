<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use App\Models\RestTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class BreakFunctionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 休憩ボタンが正しく機能する
     * 
     * テスト手順：
     * 1. ステータスが出勤中のユーザーにログインする
     * 2. 画面に「休憩入」ボタンが表示されていることを確認する
     * 3. 休憩の処理を行う
     * 
     * 期待結果：画面上に「休憩入」ボタンが表示され、処理後に画面上に表示されるステータスが「休憩中」になる
     */
    public function test_break_start_button_works_correctly(): void
    {
        $user = User::factory()->create();
        $today = Carbon::today()->format('Y-m-d');
        
        // 出勤中の勤怠データを作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'status' => '出勤中',
            'start_time' => Carbon::now()->subHours(2)
        ]);

        // 1. 休憩入ボタンが表示されていることを確認
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤中'); // ステータス確認
        $response->assertSee('<button type="submit" class="attendance__button attendance__button--secondary">休憩入</button>', false);

        // 2. 休憩の処理を行う
        $response = $this->actingAs($user)->post('/attendance/break-start');
        $response->assertRedirect('/attendance');

        // 3. 処理後にステータスが「休憩中」になることを確認
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩中');
        $response->assertSee('休憩戻'); // 休憩戻ボタンが表示される
        $response->assertDontSee('休憩入'); // 休憩入ボタンは表示されない

        // 休憩記録が作成されていることを確認
        $this->assertDatabaseHas('rest_times', [
            'attendance_id' => $attendance->id
        ]);
    }

    /**
     * 休憩は一日に何回でもできる
     * 
     * テスト手順：
     * 1. ステータスが出勤中であるユーザーにログインする
     * 2. 休憩入と休憩戻の処理を行う
     * 3. 「休憩入」ボタンが表示されることを確認する
     * 
     * 期待結果：画面上に「休憩入」ボタンが表示される
     */
    public function test_can_take_multiple_breaks_per_day(): void
    {
        $user = User::factory()->create();
        $today = Carbon::today()->format('Y-m-d');
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'status' => '出勤中',
            'start_time' => Carbon::now()->subHours(4)
        ]);

        // 1回目の休憩
        $this->actingAs($user)->post('/attendance/break-start');
        $this->actingAs($user)->post('/attendance/break-end');

        // 再度出勤中の状態で休憩ボタンが表示される
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('出勤中');
        $response->assertSee('休憩入'); // 2回目の休憩ボタンが表示される

        // 2回目の休憩
        $response = $this->actingAs($user)->post('/attendance/break-start');
        $response->assertRedirect('/attendance');

        // 複数の休憩記録が作成されていることを確認
        $restTimes = RestTime::where('attendance_id', $attendance->id)->count();
        $this->assertEquals(2, $restTimes);
    }

}