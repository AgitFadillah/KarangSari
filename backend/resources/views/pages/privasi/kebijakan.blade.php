@extends('layouts.public')

@section('title', 'Kebijakan Privasi · '.$profil->nama_desa)
@section('description', 'Kebijakan privasi website resmi '.$profil->nama_desa.'.')

@section('content')
<div class="topbar">
    <div class="container">
      <div class="topbar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21V8l9-6 9 6v13"/><path d="M9 21v-6h6v6"/></svg>
        <span>Pemerintah {{ $profil->kabupaten }} · Kecamatan {{ $profil->kecamatan }}</span>
      </div>
      <div class="topbar-right">
        <div class="topbar-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
          <span>Pelayanan Senin–Jumat, 08.00–15.00 WIB</span>
        </div>
        <div class="topbar-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.9.6 2.9.7A2 2 0 0 1 22 16.9z"/></svg>
          <span>(0251) 830 1122</span>
        </div>
      </div>
    </div>
  </div>

  <header class="site-header">
    <div class="container">
      <a href="{{ route('beranda') }}" class="brand">
        <div class="brand-mark">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M4 18h16M6 18v-8h3v8M11 18v-8h3v8M16 18v-8h3v8M12 3l9 5H3l9-5z"/></svg>
        </div>
        <div>
          <div class="brand-name">{{ $profil->nama_desa }}</div>
          <div class="brand-sub">Kabupaten Bogor, Jawa Barat</div>
        </div>
      </a>
      <nav class="main-nav" id="mainNav">
        <a href="{{ route('beranda') }}">Beranda</a>
        <a href="{{ route('profil') }}">Profil Desa</a>
        <a href="{{ route('informasi') }}">Informasi</a>
        <a href="{{ route('potensi') }}">Potensi</a>
        <a href="{{ route('pelayanan') }}">Pelayanan</a>
        <a href="{{ route('kontak') }}">Kontak</a>
      </nav>
      <a href="{{ route('pelayanan') }}" class="btn btn-primary btn-sm">Ajukan Surat</a>
      <button class="nav-toggle" id="navToggle" aria-label="Menu" aria-expanded="false" aria-controls="mainNav">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
    </div>
  </header>

  <section class="page-hero">
    <div class="container">
      <nav class="breadcrumb">
        <a href="{{ route('beranda') }}">Beranda</a>
        <span class="sep">/</span>
        <span>Kebijakan Privasi</span>
      </nav>
      <h1>Kebijakan Privasi</h1>
      <p class="lead" style="margin-top:14px;max-width:620px">Halaman ini masih disusun bersama Pemerintah {{ $profil->nama_desa }}.</p>
    </div>
  </section>

  <section class="section">
    <div class="container" style="max-width:760px">
      <div class="notice notice-blue">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>
        <span><b>Belum diterbitkan.</b> Teks kebijakan privasi resmi belum tersedia. Tautan ini sudah dipasang agar struktur footer sesuai desain, dan akan diisi begitu dokumennya disetujui desa.</span>
      </div>

      <div class="card" style="margin-top:26px">
        <h3 style="margin-bottom:12px">Yang bisa dibaca sekarang</h3>
        <p class="muted small">Data pemohon dan dokumen persyaratan yang dikirim melalui formulir pengajuan disimpan pada penyimpanan privat dan hanya dapat diakses admin desa yang berwenang.</p>
      </div>

      <div class="flex gap-12 mt-24 wrap">
        <a href="{{ route('beranda') }}" class="btn btn-primary">Kembali ke Beranda</a>
        <a href="{{ route('kontak') }}" class="btn btn-outline">Hubungi Kantor Desa</a>
      </div>
    </div>
  </section>

  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <div class="brand">
            <div class="brand-mark">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M4 18h16M6 18v-8h3v8M11 18v-8h3v8M16 18v-8h3v8M12 3l9 5H3l9-5z"/></svg>
            </div>
            <div>
              <div class="brand-name">{{ $profil->nama_desa }}</div>
              <div class="brand-sub">Kecamatan {{ $profil->kecamatan }} · {{ $profil->kabupaten }}</div>
            </div>
          </div>
          <p>Website resmi Pemerintah {{ $profil->nama_desa }} untuk informasi desa dan pelayanan administrasi surat secara daring.</p>
          <div class="footer-badges">
            <span class="badge badge-dark">Data terlindungi</span>
            <span class="muted small">Diperbarui 2026</span>
          </div>
        </div>
        <div class="footer-col">
          <h4>Jelajahi</h4>
          <ul>
            <li><a href="{{ route('profil') }}">Profil Desa</a></li>
            <li><a href="{{ route('informasi') }}">Berita &amp; Pengumuman</a></li>
            <li><a href="{{ route('informasi') }}#pengumuman">Agenda Kegiatan</a></li>
            <li><a href="{{ route('potensi') }}">Potensi Desa</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Pelayanan</h4>
          <ul>
            <li><a href="{{ route('pelayanan') }}#katalog">Katalog Surat</a></li>
            <li><a href="{{ route('pelayanan') }}#cara">Cara Mengajukan</a></li>
            <li><a href="{{ route('pelayanan') }}#persyaratan">Persyaratan Umum</a></li>
            <li><a href="{{ route('kontak') }}">Bantuan &amp; Kontak</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Kantor Desa</h4>
          <ul>
            <li>{{ $profil->alamat_kantor }}</li>
            <li style="margin-top:14px">Telp. (0251) 830 1122</li>
            <li>WhatsApp 0812–3456–7890</li>
            <li>Senin–Jumat, 08.00–15.00 WIB</li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <span>© {{ now()->year }} Pemerintah {{ $profil->nama_desa }}. Seluruh hak dilindungi.</span>
        <div class="footer-bottom-links">
          <a href="{{ route('privasi.kebijakan') }}">Kebijakan Privasi</a>
          <a href="{{ route('privasi.pemberitahuan') }}">Pemberitahuan Data</a>
          <a href="{{ route('admin.login') }}">Masuk Admin</a>
        </div>
      </div>
    </div>
  </footer>
@endsection
