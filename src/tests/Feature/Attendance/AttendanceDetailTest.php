<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\RestTime;
use App\Models\AttendanceCorrectionRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $adminUser;
    protected $attendance;

    protected function setUp(): void
    {
        parent::setUp();
        
        // テスト用の一般ユーザーを作成
        $this->user = User::factory()->create([
            'role' => 'user'
        ]);

        // テスト用の管理者ユーザーを作成
        $this->adminUser = User::factory()->create([
            'role' => 'admin'
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
     * FN026: 詳細情報取得機能
     * 「名前」が、自分の氏名になっていること
     */
    public function test_勤怠詳細画面の名前がログインユーザーの氏名になっている()
    {
        $response = $this->actingAs($this->user)
            ->get(route('attendance.show', $this->attendance->id));

        $response->assertStatus(200);
        $response->assertSee($this->user->name);
    }

    /**
     * FN026: 詳細情報取得機能
     * 「日付」が、動線上で自分が選択した日時と一致していること
     */
    public function test_勤怠詳細画面の日付が選択した日付になっている()
    {
        $response = $this->actingAs($this->user)
            ->get(route('attendance.show', $this->attendance->id));

        $response->assertStatus(200);
        
        // 日付が正しい形式で表示されることを確認
        $expectedDate = Carbon::parse($this->attendance->date)->format('Y年m月d日');
        $response->assertSee($expectedDate);
    }

    /**
     * FN026: 詳細情報取得機能
     * 「出勤・退勤」にて記されている時間が自分の打刻と一致していること
     */
    public function test_出勤退勤時間がログインユーザーの打刻と一致している()
    {
        $response = $this->actingAs($this->user)
            ->get(route('attendance.show', $this->attendance->id));

        $response->assertStatus(200);
        
        // 出勤時間と退勤時間が入力フィールドに正しく表示されることを確認
        $startTime = Carbon::parse($this->attendance->start_time)->format('H:i');
        $endTime = Carbon::parse($this->attendance->end_time)->format('H:i');
        
        $response->assertSee('value="' . $startTime . '"', false);
        $response->assertSee('value="' . $endTime . '"', false);
    }

    /**
     * FN026: 詳細情報取得機能
     * 「休憩」にて記されている時間がログインユーザーの打刻と一致していること
     */
    public function test_休憩時間がログインユーザーの打刻と一致している()
    {
        // 休憩時間を追加
        $restTime = RestTime::create([
            'attendance_id' => $this->attendance->id,
            'start_time' => Carbon::today()->setTime(12, 0),
            'end_time' => Carbon::today()->setTime(13, 0)
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('attendance.show', $this->attendance->id));

        $response->assertStatus(200);
        
        // 休憩時間が入力フィールドに正しく表示されることを確認
        $restStartTime = Carbon::parse($restTime->start_time)->format('H:i');
        $restEndTime = Carbon::parse($restTime->end_time)->format('H:i');
        
        $response->assertSee('value="' . $restStartTime . '"', false);
        $response->assertSee('value="' . $restEndTime . '"', false);
    }

    /**
     * FN026: 詳細情報取得機能
     * 休憩回数分のレコードと追加で１つ分の入力フィールドが表示されること
     */
    public function test_休憩回数分のレコードと追加入力フィールドが表示される()
    {
        // 複数の休憩時間を追加
        RestTime::create([
            'attendance_id' => $this->attendance->id,
            'start_time' => Carbon::today()->setTime(10, 30),
            'end_time' => Carbon::today()->setTime(10, 45)
        ]);

        RestTime::create([
            'attendance_id' => $this->attendance->id,
            'start_time' => Carbon::today()->setTime(15, 0),
            'end_time' => Carbon::today()->setTime(15, 15)
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('attendance.show', $this->attendance->id));

        $response->assertStatus(200);
        
        // 既存の休憩時間が表示されることを確認
        $response->assertSee('10:30');
        $response->assertSee('10:45');
        $response->assertSee('15:00');
        $response->assertSee('15:15');
        
        // 追加入力フィールドがあることを確認
        $response->assertSee('rest_times[2][start]');
        $response->assertSee('rest_times[2][end]');
    }

    /**
     * FN029: エラーメッセージ表示機能
     * 出勤時間が退勤時間より後になっている場合のエラーメッセージ
     */
    public function test_出勤時間が退勤時間より後の場合エラーメッセージが表示される()
    {
        $response = $this->actingAs($this->user)
            ->post(route('attendance.update', $this->attendance->id), [
                'start_time' => '18:00',
                'end_time' => '09:00',
                'remarks' => '修正申請です'
            ]);

        $response->assertSessionHasErrors(['start_time']);
        $response->assertSessionHasErrorsIn('default', [
            'start_time' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /**
     * FN029: エラーメッセージ表示機能
     * 休憩開始時間が退勤時間より後になっている場合のエラーメッセージ
     */
    public function test_休憩開始時間が退勤時間より後の場合エラーメッセージが表示される()
    {
        $response = $this->actingAs($this->user)
            ->post(route('attendance.update', $this->attendance->id), [
                'start_time' => '09:00',
                'end_time' => '18:00',
                'rest_times' => [
                    ['start' => '19:00', 'end' => '20:00']
                ],
                'remarks' => '修正申請です'
            ]);

        $response->assertSessionHasErrors(['rest_times.0.start']);
        $response->assertSessionHasErrorsIn('default', [
            'rest_times.0.start' => '休憩時間が勤務時間外です'
        ]);
    }

    /**
     * FN029: エラーメッセージ表示機能
     * 休憩終了時間が退勤時間より後になっている場合のエラーメッセージ
     */
    public function test_休憩終了時間が退勤時間より後の場合エラーメッセージが表示される()
    {
        $response = $this->actingAs($this->user)
            ->post(route('attendance.update', $this->attendance->id), [
                'start_time' => '09:00',
                'end_time' => '18:00',
                'rest_times' => [
                    ['start' => '17:00', 'end' => '19:00']
                ],
                'remarks' => '修正申請です'
            ]);

        $response->assertSessionHasErrors(['rest_times.0.start']);
        $response->assertSessionHasErrorsIn('default', [
            'rest_times.0.start' => '休憩時間が勤務時間外です'
        ]);
    }

    /**
     * FN029: エラーメッセージ表示機能
     * 備考欄が未入力の場合のエラーメッセージ
     */
    public function test_備考欄が未入力の場合エラーメッセージが表示される()
    {
        $response = $this->actingAs($this->user)
            ->post(route('attendance.update', $this->attendance->id), [
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
     * FN030: 修正申請機能
     * 修正申請処理が実行される
     */
    public function test_修正申請処理が実行される()
    {
        $response = $this->actingAs($this->user)
            ->post(route('attendance.update', $this->attendance->id), [
                'start_time' => '10:00',
                'end_time' => '19:00',
                'rest_times' => [
                    ['start' => '12:00', 'end' => '13:00']
                ],
                'remarks' => '修正申請です'
            ]);

        // 申請一覧画面にリダイレクトされることを確認
        $response->assertRedirect(route('correction.list'));
        $response->assertSessionHas('success', '修正申請を送信しました。承認されるまでお待ちください');

        // データベースに修正申請が保存されることを確認
        $this->assertDatabaseHas('attendance_correction_requests', [
            'attendance_id' => $this->attendance->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'remarks' => '修正申請です'
        ]);

        $correctionRequest = AttendanceCorrectionRequest::where('attendance_id', $this->attendance->id)->first();
        $this->assertEquals('10:00', Carbon::parse($correctionRequest->requested_start_time)->format('H:i'));
        $this->assertEquals('19:00', Carbon::parse($correctionRequest->requested_end_time)->format('H:i'));
        
        // 休憩時間がJSONで保存されることを確認
        $this->assertNotEmpty($correctionRequest->requested_breaks);
        $breaks = $correctionRequest->requested_breaks;
        $this->assertEquals('12:00', Carbon::parse($breaks[0]['start'])->format('H:i'));
        $this->assertEquals('13:00', Carbon::parse($breaks[0]['end'])->format('H:i'));
    }

    /**
     * FN033: 申請詳細表示機能
     * 「承認待ち」の申請詳細は修正を行うことができず、メッセージが表示される
     */
    public function test_承認待ちの申請詳細は修正できずメッセージが表示される()
    {
        // 承認待ちの修正申請を作成
        AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance->id,
            'user_id' => $this->user->id,
            'requested_start_time' => Carbon::today()->setTime(10, 0),
            'requested_end_time' => Carbon::today()->setTime(19, 0),
            'remarks' => '修正申請です',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('attendance.show', $this->attendance->id));

        $response->assertStatus(200);
        $response->assertSee('承認待ちのため修正はできません。');
        
        // 入力フィールドがdisabledになっていることを確認
        $response->assertSee('disabled', false);
    }

    /**
     * 承認済みの申請がある場合の表示確認
     */
    public function test_承認済みの申請がある場合の表示()
    {
        // 承認済みの修正申請を作成
        AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance->id,
            'user_id' => $this->user->id,
            'requested_start_time' => Carbon::today()->setTime(10, 0),
            'requested_end_time' => Carbon::today()->setTime(19, 0),
            'remarks' => '修正申請です',
            'status' => 'approved'
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('attendance.show', $this->attendance->id));

        $response->assertStatus(200);
        $response->assertSee('承認済み');
        
        // 入力フィールドがdisabledになっていることを確認
        $response->assertSee('disabled', false);
    }

    /**
     * 他のユーザーの勤怠詳細にはアクセスできない
     */
    public function test_他のユーザーの勤怠詳細にはアクセスできない()
    {
        $otherUser = User::factory()->create(['role' => 'user']);
        
        $response = $this->actingAs($otherUser)
            ->get(route('attendance.show', $this->attendance->id));

        $response->assertStatus(403);
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
     * 承認待ちの申請がある場合は新しい修正申請ができない
     */
    public function test_承認待ちの申請がある場合は新しい修正申請ができない()
    {
        // 承認待ちの修正申請を作成
        AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance->id,
            'user_id' => $this->user->id,
            'requested_start_time' => Carbon::today()->setTime(10, 0),
            'requested_end_time' => Carbon::today()->setTime(19, 0),
            'remarks' => '最初の修正申請',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('attendance.update', $this->attendance->id), [
                'start_time' => '11:00',
                'end_time' => '20:00',
                'remarks' => '二回目の修正申請'
            ]);

        $response->assertRedirect(route('attendance.show', $this->attendance->id));
        $response->assertSessionHas('error', '承認待ちのため修正はできません。');
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
        $response = $this->actingAs($this->user)
            ->get(route('attendance.show', 99999));

        $response->assertStatus(404);
    }
}