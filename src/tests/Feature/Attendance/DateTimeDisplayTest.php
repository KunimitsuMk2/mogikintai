<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class DateTimeDisplayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 現在の日時情報がUIと同じ形式で出力されている
     * 
     * テスト手順：
     * 1. 勤怠打刻画面を開く 
     * 2. 画面に表示されている日時情報を確認する
     * 
     * 期待結果：画面上に表示されている日時が現在の日時と一致する
     */
    public function test_current_datetime_displayed_in_ui_format(): void
    {
        $user = User::factory()->create();
        
        // 今日の勤怠データを作成（勤務外状態）
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->format('Y-m-d'),
            'status' => '勤務外'
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        
        // HTML要素の存在を確認（JavaScriptで更新される要素）
        $response->assertSee('id="current-date"', false);
        $response->assertSee('id="current-time"', false);
        
        // 初期表示される日付フォーマットを確認
        // コントローラーから渡される$dateの形式を確認
        $expectedDateFormat = Carbon::today()->format('Y年m月d日');
        $dayOfWeek = ['日', '月', '火', '水', '木', '金', '土'][Carbon::today()->dayOfWeek];
        $expectedDate = $expectedDateFormat . '(' . $dayOfWeek . ')';
        
        $response->assertSee($expectedDate, false);
        
        // 時刻要素の存在確認
        $response->assertSee('class="attendance__time"', false);
    }
}