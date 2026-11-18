<!-- Topbar -->
  <div class="topbar">
    <div class="container">
      {{-- Setiap butir hanya dirender bila datanya terisi. Sebelumnya
           topbar menampilkan "Pelayanan " dan satu butir kosong saat jam
           layanan / telepon belum diisi di menu Profil & Kontak. --}}
      @if (filled($profil->kabupaten) || filled($profil->kecamatan))
        <div class="topbar-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21V8l9-6 9 6v13"/><path d="M9 21v-6h6v6"/></svg>
          <span>{{ $profil->wilayah }}</span>
        </div>
      @endif
      <div class="topbar-right">
        @if (filled($profil->jam_layanan))
          <div class="topbar-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            <span>Pelayanan {{ $profil->jam_layanan }}</span>
          </div>
        @endif
        @if (filled($profil->telepon))
          <div class="topbar-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.9.6 2.9.7A2 2 0 0 1 22 16.9z"/></svg>
            <span>{{ $profil->telepon }}</span>
          </div>
        @endif
      </div>
    </div>
  </div>

  <!-- Header / Nav -->
  <header class="site-header">
    <div class="container">
      <a href="{{ route('beranda') }}" class="brand">
        <div class="brand-mark @if ($profil->logoUrl()) brand-mark-logo @endif">
          @if ($profil->logoUrl())
            <img src="{{ $profil->logoUrl() }}" alt="Logo {{ $profil->nama_desa }}" width="44" height="44">
          @else
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M4 18h16M6 18v-8h3v8M11 18v-8h3v8M16 18v-8h3v8M12 3l9 5H3l9-5z"/></svg>
          @endif
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
        <a href="{{ route('kontak') }}" @class(['active' => $activeNav === 'kontak'])>Kontak</a>

        {{-- Layanan di layar kecil: pengganti tombol "Ajukan Surat" yang
             disembunyikan pada lebar < 1024px. --}}
        <div class="nav-mobile-layanan">
          <div class="nav-mobile-layanan-label">Layanan</div>
          <a href="{{ route('pelayanan') }}" @class(['active' => $activeNav === 'pelayanan'])>Pelayanan</a>
          <a href="{{ route('cek-status.form') }}" @class(['active' => $activeNav === 'cek-status'])>Cek Status</a>
          <a href="{{ route('panduan') }}" @class(['active' => $activeNav === 'panduan'])>Panduan</a>
        </div>
      </nav>

      {{-- Tombol terbelah: label menuju katalog layanan, panah membuka menu. --}}
      <div class="nav-aksi" id="navAksi">
        <a href="{{ route('pelayanan') }}" class="btn btn-primary btn-sm nav-aksi-utama">Ajukan Surat</a>
        <button type="button"
                class="btn btn-primary btn-sm nav-aksi-panah"
                id="navAksiToggle"
                aria-expanded="false"
                aria-haspopup="true"
                aria-controls="navAksiMenu"
                aria-label="Menu layanan surat">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </button>

        <div class="nav-aksi-menu" id="navAksiMenu" hidden>
          <a href="{{ route('pelayanan') }}" @class(['active' => $activeNav === 'pelayanan'])>
            <span class="nav-aksi-menu-ikon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h13a3 3 0 0 1 3 3v13H7a3 3 0 0 1-3-3V4z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
            </span>
            <span class="nav-aksi-menu-teks">
              <span class="nav-aksi-menu-judul">Pelayanan</span>
              <span class="nav-aksi-menu-sub">Katalog surat &amp; ajukan</span>
            </span>
          </a>
          <a href="{{ route('cek-status.form') }}" @class(['active' => $activeNav === 'cek-status'])>
            <span class="nav-aksi-menu-ikon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            </span>
            <span class="nav-aksi-menu-teks">
              <span class="nav-aksi-menu-judul">Cek Status</span>
              <span class="nav-aksi-menu-sub">Pantau pengajuan</span>
            </span>
          </a>
          <a href="{{ route('panduan') }}" @class(['active' => $activeNav === 'panduan'])>
            <span class="nav-aksi-menu-ikon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 6.5A4.5 4.5 0 1 0 7.5 2 4.5 4.5 0 0 0 12 6.5z"/><path d="M12 22a9 9 0 1 0-9-9"/></svg>
            </span>
            <span class="nav-aksi-menu-teks">
              <span class="nav-aksi-menu-judul">Panduan</span>
              <span class="nav-aksi-menu-sub">Cara mengajukan</span>
            </span>
          </a>
        </div>
      </div>
      <button class="nav-toggle" id="navToggle" aria-label="Menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
    </div>
  </header>