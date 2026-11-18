@extends('layouts.public')

@php
    // Sisa waktu tunggu diambil dari header Retry-After yang dipasang middleware
    // throttle, supaya warga tahu persis berapa lama harus menunggu.
    $tunggu = null;

    if (isset($exception) && method_exists($exception, 'getHeaders')) {
        $retry = $exception->getHeaders()['Retry-After'] ?? null;
        $tunggu = is_numeric($retry) ? max(1, (int) $retry) : null;
    }
@endphp

@section('title', 'Terlalu banyak permintaan · '.$profil->nama_desa)
@section('description', 'Permintaan Anda ke layanan surat desa terlalu sering. Tunggu sebentar lalu coba lagi.')

@section('content')

  <section class="page-hero page-hero-soft">
    <div class="container">
      <nav class="breadcrumb">
        <a href="{{ route('beranda') }}">Beranda</a>
        <span class="sep">/</span>
        <span>Terlalu banyak permintaan</span>
      </nav>
      <div class="page-hero-row">
        <div>
          <div class="eyebrow eyebrow-plain" style="color:var(--blue-600)">Mohon tunggu sebentar</div>
          <h1>Permintaan Anda terlalu sering</h1>
          <p class="lead">
            Demi keamanan data warga, layanan ini membatasi jumlah permintaan.
            Halaman yang Anda buka dipanggil terlalu sering dalam waktu singkat.
          </p>
        </div>
      </div>
    </div>
  </section>

  <section class="section form-page">
    <div class="container">
      <div class="form-layout" style="grid-template-columns:1fr">
        <div>
          <div class="form-card">
            <div class="form-card-head">
              <span class="form-step-num sq">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
              </span>
              <div>
                <div style="font-weight:700;font-size:1.0625rem">Apa yang perlu dilakukan?</div>
                <div class="sub">Data Anda tidak hilang. Tidak ada yang perlu diajukan ulang.</div>
              </div>
            </div>
            <hr class="form-section-hr">

            <ul class="doc-bullets">
              <li>
                <b>Tunggu {{ $tunggu ?? 'beberapa saat' }}</b>
                @if (! empty($tunggu))
                  detik
                @endif
                lalu muat ulang halaman ini.
              </li>
              <li>Jangan menekan tombol kirim berulang kali &mdash; satu kali sudah cukup.</li>
              <li>Bila Anda sedang mengisi formulir, isian yang sudah diketik tetap tersimpan di browser.</li>
            </ul>

            <div class="form-actions" style="margin-top:24px">
              <a href="{{ route('beranda') }}" class="back-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                Kembali ke beranda
              </a>
              <a href="{{ url()->current() }}" class="btn btn-primary">
                Muat ulang halaman
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v6h-6"/></svg>
              </a>
            </div>
          </div>

          <div class="panel panel-flat" style="margin-top:20px">
            <div style="font-weight:700;margin-bottom:8px">Butuh bantuan?</div>
            <p style="font-size:.9rem;color:var(--gray-600);margin-bottom:14px">
              Bila Anda yakin ini bukan kesalahan Anda, hubungi petugas desa dan sebutkan nomor referensi pengajuan Anda.
            </p>
            <a href="{{ route('kontak') }}" class="panel-link">Hubungi kantor desa →</a>
          </div>
        </div>
      </div>
    </div>
  </section>

@endsection
