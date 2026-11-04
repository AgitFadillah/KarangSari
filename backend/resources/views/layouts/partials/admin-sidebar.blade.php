<aside class="admin-sidebar" id="adminSidebar">
      <div class="admin-brand">
        <div class="brand-mark" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M4 18h16M6 18v-8h3v8M11 18v-8h3v8M16 18v-8h3v8M12 3l9 5H3l9-5z"/></svg>
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
        <a href="{{ route('admin.pengajuan.index') }}" @class(['active' => $activeAdmin === 'admin.pengajuan.index'])>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/></svg>
          Pengajuan Surat
          @if (($pendingCount ?? 0) > 0)
            <span class="badge badge-gray">{{ $pendingCount }}</span>
          @endif
        </a>
        <div class="admin-nav-label">PENGATURAN</div>
        <a href="{{ route('admin.jenis-surat.index') }}" @class(['active' => $activeAdmin === 'admin.jenis-surat.index'])>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
          Jenis Surat
        </a>
        <a href="{{ route('admin.konten.index') }}" @class(['active' => $activeAdmin === 'admin.konten.index'])>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10M7 12h10M7 16h6"/></svg>
          Konten Website
        </a>
        <a href="{{ route('admin.profil.edit') }}" @class(['active' => $activeAdmin === 'admin.profil.edit'])>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
          Profil &amp; Kontak
        </a>
        <a href="{{ route('admin.akun.index') }}" @class(['active' => $activeAdmin === 'admin.akun.index'])>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          Akun Admin
        </a>
        <a href="{{ route('admin.log') }}" @class(['active' => $activeAdmin === 'admin.log'])>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
          Log Aktivitas
        </a>
        <div class="admin-help">
          <b>Butuh bantuan?</b>
          Panduan singkat alur verifikasi dan pengelolaan surat.
          <a href="{{ route('pelayanan') }}#cara">Buka panduan →</a>
        </div>
      </nav>
      <a href="{{ route('admin.akun.index') }}" class="admin-user">
        <div class="avatar">{{ auth()->user()->initials }}</div>
        <div>
          <div class="admin-user-name">{{ auth()->user()->name }}</div>
          <div class="admin-user-role">{{ auth()->user()->role->label() }}</div>
        </div>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="margin-left:auto;opacity:.5"><path d="M6 9l6 6 6-6"/></svg>
      </a>
    </aside>