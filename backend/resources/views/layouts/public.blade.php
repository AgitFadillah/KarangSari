@php
    $profil = \App\Support\SiteProfile::current();
    $logoUrl = $profil->logoUrl();
    $judulHalaman = trim((string) $__env->yieldContent('title')) ?: $profil->nama_desa.' · Layanan Digital Resmi';
    $deskripsiHalaman = trim((string) $__env->yieldContent('description')) ?: 'Website resmi '.$profil->nama_desa.'. Ajukan surat dari rumah, cek status, lihat potensi desa.';

    // URL harus absolut agar WhatsApp dan media sosial mau mengambil gambar
    // pratinjau. Parameter versi berubah saat logo diganti sehingga cache
    // pratinjau lama tidak terus dipakai.
    $gambarSosial = url(route('gambar-sosial'))
        .($logoUrl ? '?v='.substr(md5($logoUrl), 0, 8) : '');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $judulHalaman }}</title>
  <meta name="description" content="{{ $deskripsiHalaman }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- Pratinjau tautan di WhatsApp/media sosial: logo desa, bukan ikon abu-abu. --}}
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="{{ $profil->nama_desa }}">
  <meta property="og:title" content="{{ $judulHalaman }}">
  <meta property="og:description" content="{{ $deskripsiHalaman }}">
  <meta property="og:url" content="{{ url()->current() }}">
  <meta property="og:image" content="{{ $gambarSosial }}">
  <meta property="og:image:secure_url" content="{{ $gambarSosial }}">
  <meta property="og:image:type" content="image/png">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="Logo {{ $profil->nama_desa }}">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="{{ $judulHalaman }}">
  <meta name="twitter:description" content="{{ $deskripsiHalaman }}">
  <meta name="twitter:image" content="{{ $gambarSosial }}">

  @if ($logoUrl)
    <link rel="icon" type="image/png" href="{{ url($logoUrl) }}">
    <link rel="apple-touch-icon" href="{{ url($logoUrl) }}">
  @endif

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  @vite('resources/css/site.css')
  @stack('styles')
</head>
<body class="publik">

  @include('layouts.partials.topbar')

  @yield('content')

  @include('layouts.partials.footer')

  @vite('resources/js/site.js')
  @stack('scripts')
</body>
</html>
