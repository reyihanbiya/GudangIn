@if (auth()->guard('employee')->check())
    @php($user = auth()->guard('employee')->user())
    <div class="topbar-user">
        <span>{{ $user->nama_pegawai }} <small>Pegawai</small></span>
        <form method="POST" action="{{ route('employee.logout') }}">
            @csrf
            <button class="button button-quiet" type="submit">Keluar</button>
        </form>
    </div>
@elseif (auth()->guard('owner')->check())
    @php($user = auth()->guard('owner')->user())
    <div class="topbar-user">
        <span>{{ $user->nama_owner }} <small>Owner</small></span>
        <form method="POST" action="{{ route('owner.logout') }}">
            @csrf
            <button class="button button-quiet" type="submit">Keluar</button>
        </form>
    </div>
@endif
