@extends('layouts.app')

@section('title', '勤怠登録')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="attendance__content">
    <div class="attendance__status-badge">
        {{ $attendance->status }}
    </div>

    <div class="attendance__datetime">
        <p class="attendance__date" id="current-date">{{ $date }}</p>
        <p class="attendance__time" id="current-time">{{ $time }}</p>
    </div>

    <div class="attendance__actions">
        @if ($attendance->status === '勤務外')
            <form action="{{ route('attendance.start') }}" method="POST" class="attendance__form">
                @csrf
                <button type="submit" class="attendance__button attendance__button--primary">出勤</button>
            </form>
        @elseif ($attendance->status === '出勤中')
            <div class="attendance__buttons-group">
                <form action="{{ route('attendance.end') }}" method="POST" class="attendance__form">
                    @csrf
                    <button type="submit" class="attendance__button attendance__button--primary">退勤</button>
                </form>
                <form action="{{ route('attendance.break-start') }}" method="POST" class="attendance__form">
                    @csrf
                    <button type="submit" class="attendance__button attendance__button--secondary">休憩入</button>
                </form>
            </div>
        @elseif ($attendance->status === '休憩中')
            <form action="{{ route('attendance.break-end') }}" method="POST" class="attendance__form">
                @csrf
                <button type="submit" class="attendance__button attendance__button--secondary">休憩戻</button>
            </form>
        @elseif ($attendance->status === '退勤済')
            <p class="attendance__message">お疲れ様でした。</p>
        @endif
    </div>
</div>
@endsection

@section('js')
<script>
    // リアルタイム時計の更新
    function updateClock() {
        const now = new Date();
        
        // 曜日の配列
        const weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        
        // 日付フォーマット: YYYY年MM月DD日(曜)
        const dateString = now.getFullYear() + '年' + 
                          (now.getMonth() + 1).toString().padStart(2, '0') + '月' + 
                          now.getDate().toString().padStart(2, '0') + '日(' + 
                          weekdays[now.getDay()] + ')';
        
        // 時刻フォーマット: HH:MM
        const timeString = now.getHours().toString().padStart(2, '0') + ':' + 
                           now.getMinutes().toString().padStart(2, '0');
        
        // 画面に表示を更新
        document.getElementById('current-date').textContent = dateString;
        document.getElementById('current-time').textContent = timeString;
    }
    
    // 1秒ごとに時計を更新
    setInterval(updateClock, 1000);
    
    // ページ読み込み時にも更新
    updateClock();
</script>
@endsection