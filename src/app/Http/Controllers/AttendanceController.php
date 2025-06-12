<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\RestTime;
use App\Models\AttendanceCorrectionRequest;
use Carbon\Carbon;
use App\Http\Requests\AttendanceCorrectionFormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * 勤怠打刻ページの表示
     */
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today()->format('Y-m-d');
        
        // 今日の勤怠記録を取得または作成
        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            ['status' => '勤務外']
        );
        
        // 日付と時間をフォーマット（JavaScriptで更新されるため初期値のみ）
        $date = Carbon::today()->format('Y年m月d日') . '(' . $this->getDayOfWeekKanji(Carbon::today()->dayOfWeek) . ')';
        $time = Carbon::now()->format('H:i');
        
        return view('attendance', compact('attendance', 'date', 'time'));
    }
    
    /**
     * 出勤処理
     */
    // AttendanceController::startWork()が以下のようになっているか確認
    public function startWork()
    {
        $user = Auth::user();
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();
    
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();
        
        if ($attendance && $attendance->status === '勤務外') {
            $attendance->update([
                'start_time' => $now,
                'status' => '出勤中'
            ]);
        }
    
        return redirect()->route('attendance.index');
    }
    
    /**
     * 休憩開始処理
     */
    public function startBreak()
    {
        $user = Auth::user();
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now()->second(0)->microsecond(0);
        
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();
            
        if ($attendance && $attendance->status === '出勤中') {
            // 勤怠状態を更新
            $attendance->update(['status' => '休憩中']);
            
            // 休憩記録を作成
            RestTime::create([
                'attendance_id' => $attendance->id,
                'start_time' => $now
            ]);
        }
        
        return redirect()->route('attendance.index');
    }
    
    /**
     * 休憩終了処理
     */
    public function endBreak()
    {
        $user = Auth::user();
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now()->second(0)->microsecond(0);
        
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();
            
        if ($attendance && $attendance->status === '休憩中') {
            // 勤怠状態を更新
            $attendance->update(['status' => '出勤中']);
            
            // 最新の未終了休憩を取得して終了時間を記録
            $restTime = $attendance->restTimes()->whereNull('end_time')->latest()->first();
            if ($restTime) {
                $restTime->update(['end_time' => $now]);
            }
        }
        
        return redirect()->route('attendance.index');
    }
    
    /**
     * 退勤処理
     */
    public function endWork()
    {
        $user = Auth::user();
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now()->second(0)->microsecond(0);
        
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();
            
        if ($attendance && $attendance->status === '出勤中') {
            $attendance->update([
                'end_time' => $now,
                'status' => '退勤済'
            ]);
            
            // フラッシュメッセージをセット
            session()->flash('message', 'お疲れ様でした。');
        }
        
        return redirect()->route('attendance.index');
    }
    
    /**
     * 曜日の日本語表記を取得
     */
    private function getDayOfWeekKanji($dayOfWeek)
    {
        $days = ['日', '月', '火', '水', '木', '金', '土'];
        return $days[$dayOfWeek];
    }

    public function list(Request $request)
    {
        $user = Auth::user();

        //クエリパラメータから月を取得
        $month = $request->query('month',Carbon::now()->format('Y-m'));

        //文字列形式の年月をCarbonオブジェクトに変換
        $date = Carbon::createFromFormat('Y-m',$month);

        //前月と翌月を計算
        $previousMonth = Carbon::createFromFormat('Y-m',$month)->subMonth()->format('Y-m');
        $nextMonth = Carbon::createFromFormat('Y-m',$month)->addMonth()->format('Y-m');

        //ユーザーの勤怠情報を取得
        $attendances = Attendance::where('user_id',$user->id)
            ->whereYear('date',$date->year)
            ->whereMonth('date',$date->month)
            ->get()
            ->keyBy(function($item){
                return Carbon::parse($item->date)->format('Y-m-d');
            });

            // その月の全日程を生成
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        $allDays = [];

        for($day = $startOfMonth->copy(); $day <= $endOfMonth; $day->addDay()){
            $dateKey = $day->format('Y-m-d');
            $allDays[] = [
                'date' => $day->copy(),
                'attendance' => $attendances->get($dateKey)
            ];
        }

        //勤怠一覧ページを表示
        return view('attendance.list',compact('allDays','month','previousMonth','nextMonth'));


    }
    public function show(Attendance $attendance)
{
    // アクセス権限の確認（自分の勤怠データのみ閲覧可能）
    if($attendance->user_id !== Auth::id() && !Auth::user()->isAdmin()){
        abort(403,'権限がありません');
    }

    // 修正申請がすでに出されているか確認（pending と approved 両方）
    $pendingRequest = AttendanceCorrectionRequest::where('attendance_id',$attendance->id)
        ->where('status','pending')
        ->first();

    $approvedRequest = AttendanceCorrectionRequest::where('attendance_id',$attendance->id)
        ->where('status','approved')
        ->first();

    // 管理者フラグ
    $isAdmin = Auth::user()->isAdmin();

    // 表示用データの準備
    $displayData = $this->prepareDisplayData($attendance, $pendingRequest, $approvedRequest);

    return view('attendance.show', compact(
        'attendance', 
        'pendingRequest', 
        'approvedRequest', 
        'isAdmin',
        'displayData'
    ));
}

/**
 * 表示用データを準備する
 */
private function prepareDisplayData($attendance, $pendingRequest, $approvedRequest)
{
    // 承認待ちの申請がある場合は申請データを優先
    if ($pendingRequest) {
        return [
            'start_time' => $pendingRequest->requested_start_time,
            'end_time' => $pendingRequest->requested_end_time,
            'rest_times' => $this->formatRequestedBreaks($pendingRequest->requested_breaks),
            'remarks' => $pendingRequest->remarks,
            'data_source' => 'pending_request'
        ];
    }
    
    // 承認済みの申請がある場合も申請データを表示
    if ($approvedRequest) {
        return [
            'start_time' => $approvedRequest->requested_start_time,
            'end_time' => $approvedRequest->requested_end_time,
            'rest_times' => $this->formatRequestedBreaks($approvedRequest->requested_breaks),
            'remarks' => $approvedRequest->remarks,
            'data_source' => 'approved_request'
        ];
    }
    
    // 申請がない場合は元の勤怠データを表示
    return [
        'start_time' => $attendance->start_time,
        'end_time' => $attendance->end_time,
        'rest_times' => $attendance->restTimes,
        'remarks' => $attendance->remarks,
        'data_source' => 'original_attendance'
    ];
}

/**
 * 申請の休憩時間データを表示用にフォーマット
 */
private function formatRequestedBreaks($requestedBreaks)
{
    if (!$requestedBreaks) {
        return collect();
    }
    
    return collect($requestedBreaks)->map(function($break) {
        return (object)[
            'start_time' => $break['start'],
            'end_time' => $break['end']
        ];
    });
}
    

    
    public function update(AttendanceCorrectionFormRequest $request, Attendance $attendance)
    {
    try {
        // すでに申請中かチェック
        $pendingRequest = AttendanceCorrectionRequest::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->first();

        if ($pendingRequest) {
            return redirect()->route('attendance.show', $attendance->id)
                ->with('error', '承認待ちのため修正はできません。');
        }

        $date = Carbon::parse($attendance->date)->format('Y-m-d');
        $startDateTime = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $request->start_time);
        $endDateTime = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $request->end_time);

        // 休憩時間の整形
        $restTimes = [];
        if ($request->filled('rest_times')) {
            foreach ($request->rest_times as $restTime) {
                // 両方の時間が入力されている場合のみ処理
                if (!empty($restTime['start']) && !empty($restTime['end'])) {
                    $restStartDateTime = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $restTime['start']);
                    $restEndDateTime = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $restTime['end']);

                    $restTimes[] = [
                        'start' => $restStartDateTime->format('Y-m-d H:i:s'),
                        'end' => $restEndDateTime->format('Y-m-d H:i:s')
                    ];
                }
            }
        }

        // 修正データを作成
        $correctionRequest = new AttendanceCorrectionRequest();
        $correctionRequest->attendance_id = $attendance->id;
        $correctionRequest->user_id = Auth::id();
        $correctionRequest->requested_start_time = $startDateTime;
        $correctionRequest->requested_end_time = $endDateTime;
        $correctionRequest->requested_breaks = $restTimes;
        $correctionRequest->remarks = $request->remarks;
        $correctionRequest->status = 'pending';
        $correctionRequest->save();

        return redirect()->route('correction.list')
            ->with('success', '修正申請を送信しました。承認されるまでお待ちください');
    } catch (\Exception $e) {
        return redirect()->route('attendance.show', $attendance->id)
            ->with('error', '処理中にエラーが発生しました: ' . $e->getMessage());
    }
    }

    //申請一覧画面の表示
    public function correctionList()
{
    $user = Auth::user();
    
    if ($user->isAdmin()) {
        // 管理者の場合：全ユーザーの申請を取得
        $pendingRequests = AttendanceCorrectionRequest::where('status', 'pending')
            ->with(['attendance', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        $approvedRequests = AttendanceCorrectionRequest::where('status', 'approved')
            ->with(['attendance', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();
    } else {
        // 一般ユーザーの場合：自分の申請のみ取得
        $pendingRequests = AttendanceCorrectionRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->with('attendance')
            ->orderBy('created_at', 'desc')
            ->get();

        $approvedRequests = AttendanceCorrectionRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->with('attendance')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    return view('request_list', compact('pendingRequests', 'approvedRequests'));
}
    
    
}