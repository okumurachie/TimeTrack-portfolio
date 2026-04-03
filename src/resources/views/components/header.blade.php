<header class="header">
    <div class="header__logo">
        <a href="{{ route('attendance.index') }}">
            <p class="header__logo-text">Time track</p>
        </a>
    </div>
    <nav class="header__nav">
        <ul>
            @if(Auth::check() && Auth::user()->hasVerifiedEmail())
            <li><a href="{{ route('attendance.index') }}">勤怠</a></li>
            <li><a href="{{ route('my-record.list') }}">勤怠一覧</a></li>
            <li><a href="{{ route('user.correction.list')}}">申請</a></li>
            <li>
                <form action="/logout" class="logout" method="post">
                    @csrf
                    <button class="header__logout">ログアウト</button>
                </form>
            </li>
        </ul>
    </nav>
    @endif
</header>
