@props([
    'trail' => [],
    'title' => 'Dashboard',
    'action' => null,
])

@php
    $pengguna = auth()->user();
    // Menu ini boleh dibuka siapa saja yang sudah login (Pengaturan akun,
    // Lihat website, Keluar) — tidak perlu hak kelola konten.
@endphp

<header class="admin-topbar">
  <button class="admin-menu-btn" id="adminMenuBtn" aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="adminSidebar">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
  </button>
  <div>
    <div class="breadcrumb">
      @foreach ($trail as $i => $item)
        @if ($i > 0)
          <span class="sep">/</span>
        @endif
        @if (! empty($item['route']) && ! $loop->last)
          <a href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
        @else
          <span>{{ $item['label'] }}</span>
        @endif
      @endforeach
    </div>
    <h1>{{ $title }}</h1>
  </div>
  @if ($pengguna->role->bolehVerifikasiPengajuan())
    <form class="search" action="{{ route('admin.pengajuan.index') }}" method="get" role="search">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari pengajuan..." aria-label="Cari pengajuan">
    </form>
    <a href="{{ route('admin.pengajuan.index', ['status' => 'menunggu_verifikasi']) }}" class="admin-bell" aria-label="{{ $pendingCount ?? 0 }} pengajuan menunggu tindakan">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
      @if (($pendingCount ?? 0) > 0)
        <span class="admin-bell-dot"></span>
      @endif
    </a>
  @endif

  {{-- Menu pengguna: dropdown berisi identitas, pengaturan akun, dan keluar. --}}
  <div class="admin-user-menu" id="adminUserMenu">
    <button type="button" class="admin-user-chip" id="adminUserBtn"
            aria-expanded="false" aria-haspopup="true" aria-controls="adminUserPanel">
      <div class="avatar">{{ $pengguna->initials }}</div>
      <div>
        <div class="name">{{ Str::limit($pengguna->name, 12) }}</div>
        <div class="role">{{ $pengguna->role->shortLabel() }}</div>
      </div>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="color:var(--gray-500)" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
    </button>

    <div class="admin-user-panel" id="adminUserPanel" role="menu" aria-labelledby="adminUserBtn" hidden>
      <div class="admin-user-panel-head">
        <div class="avatar">{{ $pengguna->initials }}</div>
        <div>
          <div class="nama">{{ $pengguna->name }}</div>
          <div class="peran">{{ $pengguna->role->label() }}</div>
        </div>
      </div>
      <div class="admin-user-panel-meta">
        <span class="label">Masuk sebagai</span>
        <span class="nilai">{{ $pengguna->username }}</span>
      </div>
      <div class="admin-user-panel-actions">
        @if ($pengguna->role->bolehKelolaAkun())
        <a href="{{ route('admin.akun.index') }}" role="menuitem">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1A1.7 1.7 0 0 0 8.9 19a1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1A1.7 1.7 0 0 0 5 8.9a1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg>
          Pengaturan akun
        </a>
        @endif
        <a href="{{ route('beranda') }}" target="_blank" rel="noopener" role="menuitem">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14L21 3"/></svg>
          Lihat website
        </a>
        <form method="POST" action="{{ route('admin.logout') }}" role="none">
          @csrf
          <button type="submit" role="menuitem" class="danger">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
            Keluar
          </button>
        </form>
      </div>
    </div>
  </div>
</header>
