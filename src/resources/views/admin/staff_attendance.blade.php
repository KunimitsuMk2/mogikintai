@extends('layouts.app')

@section('title', 'スタッフ別勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff_attendance.css') }}">
@endsection

@section('content')
<div class="admin-staff-attendance__content">
    <h1 class="admin-staff-attendance__heading">{{ $user->name }}さんの勤怠</h1>
    
    <!-- 月ナビゲーション -->
    <div class="admin-staff-attendance__month-nav">
        <a href="{{ route('admin.attendance.staff', ['user' => $user->id, 'month' => $previousMonth]) }}" class="admin-staff-attendance__month-button">
            ← 前月
        </a>
        <div class="admin-staff-attendance__current-month">
            <span class="admin-staff-attendance__calendar-icon">📅</span>
            {{ date('Y/m', strtotime($month . '-01')) }}
        </div>
        <a href="{{ route('admin.attendance.staff', ['user' => $user->id, 'month' => $nextMonth]) }}" class="admin-staff-attendance__month-button">
            翌月 →
        </a>
    </div>
    
    <!-- 勤怠一覧テーブル -->
    <div class="admin-staff-attendance__table-container">
        <table class="admin-staff-attendance__table">
            <thead>
                <tr>
                    <th class="admin-staff-attendance__th">日付</th>
                    <th class="admin-staff-attendance__th">出勤</th>
                    <th class="admin-staff-attendance__th">退勤</th>
                    <th class="admin-staff-attendance__th">休憩</th>
                    <th class="admin-staff-attendance__th">合計</th>
                    <th class="admin-staff-attendance__th">詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allDays as $day)
            <tr>
                <td class="admin-staff-attendance__td">
                    {{ $day['date']->format('m/d') }}({{ $day['date']->isoFormat('ddd') }})
                </td>
                <td class="admin-staff-attendance__td">
                    {{ $day['attendance'] && $day['attendance']->start_time ? \Carbon\Carbon::parse($day['attendance']->start_time)->format('H:i') : '' }}
                </td>
                <td class="admin-staff-attendance__td">
                    {{ $day['attendance'] && $day['attendance']->end_time ? \Carbon\Carbon::parse($day['attendance']->end_time)->format('H:i') : '' }}
                </td>
                <td class="admin-staff-attendance__td">
                    {{ $day['attendance'] && $day['attendance']->total_rest_time ? gmdate('H:i', $day['attendance']->total_rest_time) : '' }}
                </td>
                <td class="admin-staff-attendance__td">
                    {{ $day['attendance'] && $day['attendance']->working_time ? gmdate('H:i', $day['attendance']->working_time) : '' }}
                </td>
                <td class="admin-staff-attendance__td">
                    @if($day['attendance'])
                        <a href="{{ route('attendance.show', $day['attendance']->id) }}" class="admin-staff-attendance__detail-button">詳細</a>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    
    <!-- CSV出力ボタン -->
    <div class="admin-staff-attendance__csv-container">
        <a href="{{ route('admin.attendance.staff.csv', ['user' => $user->id, 'month' => $month]) }}" class="admin-staff-attendance__csv-button">CSV出力</a>

    </div>
</div>
@endsection