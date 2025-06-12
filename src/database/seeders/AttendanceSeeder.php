<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\RestTime;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run()
    {
        // 一般ユーザーのみを取得（管理者を除く）
        $users = User::where('role', 'user')->get();

        // 各ユーザーに対して過去30日分の勤怠データを作成
        foreach ($users as $user) {
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                
                // 土日はスキップ（平日のみ勤怠データを作成）
                if ($date->isWeekend()) {
                    continue;
                }

                // ランダムで勤怠パターンを決定
                $pattern = rand(1, 10);
                
                if ($pattern <= 8) {
                    // 80%の確率で通常勤務
                    $this->createNormalAttendance($user, $date);
                } elseif ($pattern === 9) {
                    // 10%の確率で遅刻
                    $this->createLateAttendance($user, $date);
                } else {
                    // 10%の確率で早退
                    $this->createEarlyLeaveAttendance($user, $date);
                }
            }
        }
    }

    /**
     * 通常勤務の勤怠データを作成
     */
    private function createNormalAttendance($user, $date)
    {
        $startHour = rand(8, 9); // 8-9時に出勤
        $startMinute = rand(0, 59);
        $endHour = rand(17, 19); // 17-19時に退勤
        $endMinute = rand(0, 59);

        $startTime = $date->copy()->setTime($startHour, $startMinute);
        $endTime = $date->copy()->setTime($endHour, $endMinute);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $date->format('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => '退勤済',
            'remarks' => $this->getRandomRemarks()
        ]);

        // 休憩時間を追加（昼休み）
        $this->addLunchBreak($attendance, $date);

        // ランダムで追加の休憩時間を作成（30%の確率）
        if (rand(1, 10) <= 3) {
            $this->addAdditionalBreak($attendance, $date);
        }
    }

    /**
     * 遅刻の勤怠データを作成
     */
    private function createLateAttendance($user, $date)
    {
        $startHour = rand(9, 11); // 9-11時に遅刻出勤
        $startMinute = rand(0, 59);
        $endHour = rand(18, 20); // 遅刻分を考慮して遅めに退勤
        $endMinute = rand(0, 59);

        $startTime = $date->copy()->setTime($startHour, $startMinute);
        $endTime = $date->copy()->setTime($endHour, $endMinute);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $date->format('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => '退勤済',
            'remarks' => '電車遅延のため遅刻'
        ]);

        $this->addLunchBreak($attendance, $date);
    }

    /**
     * 早退の勤怠データを作成
     */
    private function createEarlyLeaveAttendance($user, $date)
    {
        $startHour = rand(8, 9);
        $startMinute = rand(0, 59);
        $endHour = rand(15, 16); // 15-16時に早退
        $endMinute = rand(0, 59);

        $startTime = $date->copy()->setTime($startHour, $startMinute);
        $endTime = $date->copy()->setTime($endHour, $endMinute);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $date->format('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => '退勤済',
            'remarks' => '体調不良のため早退'
        ]);

        // 早退の場合は昼休憩のみ
        if ($endHour >= 13) {
            $this->addLunchBreak($attendance, $date);
        }
    }

    /**
     * 昼休憩を追加
     */
    private function addLunchBreak($attendance, $date)
    {
        $lunchStart = rand(12, 13); // 12-13時に昼休憩開始
        $lunchStartMinute = rand(0, 30);
        $lunchEndHour = $lunchStart;
        $lunchEndMinute = $lunchStartMinute + 60; // 1時間休憩

        // 60分を超えた場合の調整
        if ($lunchEndMinute >= 60) {
            $lunchEndHour++;
            $lunchEndMinute -= 60;
        }

        RestTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => $date->copy()->setTime($lunchStart, $lunchStartMinute),
            'end_time' => $date->copy()->setTime($lunchEndHour, $lunchEndMinute)
        ]);
    }

    /**
     * 追加の休憩時間を作成
     */
    private function addAdditionalBreak($attendance, $date)
    {
        $breakPatterns = [
            // 午前の小休憩
            ['start' => [10, rand(0, 30)], 'end' => [10, rand(45, 59)]],
            // 午後の小休憩
            ['start' => [15, rand(0, 30)], 'end' => [15, rand(45, 59)]],
        ];

        $pattern = $breakPatterns[array_rand($breakPatterns)];

        RestTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => $date->copy()->setTime($pattern['start'][0], $pattern['start'][1]),
            'end_time' => $date->copy()->setTime($pattern['end'][0], $pattern['end'][1])
        ]);
    }

    /**
     * ランダムな備考を生成
     */
    private function getRandomRemarks()
    {
        $remarks = [
            '通常勤務',
            '会議多数のため忙しい1日',
            'プロジェクト作業に集中',
            '外出先からの勤務',
            '研修参加',
            '顧客打ち合わせあり',
            '資料作成業務',
            '',  // 空の備考も含む
            '',
            ''
        ];

        return $remarks[array_rand($remarks)];
    }
}