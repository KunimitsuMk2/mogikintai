@extends('layouts.app')

@section('title', '申請一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request_list.css') }}">
@endsection

@section('content')
<div class="correction-list__content">
    <h1 class="correction-list__heading">申請一覧</h1>
    
    <!-- タブナビゲーション -->
    <div class="correction-list__tab-nav">
        <button class="correction-list__tab correction-list__tab--active" data-tab="pending">承認待ち</button>
        <button class="correction-list__tab" data-tab="approved">承認済み</button>
    </div>
    
    <!-- 承認待ちのテーブル -->
    <div class="correction-list__table-container correction-list__tab-content correction-list__tab-content--active" id="pending-tab">
        <table class="correction-list__table">
            <thead>
                <tr>
                    <th class="correction-list__th">状態</th>
                    <th class="correction-list__th">名前</th>
                    <th class="correction-list__th">対象日時</th>
                    <th class="correction-list__th">申請理由</th>
                    <th class="correction-list__th">申請日時</th>
                    <th class="correction-list__th">詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pendingRequests as $request)
                <tr>
                    <td class="correction-list__td">承認待ち</td>
                    <td class="correction-list__td">
                        @if(Auth::user()->isAdmin())
                            {{ $request->user->name }}
                        @else
                            {{ Auth::user()->name }}
                        @endif
                    </td>
                    <td class="correction-list__td">{{ \Carbon\Carbon::parse($request->attendance->date)->format('Y/m/d') }}</td>
                    <td class="correction-list__td">{{ $request->remarks }}</td>
                    <td class="correction-list__td">{{ \Carbon\Carbon::parse($request->created_at)->format('Y/m/d') }}</td>
                    <td class="correction-list__td">
                        @if(Auth::user()->isAdmin())
                            <a href="{{ route('stamp_correction_request.approve', $request->id) }}" class="correction-list__detail-button">詳細</a>
                        @else
                            <a href="{{ route('attendance.show', $request->attendance->id) }}" class="correction-list__detail-button">詳細</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="correction-list__td correction-list__td--empty">承認待ちの申請はありません</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- 承認済みのテーブル -->
    <div class="correction-list__table-container correction-list__tab-content" id="approved-tab">
        <table class="correction-list__table">
            <thead>
                <tr>
                    <th class="correction-list__th">状態</th>
                    <th class="correction-list__th">名前</th>
                    <th class="correction-list__th">対象日時</th>
                    <th class="correction-list__th">申請理由</th>
                    <th class="correction-list__th">申請日時</th>
                    <th class="correction-list__th">詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse($approvedRequests as $request)
                <tr>
                    <td class="correction-list__td">承認済み</td>
                    <td class="correction-list__td">
                        @if(Auth::user()->isAdmin())
                            {{ $request->user->name }}
                        @else
                            {{ Auth::user()->name }}
                        @endif
                    </td>
                    <td class="correction-list__td">{{ \Carbon\Carbon::parse($request->attendance->date)->format('Y/m/d') }}</td>
                    <td class="correction-list__td">{{ $request->remarks }}</td>
                    <td class="correction-list__td">{{ \Carbon\Carbon::parse($request->created_at)->format('Y/m/d') }}</td>
                    <td class="correction-list__td">
                        @if(Auth::user()->isAdmin())
                            <a href="{{ route('stamp_correction_request.approve', $request->id) }}" class="correction-list__detail-button">詳細</a>
                        @else
                            <a href="{{ route('attendance.show', $request->attendance->id) }}" class="correction-list__detail-button">詳細</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="correction-list__td correction-list__td--empty">承認済みの申請はありません</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
// タブの切り替え機能
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.correction-list__tab');
    const tabContents = document.querySelectorAll('.correction-list__tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // すべてのタブからactiveクラスを削除
            tabs.forEach(t => t.classList.remove('correction-list__tab--active'));
            tabContents.forEach(tc => tc.classList.remove('correction-list__tab-content--active'));
            
            // クリックされたタブにactiveクラスを追加
            this.classList.add('correction-list__tab--active');
            document.getElementById(targetTab + '-tab').classList.add('correction-list__tab-content--active');
        });
    });
});
</script>
@endsection