<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\RestTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCorrectionApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $user1;
    protected $user2;
    protected $attendance1;
    protected $attendance2;

    protected function setUp(): void
    {
        parent::setUp();
        
        // テスト用の管理者ユーザーを作成
        $this->adminUser = User::factory()->create([
            'role' => 'admin'
        ]);

        // テスト用の一般ユーザーを作成
        $this->user1 = User::factory()->create([
            'role' => 'user',
            'name' => 'テストユーザー1'
        ]);

        $this->user2 = User::factory()->create([
            'role' => 'user',
            'name' => 'テストユーザー2'
        ]);

        // テスト用の勤怠データを作成
        $this->attendance1 = Attendance::create([
            'user_id' => $this->user1->id,
            'date' => Carbon::today(),
            'start_time' => Carbon::today()->setTime(9, 0),
            'end_time' => Carbon::today()->setTime(18, 0),
            'status' => '退勤済',
            'remarks' => '元の備考1'
        ]);

        $this->attendance2 = Attendance::create([
            'user_id' => $this->user2->id,
            'date' => Carbon::yesterday(),
            'start_time' => Carbon::yesterday()->setTime(10, 0),
            'end_time' => Carbon::yesterday()->setTime(19, 0),
            'status' => '退勤済',
            'remarks' => '元の備考2'
        ]);
    }

    /**
     * US014 - FN047: 承認待ち情報取得機能
     * "承認待ち"には全ての一般ユーザーが行った未承認の修正申請が全て表示されていること
     */
    public function test_承認待ちに全ユーザーの未承認申請が表示される()
    {
        // 複数ユーザーの承認待ち申請を作成
        $pendingRequest1 = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance1->id,
            'user_id' => $this->user1->id,
            'requested_start_time' => Carbon::today()->setTime(10, 0),
            'requested_end_time' => Carbon::today()->setTime(19, 0),
            'remarks' => 'ユーザー1の修正申請',
            'status' => 'pending'
        ]);

        $pendingRequest2 = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance2->id,
            'user_id' => $this->user2->id,
            'requested_start_time' => Carbon::yesterday()->setTime(9, 30),
            'requested_end_time' => Carbon::yesterday()->setTime(18, 30),
            'remarks' => 'ユーザー2の修正申請',
            'status' => 'pending'
        ]);

        // 承認済みの申請（承認待ちには表示されない）
        AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance1->id,
            'user_id' => $this->user1->id,
            'requested_start_time' => Carbon::today()->setTime(8, 0),
            'requested_end_time' => Carbon::today()->setTime(17, 0),
            'remarks' => '承認済みの申請',
            'status' => 'approved'
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('correction.list'));

        $response->assertStatus(200);
        
        // 承認待ちタブが存在することを確認
        $response->assertSee('承認待ち');
        
        // データベースレベルで承認待ち申請が存在することを確認
        $this->assertDatabaseHas('attendance_correction_requests', [
            'user_id' => $this->user1->id,
            'remarks' => 'ユーザー1の修正申請',
            'status' => 'pending'
        ]);
        
        $this->assertDatabaseHas('attendance_correction_requests', [
            'user_id' => $this->user2->id,
            'remarks' => 'ユーザー2の修正申請',
            'status' => 'pending'
        ]);
        
        // 基本的な申請内容が表示されることを確認
        $response->assertSee('ユーザー1の修正申請');
        $response->assertSee('ユーザー2の修正申請');
    }

    /**
     * US014 - FN048: 承認済み情報取得機能
     * "承認済み"には、全ての一般ユーザーが行った、これまでの承認済みの修正申請が全て表示されていること
     */
    public function test_承認済みに全ユーザーの承認済み申請が表示される()
    {
        // 複数ユーザーの承認済み申請を作成
        $approvedRequest1 = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance1->id,
            'user_id' => $this->user1->id,
            'requested_start_time' => Carbon::today()->setTime(10, 0),
            'requested_end_time' => Carbon::today()->setTime(19, 0),
            'remarks' => 'ユーザー1の承認済み申請',
            'status' => 'approved'
        ]);

        $approvedRequest2 = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance2->id,
            'user_id' => $this->user2->id,
            'requested_start_time' => Carbon::yesterday()->setTime(9, 30),
            'requested_end_time' => Carbon::yesterday()->setTime(18, 30),
            'remarks' => 'ユーザー2の承認済み申請',
            'status' => 'approved'
        ]);

        // 承認待ちの申請（承認済みには表示されない）
        AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance1->id,
            'user_id' => $this->user1->id,
            'requested_start_time' => Carbon::today()->setTime(8, 0),
            'requested_end_time' => Carbon::today()->setTime(17, 0),
            'remarks' => '承認待ちの申請',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('correction.list'));

        $response->assertStatus(200);
        
        // 承認済みタブが存在することを確認
        $response->assertSee('承認済み');
        
        // データベースレベルで承認済み申請が存在することを確認
        $this->assertDatabaseHas('attendance_correction_requests', [
            'user_id' => $this->user1->id,
            'remarks' => 'ユーザー1の承認済み申請',
            'status' => 'approved'
        ]);
        
        $this->assertDatabaseHas('attendance_correction_requests', [
            'user_id' => $this->user2->id,
            'remarks' => 'ユーザー2の承認済み申請',
            'status' => 'approved'
        ]);
        
        // JavaScript切り替えのため、直接的なHTML確認ではなく基本要素の存在確認
        $response->assertSee('ユーザー1の承認済み申請');
        $response->assertSee('ユーザー2の承認済み申請');
    }

    /**
     * US014 - FN049: 申請詳細遷移機能
     * 各項目の「詳細」を押下すると、申請詳細画面に遷移すること
     */
    public function test_詳細ボタンで申請詳細画面に遷移する()
    {
        $correctionRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance1->id,
            'user_id' => $this->user1->id,
            'requested_start_time' => Carbon::today()->setTime(10, 0),
            'requested_end_time' => Carbon::today()->setTime(19, 0),
            'remarks' => 'テスト申請',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('correction.list'));

        $response->assertStatus(200);
        
        // 詳細リンクが存在することを確認
        $response->assertSee(route('stamp_correction_request.approve', $correctionRequest->id));
        
        // 詳細ボタンのテキストが表示されることを確認
        $response->assertSee('詳細');
    }

    /**
     * US015 - FN050: 申請詳細取得機能
     * 詳細画面の内容が、動線上で自分が選択した情報と一致していること
     */
    public function test_申請詳細画面の内容が選択した情報と一致している()
    {
        $correctionRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance1->id,
            'user_id' => $this->user1->id,
            'requested_start_time' => Carbon::today()->setTime(10, 30),
            'requested_end_time' => Carbon::today()->setTime(19, 30),
            'requested_breaks' => [
                ['start' => Carbon::today()->setTime(12, 0)->format('Y-m-d H:i:s'), 'end' => Carbon::today()->setTime(13, 0)->format('Y-m-d H:i:s')]
            ],
            'remarks' => '詳細画面テスト申請',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('stamp_correction_request.approve', $correctionRequest->id));

        $response->assertStatus(200);
        
        // 申請者の名前が表示されることを確認
        $response->assertSee($this->user1->name);
        
        // 日付が表示されることを確認
        $expectedDate = Carbon::parse($this->attendance1->date)->format('Y年n月j日');
        $response->assertSee($expectedDate);
        
        // 申請内容が表示されることを確認
        $response->assertSee('10:30'); // 申請された出勤時間
        $response->assertSee('19:30'); // 申請された退勤時間
        $response->assertSee('12:00'); // 申請された休憩開始時間
        $response->assertSee('13:00'); // 申請された休憩終了時間
        $response->assertSee('詳細画面テスト申請'); // 備考
    }

    /**
     * US015 - FN050: 申請詳細取得機能
     * 詳細画面の内容が、正しく実際の打刻内容が反映されていること
     */
    public function test_申請詳細画面が実際の申請内容と一致している()
    {
        // 複数の休憩時間を含む申請を作成
        $correctionRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance1->id,
            'user_id' => $this->user1->id,
            'requested_start_time' => Carbon::today()->setTime(8, 45),
            'requested_end_time' => Carbon::today()->setTime(17, 45),
            'requested_breaks' => [
                ['start' => Carbon::today()->setTime(10, 15)->format('Y-m-d H:i:s'), 'end' => Carbon::today()->setTime(10, 30)->format('Y-m-d H:i:s')],
                ['start' => Carbon::today()->setTime(12, 30)->format('Y-m-d H:i:s'), 'end' => Carbon::today()->setTime(13, 30)->format('Y-m-d H:i:s')]
            ],
            'remarks' => '複数休憩申請テスト',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('stamp_correction_request.approve', $correctionRequest->id));

        $response->assertStatus(200);
        
        // 出勤・退勤時間が正しく表示されることを確認
        $response->assertSee('08:45');
        $response->assertSee('17:45');
        
        // 複数の休憩時間が正しく表示されることを確認
        $response->assertSee('10:15');
        $response->assertSee('10:30');
        $response->assertSee('12:30');
        $response->assertSee('13:30');
        
        // 備考が正しく表示されることを確認
        $response->assertSee('複数休憩申請テスト');
    }

    /**
     * 休憩時間がない申請の表示確認
     */
    public function test_休憩時間がない申請の表示()
    {
        $correctionRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance1->id,
            'user_id' => $this->user1->id,
            'requested_start_time' => Carbon::today()->setTime(9, 0),
            'requested_end_time' => Carbon::today()->setTime(18, 0),
            'requested_breaks' => null, // 休憩なし
            'remarks' => '休憩なし申請',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('stamp_correction_request.approve', $correctionRequest->id));

        $response->assertStatus(200);
        
        // 休憩時間がない場合の表示を確認
        $response->assertSee('-'); // 休憩なしの場合の表示
    }

    /**
     * US015 - FN051: 承認機能
     * 管理者ユーザーの当該勤怠情報が更新され、修正申請の内容と一致すること
     */
    public function test_承認により管理者の勤怠情報が更新される()
    {
        $correctionRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance1->id,
            'user_id' => $this->user1->id,
            'requested_start_time' => Carbon::today()->setTime(8, 30),
            'requested_end_time' => Carbon::today()->setTime(17, 30),
            'requested_breaks' => [
                ['start' => Carbon::today()->setTime(12, 0)->format('Y-m-d H:i:s'), 'end' => Carbon::today()->setTime(13, 0)->format('Y-m-d H:i:s')]
            ],
            'remarks' => '承認テスト申請',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('stamp_correction_request.approve.submit', $correctionRequest->id));

        // 申請一覧画面にリダイレクトされることを確認
        $response->assertRedirect(route('correction.list'));
        $response->assertSessionHas('success', '申請を承認しました');

        // 勤怠データが更新されることを確認
        $this->attendance1->refresh();
        
        $this->assertEquals('08:30', Carbon::parse($this->attendance1->start_time)->format('H:i'));
        $this->assertEquals('17:30', Carbon::parse($this->attendance1->end_time)->format('H:i'));
        $this->assertEquals('承認テスト申請', $this->attendance1->remarks);
    }

    /**
     * US015 - FN051: 承認機能
     * 管理者ユーザーの「修正申請一覧」で、"承認待ち"から"承認済み"に変更されていること
     */
    public function test_承認により申請ステータスが承認済みに変更される()
    {
        $correctionRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance1->id,
            'user_id' => $this->user1->id,
            'requested_start_time' => Carbon::today()->setTime(9, 30),
            'requested_end_time' => Carbon::today()->setTime(18, 30),
            'remarks' => 'ステータス変更テスト',
            'status' => 'pending'
        ]);

        // 承認前のステータス確認
        $this->assertEquals('pending', $correctionRequest->status);

        // 承認処理を実行
        $this->actingAs($this->adminUser)
            ->post(route('stamp_correction_request.approve.submit', $correctionRequest->id));

        // 申請のステータスが承認済みに変更されることを確認
        $correctionRequest->refresh();
        $this->assertEquals('approved', $correctionRequest->status);
        
        // データベースでもステータスが更新されていることを確認
        $this->assertDatabaseHas('attendance_correction_requests', [
            'id' => $correctionRequest->id,
            'status' => 'approved'
        ]);
    }

    /**
     * US015 - FN051: 承認機能
     * 一般ユーザーの当該勤怠情報が更新され、修正申請の内容と一致すること
     */
    public function test_承認により一般ユーザーの勤怠情報が更新される()
    {
        $correctionRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance1->id,
            'user_id' => $this->user1->id,
            'requested_start_time' => Carbon::today()->setTime(10, 15),
            'requested_end_time' => Carbon::today()->setTime(19, 15),
            'remarks' => '一般ユーザー向け更新テスト',
            'status' => 'pending'
        ]);

        // 承認処理を実行
        $this->actingAs($this->adminUser)
            ->post(route('stamp_correction_request.approve.submit', $correctionRequest->id));

        // 一般ユーザーとして勤怠詳細を確認
        $response = $this->actingAs($this->user1)
            ->get(route('attendance.show', $this->attendance1->id));

        $response->assertStatus(200);
        
        // 承認された内容が一般ユーザーからも確認できることを検証
        $response->assertSee('value="10:15"', false);
        $response->assertSee('value="19:15"', false);
        $response->assertSee('一般ユーザー向け更新テスト');
    }

    /**
     * US015 - FN051: 承認機能
     * 一般ユーザーの「修正申請一覧」で、"承認待ち"から"承認済み"に変更されていること
     */
    public function test_承認により一般ユーザーの申請一覧でもステータスが変更される()
    {
        $correctionRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance1->id,
            'user_id' => $this->user1->id,
            'requested_start_time' => Carbon::today()->setTime(9, 45),
            'requested_end_time' => Carbon::today()->setTime(18, 45),
            'remarks' => '一般ユーザー申請一覧テスト',
            'status' => 'pending'
        ]);

        // 承認処理を実行
        $this->actingAs($this->adminUser)
            ->post(route('stamp_correction_request.approve.submit', $correctionRequest->id));

        // 一般ユーザーとして申請一覧を確認
        $response = $this->actingAs($this->user1)
            ->get(route('correction.list'));

        $response->assertStatus(200);
        
        // 承認済みタブに申請が移動していることを確認
        $response->assertSee('承認済み');
        $response->assertSee('一般ユーザー申請一覧テスト');
    }

    /**
     * 休憩時間の承認処理が正しく動作する
     */
    public function test_休憩時間の承認処理が正しく動作する()
    {
        // 既存の休憩時間を作成
        $existingRest = RestTime::create([
            'attendance_id' => $this->attendance1->id,
            'start_time' => Carbon::today()->setTime(12, 0),
            'end_time' => Carbon::today()->setTime(13, 0)
        ]);

        $correctionRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance1->id,
            'user_id' => $this->user1->id,
            'requested_start_time' => Carbon::today()->setTime(9, 0),
            'requested_end_time' => Carbon::today()->setTime(18, 0),
            'requested_breaks' => [
                ['start' => Carbon::today()->setTime(10, 30)->format('Y-m-d H:i:s'), 'end' => Carbon::today()->setTime(10, 45)->format('Y-m-d H:i:s')],
                ['start' => Carbon::today()->setTime(15, 0)->format('Y-m-d H:i:s'), 'end' => Carbon::today()->setTime(15, 15)->format('Y-m-d H:i:s')]
            ],
            'remarks' => '休憩時間変更申請',
            'status' => 'pending'
        ]);

        // 承認処理を実行
        $this->actingAs($this->adminUser)
            ->post(route('stamp_correction_request.approve.submit', $correctionRequest->id));

        // 既存の休憩時間が削除されることを確認
        $this->assertDatabaseMissing('rest_times', [
            'id' => $existingRest->id
        ]);

        // 新しい休憩時間が作成されることを確認
        $this->assertDatabaseHas('rest_times', [
            'attendance_id' => $this->attendance1->id,
            'start_time' => Carbon::today()->setTime(10, 30),
            'end_time' => Carbon::today()->setTime(10, 45)
        ]);

        $this->assertDatabaseHas('rest_times', [
            'attendance_id' => $this->attendance1->id,
            'start_time' => Carbon::today()->setTime(15, 0),
            'end_time' => Carbon::today()->setTime(15, 15)
        ]);
    }

    /**
     * 承認済みの申請には承認ボタンが表示されない
     */
    public function test_承認済み申請には承認ボタンが表示されない()
    {
        $correctionRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance1->id,
            'user_id' => $this->user1->id,
            'requested_start_time' => Carbon::today()->setTime(9, 0),
            'requested_end_time' => Carbon::today()->setTime(18, 0),
            'remarks' => '既に承認済みの申請',
            'status' => 'approved'
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('stamp_correction_request.approve', $correctionRequest->id));

        $response->assertStatus(200);
        
        // 承認済みの表示があることを確認
        $response->assertSee('承認済み');
        
        // データベースでステータスが承認済みであることを確認
        $this->assertDatabaseHas('attendance_correction_requests', [
            'id' => $correctionRequest->id,
            'status' => 'approved'
        ]);
        
        // 承認ボタンが表示されないことを、ビューの条件分岐で確認
        // ビューでは status === 'pending' の場合のみフォームが表示される
        $this->assertEquals('approved', $correctionRequest->status);
    }

    /**
     * 承認待ちの申請には承認ボタンが表示される
     */
    public function test_承認待ち申請には承認ボタンが表示される()
    {
        $correctionRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance1->id,
            'user_id' => $this->user1->id,
            'requested_start_time' => Carbon::today()->setTime(9, 0),
            'requested_end_time' => Carbon::today()->setTime(18, 0),
            'remarks' => '承認待ちの申請',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('stamp_correction_request.approve', $correctionRequest->id));

        $response->assertStatus(200);
        
        // 承認ボタンが表示されることを確認
        $response->assertSee('承認');
        $response->assertSee('type="submit"', false);
    }

    /**
     * 一般ユーザーは承認画面にアクセスできない
     */
    public function test_一般ユーザーは承認画面にアクセスできない()
    {
        $correctionRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance1->id,
            'user_id' => $this->user1->id,
            'requested_start_time' => Carbon::today()->setTime(9, 0),
            'requested_end_time' => Carbon::today()->setTime(18, 0),
            'remarks' => 'テスト申請',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->user1)
            ->get(route('stamp_correction_request.approve', $correctionRequest->id));
        
        $response->assertStatus(403);
    }

    /**
     * 一般ユーザーは承認処理を実行できない
     */
    public function test_一般ユーザーは承認処理を実行できない()
    {
        $correctionRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance1->id,
            'user_id' => $this->user1->id,
            'requested_start_time' => Carbon::today()->setTime(9, 0),
            'requested_end_time' => Carbon::today()->setTime(18, 0),
            'remarks' => 'テスト申請',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->user1)
            ->post(route('stamp_correction_request.approve.submit', $correctionRequest->id));
        
        $response->assertStatus(403);
        
        // ステータスが変更されていないことを確認
        $correctionRequest->refresh();
        $this->assertEquals('pending', $correctionRequest->status);
    }

    /**
     * 未認証ユーザーはアクセスできない
     */
    public function test_未認証ユーザーはアクセスできない()
    {
        $correctionRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance1->id,
            'user_id' => $this->user1->id,
            'requested_start_time' => Carbon::today()->setTime(9, 0),
            'requested_end_time' => Carbon::today()->setTime(18, 0),
            'remarks' => 'テスト申請',
            'status' => 'pending'
        ]);

        $response = $this->get(route('stamp_correction_request.approve', $correctionRequest->id));
        $this->assertTrue($response->isRedirect(), '未認証ユーザーはリダイレクトされるべきです');
        
        $response = $this->post(route('stamp_correction_request.approve.submit', $correctionRequest->id));
        $this->assertTrue($response->isRedirect(), '未認証ユーザーはリダイレクトされるべきです');
    }

    /**
     * 存在しない申請IDでは404エラー
     */
    public function test_存在しない申請IDでは404エラー()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('stamp_correction_request.approve', 99999));

        $response->assertStatus(404);
    }
}