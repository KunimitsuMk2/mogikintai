<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use Carbon\Carbon;

class AttendanceCorrectionRequestSeeder extends Seeder
{
    public function run()
    {
        // 一般ユーザーのみを取得
        $users = User::where('role', 'user')->get();

        foreach ($users as $user) {
            // 各ユーザーに対してランダムで修正申請を作成
            $attendances = Attendance::where('user_id', $user->id)
                ->orderBy('date', 'desc')
                ->limit(10) // 最新10件の勤怠データから
                ->get();

            foreach ($attendances as $attendance) {
                // 30%の確率で修正申請を作成
                if (rand(1, 10) <= 3) {
                    $this->createCorrectionRequest($attendance, $user);
                }
            }
        }
    }

    /**
     * 修正申請を作成
     */
    private function createCorrectionRequest($attendance, $user)
    {
        // ランダムでステータスを決定（70%が承認待ち、30%が承認済み）
        $status = rand(1, 10) <= 7 ? 'pending' : 'approved';

        // 元の時間から少し修正した時間を生成
        $originalStart = Carbon::parse($attendance->start_time);
        $originalEnd = Carbon::parse($attendance->end_time);

        // 出勤時間を±30分調整
        $newStartTime = $originalStart->copy()->addMinutes(rand(-30, 30));
        // 退勤時間を±60分調整
        $newEndTime = $originalEnd->copy()->addMinutes(rand(-60, 60));

        // 修正理由のパターン
        $reasonPatterns = [
            'タイムカードの打刻忘れがあったため修正をお願いします',
            '実際の勤務時間と記録が異なっていたため修正申請いたします',
            '外出先からの打刻ができず、手動での修正をお願いします',
            'システムエラーにより正しく記録されなかったため修正が必要です',
            '休憩時間の記録に誤りがあったため修正をお願いします',
            '会議の延長により退勤時間が遅れたため修正申請します',
            '早朝出勤の記録が漏れていたため修正をお願いします'
        ];

        // 休憩時間の修正データを生成
        $requestedBreaks = $this->generateRequestedBreaks($attendance);

        // 申請日時を勤怠日の翌日以降で、かつ今日以前に設定
        $attendanceDate = Carbon::parse($attendance->date);
        $today = Carbon::today();
        
        // 勤怠日の翌日から今日までの範囲で申請日を設定
        $minDate = $attendanceDate->copy()->addDay();
        $maxDate = $today->copy()->subDay(); // 今日の前日まで
        
        // 最小日が最大日より後の場合は、最小日を使用（今日より前に調整）
        if ($minDate->greaterThan($maxDate)) {
            $createdAt = $maxDate->copy()->setTime(rand(8, 17), rand(0, 59));
        } else {
            $daysDiff = $minDate->diffInDays($maxDate);
            $randomDays = $daysDiff > 0 ? rand(0, $daysDiff) : 0;
            $createdAt = $minDate->copy()->addDays($randomDays)->setTime(rand(8, 17), rand(0, 59));
        }

        AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_start_time' => $newStartTime,
            'requested_end_time' => $newEndTime,
            'requested_breaks' => $requestedBreaks,
            'remarks' => $reasonPatterns[array_rand($reasonPatterns)],
            'status' => $status,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    /**
     * 修正申請用の休憩時間データを生成
     */
    private function generateRequestedBreaks($attendance)
    {
        $breaks = [];

        // 元の休憩時間がある場合は少し修正
        if ($attendance->restTimes->count() > 0) {
            foreach ($attendance->restTimes as $restTime) {
                $originalStart = Carbon::parse($restTime->start_time);
                $originalEnd = Carbon::parse($restTime->end_time);

                // 休憩時間を±15分調整
                $newStart = $originalStart->copy()->addMinutes(rand(-15, 15));
                $newEnd = $originalEnd->copy()->addMinutes(rand(-15, 15));

                $breaks[] = [
                    'start' => $newStart->format('Y-m-d H:i:s'),
                    'end' => $newEnd->format('Y-m-d H:i:s')
                ];
            }
        } else {
            // 休憩時間がない場合は新しく追加
            $date = Carbon::parse($attendance->date);
            $lunchStart = $date->copy()->setTime(12, rand(0, 30));
            $lunchEnd = $lunchStart->copy()->addHour();

            $breaks[] = [
                'start' => $lunchStart->format('Y-m-d H:i:s'),
                'end' => $lunchEnd->format('Y-m-d H:i:s')
            ];
        }

        return $breaks;
    }
}