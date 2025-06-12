@extends('layouts.app')

@section('title', '勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance_list.css') }}">
@endsection

@section('content')
<div class="admin-attendance-list__content">
    <h1 class="admin-attendance-list__heading">{{ \Carbon\Carbon::parse($date)->format('Y年n月j日') }}の勤怠</h1>
    
    <!-- 日付ナビゲーション -->
    <div class="admin-attendance-list__date-nav">
        <a href="{{ route('admin.attendance.list', ['date' => $previousDate]) }}" class="admin-attendance-list__date-button">
            ← 前日
        </a>
        <div class="admin-attendance-list__current-date">
            <span class="admin-attendance-list__calendar-icon">📅</span>
            {{ \Carbon\Carbon::parse($date)->format('Y/m/d') }}
        </div>
        <a href="{{ route('admin.attendance.list', ['date' => $nextDate]) }}" class="admin-attendance-list__date-button">
            翌日 →
        </a>
    </div>
    
    <!-- 勤怠一覧テーブル -->
    <div class="admin-attendance-list__table-container">
        <table class="admin-attendance-list__table">
            <thead>
                <tr>
                    <th class="admin-attendance-list__th">名前</th>
                    <th class="admin-attendance-list__th">出勤</th>
                    <th class="admin-attendance-list__th">退勤</th>
                    <th class="admin-attendance-list__th">休憩</th>
                    <th class="admin-attendance-list__th">合計</th>
                    <th class="admin-attendance-list__th">詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $attendance)
                <tr>
                    <td class="admin-attendance-list__td">{{ $attendance->user->name }}</td>
                    <td class="admin-attendance-list__td">{{ $attendance->start_time ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '' }}</td>
                    <td class="admin-attendance-list__td">{{ $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '' }}</td>
                    <td class="admin-attendance-list__td">{{ $attendance->total_rest_time ? gmdate('H:i', $attendance->total_rest_time) : '' }}</td>
                    <td class="admin-attendance-list__td">{{ $attendance->working_time ? gmdate('H:i', $attendance->working_time) : '' }}</td>
                    <td class="admin-attendance-list__td">
                        <a href="{{ route('attendance.show', $attendance->id) }}" class="admin-attendance-list__detail-button">詳細</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="admin-attendance-list__td admin-attendance-list__td--empty">
                        該当日の勤怠データはありません
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection