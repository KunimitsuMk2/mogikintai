<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\RestTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $user;
    protected $attendance;

    protected function setUp(): void
    {
        parent::setUp();
        
        // テスト用の管理者ユーザーを作成
        $this->adminUser = User::factory()->create([
            'role' => 'admin'
        ]);

        // テスト用の一般ユーザーを作成
        $this->user = User::factory()->create([
            'role' => 'user'
        ]);

        // テスト用の勤怠データを作成
        $this->attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'start_time' => Carbon::today()->setTime(9, 0),
            'end_time' => Carbon::today()->setTime(18, 0),
            'status' => '退勤済',
            'remarks' => 'テスト備考'
        ]);
    }

    /**
     * FN037: 詳細情報取得機能
     * 詳細画面の内容が、動線上で自分が選択した情報と一致していること
     */
    public function test_詳細画面の内容が選択した情報と一致している()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('attendance.show', $this->attendance->id));

        $response->assertStatus(200);
        
        // 選択したユーザーの情報が表示されることを確認
        $response->assertSee($this->user->name);
        
        // 選択した日付が表示されることを確認
        $expectedDate = Carbon::parse($this->attendance->date)->format('Y年m月d日');
        $response->assertSee($expectedDate);
        
        // 備考が表示されることを確認
        $response->assertSee('テスト備考');
    }

    /**
     * FN037: 詳細情報取得機能
     * 詳細画面の内容が、正しく実際の勤怠内容が反映されていること
     */
    public function test_詳細画面の内容が実際の勤怠内容と一致している()
    {
        // 休憩時間を追加
        RestTime::create([
            'attendance_id' => $this->attendance->id,
            'start_time' => Carbon::today()->setTime(12, 0),
            'end_time' => Carbon::today()->setTime(13, 0)
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('attendance.show', $this->attendance->id));

        $response->assertStatus(200);
        
        // 出勤・退勤時間が正しく表示されることを確認
        $startTime = Carbon::parse($this->attendance->start_time)->format('H:i');
        $endTime = Carbon::parse($this->attendance->end_time)->format('H:i');
        
        $response->assertSee('value="' . $startTime . '"', false);
        $response->assertSee('value="' . $endTime . '"', false);
        
        // 休憩時間が正しく表示されることを確認
        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="13:00"', false);
    }

    /**
     * FN039: バリデーション機能
     * 出勤時間が退勤時間より後になっている場合のエラーメッセージ
     */
    public function test_出勤時間が退勤時間より後の場合エラーメッセージが表示される()
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.attendance.update', $this->attendance->id), [
                'start_time' => '18:00',
                'end_time' => '09:00',
                'remarks' => '管理者による修正'
            ]);

        $response->assertSessionHasErrors(['start_time']);
        $response->assertSessionHasErrorsIn('default', [
            'start_time' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /**
     * FN039: バリデーション機能
     * 休憩開始時間及び休憩終了時間が、出勤時間及び退勤時間を超えている際のエラーメッセージ
     */
    public function test_休憩時間が勤務時間外の場合エラーメッセージが表示される()
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.attendance.update', $this->attendance->id), [
                'start_time' => '09:00',
                'end_time' => '18:00',
                'rest_times' => [
                    ['start' => '19:00', 'end' => '20:00'] // 勤務時間外
                ],
                'remarks' => '管理者による修正'
            ]);

        $response->assertSessionHasErrors(['rest_times.0.start']);
        $response->assertSessionHasErrorsIn('default', [
            'rest_times.0.start' => '休憩時間が勤務時間外です'
        ]);
    }

    /**
     * FN039: バリデーション機能
     * 備考欄が未入力になっている際のエラーメッセージ
     */
    public function test_備考欄が未入力の場合エラーメッセージが表示される()
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.attendance.update', $this->attendance->id), [
                'start_time' => '09:00',
                'end_time' => '18:00',
                'remarks' => ''
            ]);

        $response->assertSessionHasErrors(['remarks']);
        $response->assertSessionHasErrorsIn('default', [
            'remarks' => '備考を記入してください'
        ]);
    }

    /**
     * FN040: 修正機能
     * 「修正」ボタンを押下すると、管理者として直接修正が実行されること
     */
    public function test_管理者として直接修正が実行される()
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.attendance.update', $this->attendance->id), [
                'start_time' => '10:00',
                'end_time' => '19:00',
                'rest_times' => [
                    ['start' => '12:30', 'end' => '13:30']
                ],
                'remarks' => '管理者による直接修正'
            ]);

        // スタッフ別勤怠一覧ページにリダイレクトされることを確認
        $currentMonth = Carbon::parse($this->attendance->date)->format('Y-m');
        $response->assertRedirect(route('admin.attendance.staff', [
            'user' => $this->attendance->user_id,
            'month' => $currentMonth
        ]));
        
        $response->assertSessionHas('success', '勤怠データを更新しました');

        // データベースの勤怠データが直接更新されることを確認
        $this->attendance->refresh();
        
        $this->assertEquals('10:00', Carbon::parse($this->attendance->start_time)->format('H:i'));
        $this->assertEquals('19:00', Carbon::parse($this->attendance->end_time)->format('H:i'));
        $this->assertEquals('管理者による直接修正', $this->attendance->remarks);
    }

    /**
     * FN040: 修正機能
     * 修正された内容は一般ユーザーの勤怠情報としても反映されていること
     */
    public function test_修正された内容が一般ユーザーの勤怠情報としても反映される()
    {
        // 管理者が修正を実行
        $this->actingAs($this->adminUser)
            ->post(route('admin.attendance.update', $this->attendance->id), [
                'start_time' => '08:30',
                'end_time' => '17:30',
                'rest_times' => [
                    ['start' => '12:00', 'end' => '13:00']
                ],
                'remarks' => '管理者による修正'
            ]);

        // 一般ユーザーとして勤怠詳細を確認
        $response = $this->actingAs($this->user)
            ->get(route('attendance.show', $this->attendance->id));

        $response->assertStatus(200);
        
        // 修正された内容が一般ユーザーからも確認できることを検証
        $response->assertSee('value="08:30"', false);
        $response->assertSee('value="17:30"', false);
        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="13:00"', false);
        $response->assertSee('管理者による修正');
    }

    /**
     * 休憩時間の更新処理が正しく動作する
     */
    public function test_休憩時間の更新処理が正しく動作する()
    {
        // 既存の休憩時間を作成
        $existingRest = RestTime::create([
            'attendance_id' => $this->attendance->id,
            'start_time' => Carbon::today()->setTime(12, 0),
            'end_time' => Carbon::today()->setTime(13, 0)
        ]);

        // 管理者が休憩時間を変更
        $this->actingAs($this->adminUser)
            ->post(route('admin.attendance.update', $this->attendance->id), [
                'start_time' => '09:00',
                'end_time' => '18:00',
                'rest_times' => [
                    ['start' => '11:30', 'end' => '12:30'],
                    ['start' => '15:00', 'end' => '15:15']
                ],
                'remarks' => '休憩時間修正'
            ]);

        // 既存の休憩時間が削除され、新しい休憩時間が作成されることを確認
        $this->assertDatabaseMissing('rest_times', [
            'id' => $existingRest->id
        ]);

        $this->assertDatabaseHas('rest_times', [
            'attendance_id' => $this->attendance->id,
            'start_time' => Carbon::today()->setTime(11, 30),
            'end_time' => Carbon::today()->setTime(12, 30)
        ]);

        $this->assertDatabaseHas('rest_times', [
            'attendance_id' => $this->attendance->id,
            'start_time' => Carbon::today()->setTime(15, 0),
            'end_time' => Carbon::today()->setTime(15, 15)
        ]);
    }

    /**
     * 空の休憩時間は保存されない
     */
    public function test_空の休憩時間は保存されない()
    {
        $this->actingAs($this->adminUser)
            ->post(route('admin.attendance.update', $this->attendance->id), [
                'start_time' => '09:00',
                'end_time' => '18:00',
                'rest_times' => [
                    ['start' => '12:00', 'end' => '13:00'], // 有効な休憩時間
                    ['start' => '', 'end' => ''],           // 空の休憩時間
                    ['start' => '15:00', 'end' => '']       // 不完全な休憩時間
                ],
                'remarks' => '一部空白の休憩時間'
            ]);

        // 有効な休憩時間のみが保存されることを確認
        $this->assertDatabaseHas('rest_times', [
            'attendance_id' => $this->attendance->id,
            'start_time' => Carbon::today()->setTime(12, 0),
            'end_time' => Carbon::today()->setTime(13, 0)
        ]);

        // 休憩レコードが1つだけ存在することを確認
        $this->assertEquals(1, RestTime::where('attendance_id', $this->attendance->id)->count());
    }

    /**
     * 管理者は他のユーザーの勤怠詳細にアクセスできる
     */
    public function test_管理者は他のユーザーの勤怠詳細にアクセスできる()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('attendance.show', $this->attendance->id));

        $response->assertStatus(200);
        $response->assertSee($this->user->name);
    }

    /**
     * 一般ユーザーは他のユーザーの勤怠詳細にアクセスできない
     */
    public function test_一般ユーザーは他のユーザーの勤怠詳細にアクセスできない()
    {
        $otherUser = User::factory()->create(['role' => 'user']);
        
        $response = $this->actingAs($otherUser)
            ->get(route('attendance.show', $this->attendance->id));

        $response->assertStatus(403);
    }

    /**
     * 一般ユーザーは管理者の修正ルートにアクセスできない
     */
    public function test_一般ユーザーは管理者の修正ルートにアクセスできない()
    {
        $response = $this->actingAs($this->user)
            ->post(route('admin.attendance.update', $this->attendance->id), [
                'start_time' => '10:00',
                'end_time' => '19:00',
                'remarks' => '一般ユーザーからの修正試行'
            ]);

        $response->assertStatus(403);
        
        // データベースが変更されていないことを確認
        $this->attendance->refresh();
        $this->assertEquals('09:00', Carbon::parse($this->attendance->start_time)->format('H:i'));
        $this->assertEquals('18:00', Carbon::parse($this->attendance->end_time)->format('H:i'));
    }

    /**
     * 未認証ユーザーはアクセスできない
     */
    public function test_未認証ユーザーはアクセスできない()
    {
        $response = $this->get(route('attendance.show', $this->attendance->id));
        
        $response->assertRedirect(route('login'));
    }

    /**
     * 存在しない勤怠IDでは404エラー
     */
    public function test_存在しない勤怠IDでは404エラー()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('attendance.show', 99999));

        $response->assertStatus(404);
    }

    /**
     * エラー時のリダイレクト先が正しい
     */
    public function test_エラー時のリダイレクト先が正しい()
    {
        // バリデーションエラーを発生させる
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.attendance.update', $this->attendance->id), [
                'start_time' => '',  // 必須エラー
                'end_time' => '18:00',
                'remarks' => '修正テスト'
            ]);

        // エラー時は適切にリダイレクトされることを確認
        $this->assertTrue($response->isRedirect());
        $response->assertSessionHasErrors();
    }
}