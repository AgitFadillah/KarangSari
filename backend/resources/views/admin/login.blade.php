@extends('layouts.auth')

@section('title', 'Masuk Admin · '.$profil->nama_desa)

@section('content')
<div class="auth-split">
    <aside class="auth-side">
      <a href="{{ route('beranda') }}" class="brand">
        <div class="brand-mark">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M4 18h16M6 18v-8h3v8M11 18v-8h3v8M16 18v-8h3v8M12 3l9 5H3l9-5z"/></svg>
        </div>
        <div>
          <div class="brand-name">{{ $profil->nama_desa }}</div>
          <div class="brand-sub">DASHBOARD ADMIN</div>
        </div>
      </a>
      <div class="auth-side-mid">
        <span class="eyebrow eyebrow-plain">RUANG KERJA PETUGAS</span>
        <h2>Satu tempat untuk memeriksa dan menerbitkan surat warga.</h2>
        <p class="lead">Periksa data pemohon, dokumen persyaratan, dan preview surat sebelum menyetujui. Hubungi warga melalui WhatsApp ketika surat siap diambil.</p>
        <div class="auth-feature">
          <div class="auth-feature-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
          </div>
          <span>Verifikasi data dan dokumen dalam satu layar</span>
        </div>
        <div class="auth-feature">
          <div class="auth-feature-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
          </div>
          <span>Preview surat otomatis dari template desa</span>
        </div>
        <div class="auth-feature">
          <div class="auth-feature-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
          </div>
          <span>Riwayat status dan log aktivitas tercatat</span>
        </div>
      </div>
      <div class="auth-side-bottom">
        <span>Akses terbatas untuk petugas desa</span>
        <a href="{{ route('beranda') }}">Rincian selengkapnya di website publik →</a>
      </div>
    </aside>

    <main class="auth-main">
      <div class="auth-stack">
        <div class="auth-card">
          <div class="auth-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
          </div>
          <h1>Masuk ke dashboard</h1>
          <p>Hanya untuk admin desa yang terdaftar dan aktif.</p>
          <form id="loginForm" method="POST" action="{{ route('admin.login.store') }}" novalidate>
            @csrf
            <div class="field">
              <label for="username">Nama pengguna</label>
              <input type="text" id="username" name="username" value="{{ old('username') }}" placeholder="admin" autocomplete="username" required autofocus>
            </div>
            <div class="field">
              <div class="field-label-row">
                <label for="password">Kata sandi</label>
                <button type="button" class="label-link" data-modal-open="modalLupa">Lupa kata sandi?</button>
              </div>
              <div class="pw-wrap">
                <input type="password" id="password" name="password" placeholder="Masukkan kata sandi" autocomplete="current-password" required>
                <button type="button" class="pw-toggle" data-pw-toggle aria-label="Tampilkan kata sandi">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 117 11 7 4-7 11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
            </div>
            @if ($errors->any())
              <p class="form-alert show" role="alert">{{ $errors->first() }}</p>
            @else
              <p class="form-alert" id="loginAlert" role="alert">Nama pengguna dan kata sandi wajib diisi.</p>
            @endif
            <button type="submit" class="btn btn-primary btn-block">Masuk</button>
          </form>
          <div class="auth-note">Akun dikelola administrator sistem desa. Hubungi kantor desa jika Anda belum memiliki akses.</div>
        </div>
        <p class="auth-below">Dengan masuk, Anda menyetujui penggunaan data sesuai <a href="{{ route('privasi.kebijakan') }}">kebijakan privasi</a> desa.</p>
      </div>
    </main>
  </div>

  <div class="modal-overlay" id="modalLupa">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="lupaTitle">
      <div class="modal-head">
        <h2 id="lupaTitle">Lupa kata sandi?</h2>
        <button type="button" class="modal-close" data-modal-close aria-label="Tutup">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <p class="muted">Pemulihan kata sandi dilakukan oleh administrator sistem desa. Hubungi administrator untuk pengaturan ulang akun.</p>
      <div class="auth-note">Telepon kantor desa <a href="tel:{{ preg_replace('/\s+/', '', $profil->telepon) }}">{{ $profil->telepon }}</a>, {{ $profil->jam_layanan }}.</div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" data-modal-close>Tutup</button>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script>
  // Validasi sisi klien sebelum dikirim ke server (server tetap memvalidasi ulang).
  (function () {
    var form = document.getElementById('loginForm');
    var alertBox = document.getElementById('loginAlert');
    if (!form || !alertBox) return;
    form.addEventListener('submit', function (e) {
      var u = document.getElementById('username');
      var p = document.getElementById('password');
      if (!u.value.trim() || !p.value) {
        e.preventDefault();
        alertBox.classList.add('show');
        (!u.value.trim() ? u : p).focus();
      }
    });
  })();
</script>
@endpush
