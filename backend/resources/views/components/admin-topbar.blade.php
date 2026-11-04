@props([
    'trail' => [],
    'title' => 'Dashboard',
])

@php
    $pending = $pendingCount ?? \App\Models\Pengajuan::query()
        ->where('status', \App\Enums\StatusPengajuan::MenungguVerifikasi->value)
        ->count();
@endphp

<header class="admin-topbar">
  <button class="admin-menu-btn" id="adminMenuBtn" aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="adminSidebar">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
  </button>
  <div>
    <div class="breadcrumb">
      @foreach ($trail as $item)
        @if (! $loop->first)
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
  <form class="search" action="{{ route('admin.pengajuan.index') }}" method="get" role="search">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari pengajuan..." aria-label="Cari pengajuan">
  </form>
  <a href="{{ route('admin.pengajuan.index', ['status' => \App\Enums\StatusPengajuan::MenungguVerifikasi->value]) }}" class="admin-bell" aria-label="{{ $pending }} pengajuan menunggu tindakan">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
    @if ($pending > 0)
      <span class="admin-bell-dot"></span>
    @endif
  </a>
  <form method="POST" action="{{ route('admin.logout') }}" style="margin:0">
    @csrf
    <button type="submit" class="admin-user-chip" style="border:0;background:none;cursor:pointer;font:inherit" title="Keluar">
      <div class="avatar">{{ auth()->user()->initials }}</div>
      <div>
        <div class="name">{{ Str::limit(auth()->user()->name, 12) }}</div>
        <div class="role">{{ auth()->user()->role->shortLabel() }}</div>
      </div>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="color:var(--gray-500)"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
    </button>
  </form>
</header>
