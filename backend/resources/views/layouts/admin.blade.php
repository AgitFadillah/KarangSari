@php
    $profil = \App\Support\SiteProfile::current();
    $pendingCount = $pendingCount ?? \App\Models\Pengajuan::query()->where('status', \App\Enums\StatusPengajuan::MenungguVerifikasi->value)->count();
    $activeAdmin = $activeAdmin ?? '';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Dashboard Admin '.$profil->nama_desa)</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="robots" content="noindex, nofollow">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  @vite('resources/css/site.css')
  @stack('styles')
</head>
<body>
  <div class="admin-shell">
    @include('layouts.partials.admin-sidebar')

    <div class="admin-main">
      @yield('topbar')

      @yield('content')

      @include('layouts.partials.admin-footer')
    </div>
  </div>

  @vite('resources/js/site.js')
  @stack('scripts')
</body>
</html>
