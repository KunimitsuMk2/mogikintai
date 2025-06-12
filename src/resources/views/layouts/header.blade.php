<header class="header">
    <div class="header__container">
        <!-- ロゴ部分（常に表示） -->
        <div class="header__logo">
            <a href="">
                <img src="/images/logo.png" alt="COACHTECH" class="header__logo-image">
            </a>
        </div>

        <!-- 認証済みの場合のみナビゲーションメニューを表示 -->
        @auth
            @if(Auth::user()->isAdmin())
                <!-- 管理者用ナビゲーション -->
                <nav class="header__nav">
                    <a href="{{ route('admin.attendance.list') }}" class="header__nav-link">勤怠一覧</a>
                    <a href="{{ route('admin.staff.list') }}" class="header__nav-link">スタッフ一覧</a>
                    <a href="{{ route('correction.list') }}" class="header__nav-link">申請一覧</a>
                    
                    <form method="POST" action="{{ route('admin.logout') }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="header__nav-link" style="background: none; border: none; cursor: pointer; padding: 0;">ログアウト</button>
                    </form>
                </nav>
            @else
                <!-- 一般ユーザー用ナビゲーション -->
                <nav class="header__nav">
                    <a href="{{ route('attendance.index') }}" class="header__nav-link">勤怠</a>
                    <a href="{{ route('attendance.list') }}" class="header__nav-link">勤怠一覧</a>
                    <a href="{{ route('correction.list') }}" class="header__nav-link">申請</a>
                    
                    <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="header__nav-link" style="background: none; border: none; cursor: pointer; padding: 0;">ログアウト</button>
                    </form>
                </nav>
            @endif
        @endauth
    </div>
</header>