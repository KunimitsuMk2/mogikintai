@extends('layouts.app')

@section('title', '勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/list.css') }}">
@endsection

@section('content')
<div class="attendance-list__content">
    <h1 class="attendance-list__heading">勤怠一覧</h1>
    
    <div class="attendance-list__month-nav">
        <a href="{{ route('attendance.list', ['month' => $previousMonth]) }}" class="attendance-list__month-button">←前月</a>
        <div class="attendance-list__current-month">
            <span class="attendance-list__calendar-icon">📅</span> <!-- カレンダーアイコン -->
            {{ date('Y/m', strtotime($month)) }}
        </div>
        <a href="{{ route('attendance.list', ['month' => $nextMonth]) }}" class="attendance-list__month-button">翌月→</a>
    </div>
    
    <div class="attendance-list__table-container">
        <table class="attendance-list__table">
            <thead>
                <tr>
                    <th class="attendance-list__th">日付</th>
                    <th class="attendance-list__th">出勤</th>
                    <th class="attendance-list__th">退勤</th>
                    <th class="attendance-list__th">休憩</th>
                    <th class="attendance-list__th">合計</th>   
                    <th class="attendance-list__th">詳細</th>    
                </tr>
            </thead>
            <tbody>
                @foreach($allDays as $day)
            <tr>
                <td class="attendance-list__td">
                    {{ $day['date']->format('m/d') }}({{ $day['date']->isoFormat('ddd') }})
                </td>
                <td class="attendance-list__td">
                    {{ $day['attendance'] && $day['attendance']->start_time ? \Carbon\Carbon::parse($day['attendance']->start_time)->format('H:i') : '' }}
                </td>
                <td class="attendance-list__td">
                    {{ $day['attendance'] && $day['attendance']->end_time ? \Carbon\Carbon::parse($day['attendance']->end_time)->format('H:i') : '' }}
                </td>
                <td class="attendance-list__td">
                    {{ $day['attendance'] && $day['attendance']->total_rest_time ? gmdate('H:i', $day['attendance']->total_rest_time) : '' }}
                </td>
                <td class="attendance-list__td">
                    {{ $day['attendance'] && $day['attendance']->working_time ? gmdate('H:i', $day['attendance']->working_time) : '' }}
                </td>
                <td class="attendance-list__td">
                    @if($day['attendance'])
                        <a href="{{ route('attendance.show', $day['attendance']->id) }}" class="attendance-list__detail-button">詳細</a>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection