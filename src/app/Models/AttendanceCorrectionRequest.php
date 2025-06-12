<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrectionRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'attendance_id',
        'user_id',
        'requested_start_time',
        'requested_end_time',
        'requested_breaks',
        'remarks',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'requested_start_time' => 'datetime',
        'requested_end_time' => 'datetime',
        'requested_breaks' => 'json',
    ];

    /**
     * 関連する勤怠データ
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * 申請を行ったユーザー
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}