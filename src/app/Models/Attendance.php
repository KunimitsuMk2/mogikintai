<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'remarks',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * ユーザーとのリレーション
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 休憩時間とのリレーション
     */
    public function restTimes()
    {
        return $this->hasMany(RestTime::class);
    }

    /**
     * その日の最新の未終了の休憩を取得
     */
    public function getActiveRestTimeAttribute()
    {
        return $this->restTimes()->whereNull('end_time')->latest()->first();
    }

    /**
    * 実働時間を計算（秒数）- 分単位で丸めて計算
    */
    public function getWorkingTimeAttribute()
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }
    
        // 開始時刻と終了時刻を分単位に丸める
        $startTime = $this->start_time->copy()->second(0)->microsecond(0);
        $endTime = $this->end_time->copy()->second(0)->microsecond(0);
    
        // 分単位での総勤務時間を計算
        $totalMinutes = $endTime->diffInMinutes($startTime);
    
        // 休憩時間も分単位で計算
        $restMinutes = floor($this->total_rest_time / 60);
    
        // 実働時間（分）を秒に変換して返す
        $workingMinutes = $totalMinutes - $restMinutes;
        return $workingMinutes * 60;
    }

    /**
    * 合計休憩時間を計算（秒数）- 分単位で丸めて計算
    */
    public function getTotalRestTimeAttribute()
    {
        $totalMinutes = 0;
    
        foreach ($this->restTimes as $restTime) {
            if ($restTime->start_time && $restTime->end_time) {
                // 休憩開始・終了時刻を分単位に丸める
                $restStart = $restTime->start_time->copy()->second(0)->microsecond(0);
                $restEnd = $restTime->end_time->copy()->second(0)->microsecond(0);
            
                // 分単位で計算
                $totalMinutes += $restEnd->diffInMinutes($restStart);
            }
        }
    
        // 秒数に変換して返す（表示用）
        return $totalMinutes * 60;
    }
}