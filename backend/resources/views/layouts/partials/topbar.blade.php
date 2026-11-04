<!-- Topbar -->
  <div class="topbar">
    <div class="container">
      <div class="topbar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21V8l9-6 9 6v13"/><path d="M9 21v-6h6v6"/></svg>
        <span>Pemerintah {{ $profil->kabupaten }} · Kecamatan {{ $profil->kecamatan }}</span>
      </div>
      <div class="topbar-right">
        <div class="topbar-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
          <span>Pelayanan {{ $profil->jam_layanan }}</span>
        </div>
        <div class="topbar-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.9.6 2.9.7A2 2 0 0 1 22 16.9z"/></svg>
          <span>{{ $profil->telepon }}</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Header / Nav -->
  <header class="site-header">
    <div class="container">
      <a href="{{ route('beranda') }}" class="brand">
        <div class="brand-mark">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M4 18h16M6 18v-8h3v8M11 18v-8h3v8M16 18v-8h3v8M12 3l9 5H3l9-5z"/></svg>
        </div>
        <div>
          <div class="brand-name">{{ $profil->nama_desa }}</div>
          <div class="brand-sub">{{ $profil->kabupaten }}, {{ $profil->provinsi }}</div>
        </div>
      </a>
      <nav class="main-nav" id="mainNav">
        <a href="{{ route('beranda') }}" @class(['active' => $activeNav === 'beranda'])>Beranda</a>
        <a href="{{ route('profil') }}" @class(['active' => $activeNav === 'profil'])>Profil Desa</a>
        <a href="{{ route('informasi') }}" @class(['active' => $activeNav === 'informasi'])>Informasi</a>
        <a href="{{ route('potensi') }}" @class(['active' => $activeNav === 'potensi'])>Potensi</a>
        <a href="{{ route('pelayanan') }}" @class(['active' => $activeNav === 'pelayanan'])>Pelayanan</a>
        <a href="{{ route('kontak') }}" @class(['active' => $activeNav === 'kontak'])>Kontak</a>
      </nav>
      <a href="{{ route('pelayanan') }}" class="btn btn-primary btn-sm">Ajukan Surat</a>
      <button class="nav-toggle" id="navToggle" aria-label="Menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
    </div>
  </header>