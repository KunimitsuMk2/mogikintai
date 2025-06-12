@extends('layouts.app')

@section('title', '勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/show.css') }}">
@endsection

@section('content')
<div class="attendance-detail__content">
    <h1 class="attendance-detail__heading">勤怠詳細</h1>
    
    @if (session('error'))
    <div class="attendance-detail__error">
        {{ session('error') }}
    </div>
    @endif
    
    <!-- 申請状況チェック -->
    @php
        $pendingRequest = \App\Models\AttendanceCorrectionRequest::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->first();
        $approvedRequest = \App\Models\AttendanceCorrectionRequest::where('attendance_id', $attendance->id)
            ->where('status', 'approved')
            ->first();
    @endphp
    
   
    @if($isAdmin)
    <form action="{{ route('admin.attendance.update', $attendance->id) }}" method="POST" class="attendance-detail__form">
        @else
        <form action="{{ route('attendance.update', $attendance->id) }}" method="POST" class="attendance-detail__form">
        @endif
        @csrf
        
        <div class="attendance-detail__container">

    {{-- 出勤・退勤時間の入力欄 --}}
    <div class="attendance-detail__row">
        <div class="attendance-detail__label">出勤・退勤</div>
        <div class="attendance-detail__value attendance-detail__time-range">
            <div class="attendance-detail__time-field">
                <input type="text" class="time-input" name="start_time" placeholder="00:00" maxlength="5" 
                    value="{{ $displayData['start_time'] ? \Carbon\Carbon::parse($displayData['start_time'])->format('H:i') : '' }}" 
                    {{ ($pendingRequest || $approvedRequest) && !$isAdmin ? 'disabled' : '' }}>
                @error('start_time')
                <div class="attendance-detail__error">{{ $message }}</div>
                @enderror
            </div>
            <span class="attendance-detail__time-separator">〜</span>
            <div class="attendance-detail__time-field">
                <input type="text" class="time-input" name="end_time" placeholder="00:00" maxlength="5" 
                    value="{{ $displayData['end_time'] ? \Carbon\Carbon::parse($displayData['end_time'])->format('H:i') : '' }}" 
                    {{ ($pendingRequest || $approvedRequest) && !$isAdmin ? 'disabled' : '' }}>
                @error('end_time')
                <div class="attendance-detail__error">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- 休憩時間の入力欄 --}}
    <div class="attendance-detail__row">
        <div class="attendance-detail__label">休憩</div>
        <div class="attendance-detail__value">
            @if($displayData['rest_times']->count() > 0)
                @foreach($displayData['rest_times'] as $index => $restTime)
            <div class="attendance-detail__time-field">
                <input type="text" class="time-input" name="rest_times[{{ $index }}][start]" placeholder="00:00" maxlength="5" 
                    value="{{ $restTime->start_time ? \Carbon\Carbon::parse($restTime->start_time)->format('H:i') : '' }}" 
                        {{ ($pendingRequest || $approvedRequest) && !$isAdmin ? 'disabled' : '' }}>
            </div>
            <span class="attendance-detail__time-separator">〜</span>
            <div class="attendance-detail__time-field">
                <input type="text" class="time-input" name="rest_times[{{ $index }}][end]" placeholder="00:00" maxlength="5" 
                        value="{{ $restTime->end_time ? \Carbon\Carbon::parse($restTime->end_time)->format('H:i') : '' }}" 
                        {{ ($pendingRequest || $approvedRequest) && !$isAdmin ? 'disabled' : '' }}>
            </div>
                @endforeach
            @else
                <div class="attendance-detail__time-range">
                    <div class="attendance-detail__time-field">
                        <input type="text" name="rest_times[0][start]" value="" {{ ($pendingRequest || $approvedRequest) && !$isAdmin ? 'disabled' : '' }}>
                    </div>
                    <span class="attendance-detail__time-separator">〜</span>
                    <div class="attendance-detail__time-field">
                        <input type="text" name="rest_times[0][end]" value="" {{ ($pendingRequest || $approvedRequest) && !$isAdmin ? 'disabled' : '' }}>
                    </div>
                </div>
            @endif
        
            {{-- 休憩追加フィールド --}}
            @if(!$pendingRequest && !$approvedRequest)
            <div class="attendance-detail__time-range attendance-detail__rest-additional">
                <div class="attendance-detail__time-field">
                    <input type="text" name="rest_times[{{ $displayData['rest_times']->count() > 0 ? $displayData['rest_times']->count() : 1 }}][start]" value="">
                </div>
                <span class="attendance-detail__time-separator">〜</span>
                <div class="attendance-detail__time-field">
                    <input type="text" name="rest_times[{{ $displayData['rest_times']->count() > 0 ? $displayData['rest_times']->count() : 1 }}][end]" value="">
                </div>
            </div>
            @endif
        
            @error('rest_times.*')
            <div class="attendance-detail__error">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- 備考欄 --}}
    <div class="attendance-detail__row">
        <div class="attendance-detail__label">備考</div>
        <div class="attendance-detail__value">
            <textarea class="attendance-detail__textarea" name="remarks" {{ ($pendingRequest || $approvedRequest) && !$isAdmin ? 'disabled' : '' }}>{{ $displayData['remarks'] }}</textarea>
            @error('remarks')
            <div class="attendance-detail__error">{{ $message }}</div>
            @enderror
        </div>
    </div>

            
            <!-- ボタン表示制御 -->
            <div class="attendance-detail__button-container">
                @if($pendingRequest && !$isAdmin)
                    <!-- 承認待ちの場合 -->
                    <div class="attendance-detail__pending-message">
                        承認待ちのため修正はできません。
                    </div>
                @elseif($approvedRequest && !$isAdmin)
                    <!-- 承認済みの場合 -->
                    <div class="attendance-detail__approved-button">承認済み</div>
                @elseif(!$pendingRequest && !$approvedRequest || $isAdmin)
                    <!-- 修正可能な場合 -->
                    <button type="submit" class="attendance-detail__button">修正</button>
                @endif
            </div>
        </div>
    </form>
    
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // すべての時間入力フィールドにイベントリスナーを追加
    const timeInputs = document.querySelectorAll('.time-input');
    
    timeInputs.forEach(function(input) {
        // 入力時のイベント処理
        input.addEventListener('input', function(e) {
            let value = e.target.value;
            
            // 数字のみに制限（コロンは後で自動挿入）
            value = value.replace(/[^\d]/g, '');
            
            // 4桁以下なら整形
            if (value.length <= 4) {
                // 桁数に応じてフォーマット
                if (value.length <= 2) {
                    // 2桁以下ならそのまま表示
                    e.target.value = value;
                } else {
                    // 3桁または4桁なら時:分に整形
                    const hours = value.substring(0, 2);
                    const minutes = value.substring(2);
                    e.target.value = hours + ':' + minutes;
                }
            }
        });
        
        // フォーカスを失った時の処理 - 入力形式の自動修正
        input.addEventListener('blur', function(e) {
            const value = e.target.value;
            
            // 空の場合は何もしない
            if (!value) {
                return;
            }
            
            // 時間形式のパターン（HH:MM）
            const timePattern = /^([01]?[0-9]|2[0-3]):([0-5][0-9])$/;
            
            // 正しい形式でない場合は自動修正を試みる
            if (!timePattern.test(value)) {
                // HH:MM 形式に変換
                if (value.length === 1 || value.length === 2) {
                    // 時間のみ入力された場合
                    const hours = parseInt(value, 10);
                    if (hours >= 0 && hours <= 23) {
                        e.target.value = (hours < 10 ? '0' + hours : hours) + ':00';
                    }
                } else if (value.indexOf(':') === -1 && value.length >= 3) {
                    // コロンがない場合で3桁以上
                    const hours = parseInt(value.substring(0, 2), 10);
                    let minutes = value.substring(2);
                    if (minutes.length === 1) minutes += '0';
                    
                    // 時間と分が有効範囲内かチェック
                    if (hours >= 0 && hours <= 23 && parseInt(minutes, 10) >= 0 && parseInt(minutes, 10) <= 59) {
                        e.target.value = (hours < 10 ? '0' + hours : hours) + ':' + minutes;
                    }
                } else if (value.indexOf(':') !== -1) {
                    // コロンがある場合
                    const parts = value.split(':');
                    const hours = parseInt(parts[0], 10);
                    let minutes = parts.length > 1 ? parts[1] : '00';
                    
                    if (hours >= 0 && hours <= 23) {
                        if (minutes.length === 1) minutes += '0';
                        if (minutes.length > 2) minutes = minutes.substring(0, 2);
                        
                        if (parseInt(minutes, 10) >= 0 && parseInt(minutes, 10) <= 59) {
                            e.target.value = (hours < 10 ? '0' + hours : hours) + ':' + minutes;
                        }
                    }
                }
            }
        });
    });
    
    // フォーム送信時の基本的な検証
    const form = document.querySelector('.attendance-detail__form');
    if (form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const startTimeInput = form.querySelector('input[name="start_time"]');
            const endTimeInput = form.querySelector('input[name="end_time"]');
            const remarksInput = form.querySelector('textarea[name="remarks"]');
            
            // すべての時間入力をシンプルに検証
            timeInputs.forEach(function(input) {
                const value = input.value;
                
                // 空欄はOK
                if (!value) return;
                
                // 時間形式のパターン（HH:MM）
                const timePattern = /^([01]?[0-9]|2[0-3]):([0-5][0-9])$/;
                
                if (!timePattern.test(value)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('入力内容に誤りがあります。時間は 00:00〜23:59 の形式で入力してください。');
            }
        });
    }
});
</script>
@endsection