<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\RestTime;
use App\Models\User;
use App\Models\AttendanceCorrectionRequest;
use Carbon\Carbon;
use App\Http\Requests\AttendanceCorrectionFormRequest;
use Illuminate\Http\Request;

class AdminAttendanceController extends Controller
{

    //管理者用勤怠一覧画面の表示
    public function list(Request $request)
    {
        //クエリから日付を取得
        $date = $request->query('date',Carbon::today()->format('Y-m-d'));

        //前日と翌日の計算
        $previousDate = Carbon::parse($date)->subDay()->format('Y-m-d');
        $nextDate = Carbon::parse($date)->addDay()->format('Y-m-d');

        //指定日の全ユーザーの勤怠データを取得
        $attendances = Attendance::with(['user','restTimes'])
            ->whereDate('date',$date)
            ->get();

            return view('admin.attendance_list',compact('attendances','date','previousDate','nextDate'));

    }

    //スタッフ一覧画面の表示
    public function staffList()
    {
        //role='user'の一般ユーザーのみを取得
        $staffUsers = User::where('role','user')
            ->orderBy('name','asc')
            ->get();
            
            return view('admin.staff_list',compact('staffUsers'));
    }

    //スタッフ別勤怠一覧画面の表示

    public function staffAttendance(Request $request,User $user)
    {
        //クエリパラメータから月を取得
        $month =$request->query('month',Carbon::now()->format('Y-m'));

        //文字列形式の年月をCarbonオブジェクトに変換
        $date = Carbon::createFromFormat('Y-m',$month);

        //前月と翌月を計算
        $previousMonth = Carbon::createFromFormat('Y-m',$month)->subMonth()->format('Y-m');
        $nextMonth =Carbon::createFromFormat('Y-m',$month)->addMonth()->format('Y-m');

        //指定したユーザーの指定月の勤怠データを取得
        $attendances = Attendance::where('user_id',$user->id)
            ->whereYear('date',$date->year)
            ->whereMonth('date',$date->month)
            ->with('restTimes')
            ->get()
            ->keyBy(function($item){
                return Carbon::parse($item->date)->format('Y-m-d');
            });

            


            //その月の全日程を生成
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();
            $allDays = [];

            for($day = $startOfMonth->copy(); $day <= $endOfMonth; $day->addDay()){
                $dateKey = $day->format('Y-m-d');
                $allDays[] = [
                    'date' => $day->copy(),
                    'attendance' =>$attendances->get($dateKey)
                ];
            }

            return view('admin.staff_attendance',compact('user','allDays','month','previousMonth','nextMonth'));
    }

    //管理者による勤怠データの直接更新

    public function updateAttendance(AttendanceCorrectionFormRequest $request,Attendance $attendance)
    {
        try{
            $date = Carbon::parse($attendance->date)->format('Y-m-d');
            $startDateTime = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $request->start_time);
            $endDateTime = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $request->end_time);

            //勤怠データを直接更新
            $attendance->update([
                'start_time'=>$startDateTime,
                'end_time'=>$endDateTime,
                'remarks'=>$request->remarks,
            ]);

            //休憩時間の更新処理
            //既存の休憩データを削除
             RestTime::where('attendance_id', $attendance->id)->delete();

            //新しい休憩データの作成
            if($request ->filled('rest_times')){
                foreach($request->rest_times as $restTime){
                    if(!empty($restTime['start']) && !empty($restTime['end'])){
                        $restStartDateTime = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $restTime['start']);
                        $restEndDateTime = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $restTime['end']);

                        RestTime::create([
                            'attendance_id'=> $attendance->id,
                            'start_time'=> $restStartDateTime,
                            'end_time'=> $restEndDateTime,
                        ]);
                    }
                }
            }

            $currentMonth = Carbon::parse($attendance->date)->format('Y-m');

            return redirect()->route('admin.attendance.staff',[
                        'user' => $attendance->user_id,
                        'month' => $currentMonth])
                    ->with('success','勤怠データを更新しました');
        } catch (\Exception $e){
            return redirect()->route('attendance.show',[
            'user' => $attendance->user_id,
            'month' => $currentMonth])
                ->with('error','処理中にエラーが発生しました：' . $e->getMessage());
        }

    }
    
    // 修正申請承認画面の表示 
public function showApprovalForm(AttendanceCorrectionRequest $attendance_correct_request)
{
    // 詳細画面の内容が、動線上で自分が選択した情報と一致していること
    // 詳細画面の内容が、正しく実際の打刻内容が反映されていること
    $attendance_correct_request->load(['attendance', 'user']);
    
    return view('admin.correction_approval', compact('attendance_correct_request'));
}

// 修正申請の承認処理 
public function approveRequest(AttendanceCorrectionRequest $attendance_correct_request)
{
    try {
        // 1. 管理者ユーザーの当該勤怠情報が更新され、修正申請の内容と一致すること
        $attendance = $attendance_correct_request->attendance;
        
        $attendance->update([
            'start_time' => $attendance_correct_request->requested_start_time,
            'end_time' => $attendance_correct_request->requested_end_time,
            'remarks' => $attendance_correct_request->remarks,
        ]);

        // 休憩時間の更新処理
        RestTime::where('attendance_id', $attendance->id)->delete();
        
        if (!empty($attendance_correct_request->requested_breaks)) {
            foreach ($attendance_correct_request->requested_breaks as $break) {
                RestTime::create([
                    'attendance_id' => $attendance->id,
                    'start_time' => $break['start'],
                    'end_time' => $break['end'],
                ]);
            }
        }

        // 2. 管理者ユーザーの「修正申請一覧」で、"承認待ち"から"承認済み"に変更されていること
        // 4. 一般ユーザーの「修正申請一覧」で、"承認待ち"から"承認済み"に変更されていること
        $attendance_correct_request->update(['status' => 'approved']);
        
        // 3. 一般ユーザーの当該勤怠情報が更新され、修正申請の内容と一致すること
        // (上記で既に更新済み - 同じ勤怠データを参照)
        
        return redirect()->route('correction.list')
            ->with('success', '申請を承認しました');
            
    } catch (\Exception $e) {
        return redirect()->back()
            ->with('error', '承認処理中にエラーが発生しました: ' . $e->getMessage());
    }
}
    //スタッフ別勤怠データのCSV出力

    public function exportCsv(Request $request,User $user)
    {
        $month = $request->query('month',Carbon::now()->format('Y-m'));
        $date = Carbon::createFromFormat('Y-m',$month);

        //勤怠データ取得
        $attendances = Attendance::where('user_id',$user->id)
            ->whereYear('date',$date->year)
            ->whereMonth('date',$date->month)
            ->with('restTimes')
            ->orderBy('date','asc')
            ->get()
            ->keyBy('date');

        //CSVデータの作成
        $csvData= [];
        $csvData[]=['日付','出勤','退勤','休憩','合計'];

       $startOfMonth = $date->copy()->startOfMonth();
       $endOfMonth = $date->copy()->endOfMonth();

       for ($day = $startOfMonth->copy(); $day <= $endOfMonth; $day->addDay()) {
            $dateKey = $day->format('Y-m-d');
            $attendance = $attendances->get($dateKey);
    
            $csvData[] = [
             $day->format('Y/m/d'),
                $attendance && $attendance->start_time ? Carbon::parse($attendance->start_time)->format('H:i') : '',
                $attendance && $attendance->end_time ? Carbon::parse($attendance->end_time)->format('H:i') : '',
                $attendance && $attendance->total_rest_time ? gmdate('H:i', $attendance->total_rest_time) : '',
                $attendance && $attendance->working_time ? gmdate('H:i', $attendance->working_time) : '',
            ];
        }
        //CSV出力
        $filename = $user->name . '_' . $date->format('Y年m月') . '_勤怠データ.csv';

        $callback = function()use($csvData){
            $file= fopen('php://output', 'w');

            //BOM追加（エクセル対応
            fputs($file, "\xEF\xBB\xBF");

            foreach($csvData as $row){
                fputcsv($file,$row);
            }
            fclose($file);
        };

        return response()->stream($callback,200,[
            'Content-Type'=>'text/csv',
             'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
            


    }
}
