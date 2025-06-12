@extends('layouts.app')

@section('title', '勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/correction_approval.css') }}">
@endsection

@section('content')
<div class="approval__content">
    <h1 class="approval__heading">勤怠詳細</h1>
    
    <div class="approval__card">
        <!-- 名前 -->
        <div class="approval__row">
            <div class="approval__label">名前</div>
            <div class="approval__value">{{ $attendance_correct_request->user->name }}</div>
        </div>
        
        <!-- 日付 -->
        <div class="approval__row">
            <div class="approval__label">日付</div>
            <div class="approval__value">{{ \Carbon\Carbon::parse($attendance_correct_request->attendance->date)->format('Y年n月j日') }}</div>
        </div>
        
        <!-- 出勤・退勤 -->
        <div class="approval__row">
            <div class="approval__label">出勤・退勤</div>
            <div class="approval__value">
                {{ \Carbon\Carbon::parse($attendance_correct_request->requested_start_time)->format('H:i') }}
                <span class="approval__separator">～</span>
                {{ \Carbon\Carbon::parse($attendance_correct_request->requested_end_time)->format('H:i') }}
            </div>
        </div>
        
        <!-- 休憩 -->
        @if($attendance_correct_request->requested_breaks)
            @foreach($attendance_correct_request->requested_breaks as $index => $break)
            <div class="approval__row">
                <div class="approval__label">
                    @if($index === 0)
                        休憩
                    @else
                        休憩{{ $index + 1 }}
                    @endif
                </div>
                <div class="approval__value">
                    {{ \Carbon\Carbon::parse($break['start'])->format('H:i') }}
                    <span class="approval__separator">～</span>
                    {{ \Carbon\Carbon::parse($break['end'])->format('H:i') }}
                </div>
            </div>
            @endforeach
        @else
            <div class="approval__row">
                <div class="approval__label">休憩</div>
                <div class="approval__value">-</div>
            </div>
        @endif
        
        <!-- 備考 -->
        <div class="approval__row">
            <div class="approval__label">備考</div>
            <div class="approval__value">{{ $attendance_correct_request->remarks ?? '-' }}</div>
        </div>
    </div>
    
    <!-- 承認ボタン -->
    <div class="approval__button-container">
        @if($attendance_correct_request->status === 'pending')
            <form method="POST" action="{{ route('stamp_correction_request.approve.submit', $attendance_correct_request) }}">
                @csrf
                <button type="submit" class="approval__button approval__button--pending">承認</button>
            </form>
        @else
            <div class="approval__button approval__button--approved">承認済み</div>
        @endif
    </div>
</div>
@endsection