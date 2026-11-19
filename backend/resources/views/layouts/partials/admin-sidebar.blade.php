<aside class="admin-sidebar" id="adminSidebar">
      <div class="admin-brand">
        <div class="brand-mark @if ($profil->logoUrl()) brand-mark-logo @endif" aria-hidden="true">
          @if ($profil->logoUrl())
            <img src="{{ $profil->logoUrl() }}" alt="" width="44" height="44">
          @else
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M4 18h16M6 18v-8h3v8M11 18v-8h3v8M16 18v-8h3v8M12 3l9 5H3l9-5z"/></svg>
          @endif
        </div>
        <div>
          <div class="brand-name">{{ $profil->nama_desa }}</div>
          <div class="brand-sub">DASHBOARD ADMIN</div>
        </div>
      </div>
      <nav class="admin-nav" aria-label="Menu admin">
        <div class="admin-nav-label">OPERASIONAL</div>
        <a href="{{ route('admin.ringkasan') }}" @class(['active' => $activeAdmin === 'admin.ringkasan']) aria-current="page">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
          Ringkasan
        </a>
        {{-- Menu pengajuan hanya untuk peran yang boleh memverifikasi:
             halaman ini memuat NIK, alamat, dan berkas KTP/KK warga. --}}
        @if (auth()->user()->role->bolehVerifikasiPengajuan())
          <a href="{{ route('admin.pengajuan.index') }}" @class(['active' => $activeAdmin === 'admin.pengajuan.index'])>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/></svg>
            Pengajuan Surat
            @if (($pendingCount ?? 0) > 0)
              <span class="badge badge-gray">{{ $pendingCount }}</span>
            @endif
          </a>
        @endif
        @if (auth()->user()->role->bolehKelolaKonten())
          <div class="admin-nav-label">KONTEN WEBSITE</div>
          <a href="{{ route('admin.berita.index') }}" @class(['active' => $activeAdmin === 'admin.berita.index'])>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h13a3 3 0 0 1 3 3v13H7a3 3 0 0 1-3-3V4z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
            Berita &amp; Agenda
          </a>
          <a href="{{ route('admin.potensi.index') }}" @class(['active' => $activeAdmin === 'admin.potensi.index'])>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22v-8M12 14c-4 0-7-3-7-8 5 0 7 3 7 8zM12 14c4 0 7-3 7-8-5 0-7 3-7 8z"/></svg>
            Potensi Desa
          </a>
          <a href="{{ route('admin.statistik.index') }}" @class(['active' => $activeAdmin === 'admin.statistik.index'])>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 15l3-4 3 3 4-6"/></svg>
            Statistik Beranda
          </a>
          <a href="{{ route('admin.perangkat.index') }}" @class(['active' => $activeAdmin === 'admin.perangkat.index'])>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/></svg>
            Perangkat Desa
          </a>
        @endif
        <div class="admin-nav-label">PENGATURAN</div>
        @if (auth()->user()->role->bolehKelolaJenisSurat())
          <a href="{{ route('admin.jenis-surat.index') }}" @class(['active' => $activeAdmin === 'admin.jenis-surat.index'])>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
            Jenis Surat
          </a>
        @endif
        @if (auth()->user()->role->bolehKelolaKonten())
          <a href="{{ route('admin.konten.index') }}" @class(['active' => $activeAdmin === 'admin.konten.index'])>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10M7 12h10M7 16h6"/></svg>
            Konten Website
          </a>
          <a href="{{ route('admin.profil.edit') }}" @class(['active' => $activeAdmin === 'admin.profil.edit'])>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
            Profil &amp; Kontak
          </a>
        @endif
        @if (auth()->user()->role->bolehKelolaAkun())
          <a href="{{ route('admin.akun.index') }}" @class(['active' => $activeAdmin === 'admin.akun.index'])>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Akun Admin
          </a>
        @endif
        <a href="{{ route('admin.log') }}" @class(['active' => $activeAdmin === 'admin.log'])>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
          Log Aktivitas
        </a>
      </nav>

      {{-- Kartu bantuan diletakkan di LUAR <nav> yang bisa digulir.
           Sebelumnya kartu ini berada di dalam daftar menu, sehingga saat
           menunya panjang ia ikut tergulir dan menimpa baris pengguna di
           bawah — itulah sebabnya baris pengguna tampak "terpotong". --}}
      <div class="admin-help">
        <b>Butuh bantuan?</b>
        Panduan lengkap alur verifikasi, dokumen, status, dan pengelolaan konten.
        <a href="{{ route('admin.panduan') }}">Buka panduan →</a>
      </div>

      {{-- Baris pengguna: kini benar-benar bisa dibuka (dulu hanya tampilan
           statis dengan ikon panah yang menyesatkan). Panelnya terbuka ke
           ATAS karena baris ini menempel di dasar sidebar. --}}
      <div class="admin-user-wrap">
        <button type="button" class="admin-user" id="adminSidebarUserBtn"
                aria-expanded="false" aria-controls="adminSidebarUserPanel" aria-haspopup="true">
          <div class="avatar">{{ auth()->user()->initials }}</div>
          <div class="admin-user-teks">
            <div class="admin-user-name">{{ auth()->user()->name }}</div>
            <div class="admin-user-role">{{ auth()->user()->role->label() }}</div>
          </div>
          <svg class="admin-user-panah" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" aria-hidden="true"><path d="M6 15l6-6 6 6"/></svg>
        </button>

        <div class="admin-user-panel admin-user-panel-atas" id="adminSidebarUserPanel" hidden>
          <div class="admin-user-panel-head">
            <div class="avatar">{{ auth()->user()->initials }}</div>
            <div>
              <div class="nama">{{ auth()->user()->name }}</div>
              <div class="peran">{{ auth()->user()->role->label() }}</div>
            </div>
          </div>
          <div class="admin-user-panel-meta">
            <span class="label">Nama pengguna</span>
            <span class="nilai">{{ auth()->user()->username }}</span>
          </div>
          <div class="admin-user-panel-actions">
            @if (auth()->user()->role->bolehKelolaAkun())
              <a href="{{ route('admin.akun.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Akun Admin
              </a>
            @endif
            <a href="{{ route('admin.panduan') }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
              Panduan admin
            </a>
            <form method="POST" action="{{ route('admin.logout') }}">
              @csrf
              <button type="submit" class="danger">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                Keluar
              </button>
            </form>
          </div>
        </div>
      </div>
    </aside>