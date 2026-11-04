@extends('layouts.public')

@section('title', $jenisSurat->nama.' · '.$profil->nama_desa)
@section('description', $jenisSurat->deskripsi)

@section('content')
{{-- topbar + header dari partial --}}

  <!-- Hero detail layanan -->
  <section class="page-hero page-hero-soft">
    <div class="container">
      <nav class="breadcrumb">
        <a href="{{ route('beranda') }}">Beranda</a>
        <span class="sep">/</span>
        <a href="{{ route('pelayanan') }}">Pelayanan</a>
        <span class="sep">/</span>
        <span>{{ $jenisSurat->nama }}</span>
      </nav>
      <div class="detail-hero-grid">
        <div>
          @if ($jenisSurat->kode_layanan)
            <div class="eyebrow eyebrow-plain">Kode Layanan {{ $jenisSurat->kode_layanan }}</div>
          @endif
          <h1>{{ $jenisSurat->nama }}</h1>
          <p class="lead">{{ $jenisSurat->deskripsi }}</p>
          <div class="chips">
            @if ($jenisSurat->estimasi_hari)
              <span class="chip">Estimasi {{ $jenisSurat->estimasi_hari }}</span>
            @endif
            <span class="chip-plain">Tanpa akun</span>
            <span class="chip-plain">Tanpa biaya tambahan</span>
          </div>
        </div>
        <div class="cta-card">
          <h3>Siap mengajukan?</h3>
          <p>Siapkan dokumen persyaratan dalam bentuk foto atau PDF yang terbaca jelas.</p>
          <a href="{{ route('pengajuan.create', ['jenis' => $jenisSurat->slug]) }}" class="btn btn-primary btn-block">
            Isi formulir pengajuan
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </a>
          <span class="fine">Surat diambil secara fisik di kantor desa</span>
        </div>
      </div>
    </div>
  </section>

  <!-- Persyaratan & info -->
  <section class="section">
    <div class="container">
      <div class="detail-grid">
        <div class="detail-main">
          <h2>Persyaratan dokumen</h2>
          <p class="detail-sub">Unggah dokumen berikut saat mengisi formulir. Format JPG, PNG, atau PDF.</p>
          <hr class="detail-hr">

          @forelse ($jenisSurat->persyaratan as $syarat)
            <div class="req-item">
              <span class="req-check" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
              </span>
              <div>
                <div class="req-title">{{ $syarat->label }}</div>
                @if ($syarat->petunjuk)
                  <div class="req-desc">{{ $syarat->petunjuk }}</div>
                @endif
              </div>
            </div>
          @empty
            <p class="muted">Belum ada persyaratan dokumen yang dikonfigurasi untuk layanan ini. Hubungi kantor desa untuk informasi lebih lanjut.</p>
          @endforelse

          <div class="detail-split">
            <div>
              <h2>Data yang diisi</h2>
              <p class="detail-sub">Formulir menyesuaikan jenis surat yang dipilih.</p>
              <hr class="detail-hr">
              <div class="data-list">
                <div class="data-item">Nama lengkap</div>
                <div class="data-item">NIK</div>
                <div class="data-item">Tempat &amp; tanggal lahir</div>
                <div class="data-item">Jenis kelamin</div>
                <div class="data-item">Alamat lengkap</div>
                <div class="data-item">Keperluan surat</div>
                <div class="data-item">Nomor WhatsApp aktif</div>
              </div>
            </div>
            <div class="after-panel">
              <div class="after-label">Setelah pengajuan dikirim</div>
              <div class="after-step"><span class="after-num">1</span><span>Admin memeriksa data dan dokumen Anda.</span></div>
              <div class="after-step"><span class="after-num">2</span><span>Surat dibuat dari template resmi desa dan diperiksa admin.</span></div>
              <div class="after-step"><span class="after-num">3</span><span>Anda dihubungi melalui WhatsApp saat surat siap diambil.</span></div>
              <div class="after-step"><span class="after-num">4</span><span>Datang ke kantor desa dengan membawa identitas.</span></div>
            </div>
          </div>

          <div class="notice notice-amber" style="margin-top:34px">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
            <div>
              <b>Perhatikan keamanan data Anda</b><br>
              Dokumen yang diunggah hanya dapat dilihat admin desa. Jangan mengirim dokumen melalui kolom catatan atau pesan WhatsApp kecuali diminta petugas.
            </div>
          </div>
        </div>

        <aside class="detail-side">
          <div class="side-card">
            <div class="side-label">Layanan lain</div>
            <a href="{{ route('pelayanan') }}#katalog" class="panel-link">← Semua layanan</a>
          </div>
          <div class="side-card side-card-dark">
            <h3>Ada pertanyaan?</h3>
            <p>Hubungi kantor desa pada jam pelayanan.</p>
            <a href="{{ route('kontak') }}">Buka halaman kontak →</a>
          </div>
        </aside>
      </div>
    </div>
  </section>

  <!-- CTA bawah -->
  <section class="section" style="padding-top:0;padding-bottom:72px">
    <div class="container">
      <div class="cta-band">
        <div>
          <h2>Sudah menyiapkan dokumen?</h2>
          <p>Pengisian formulir membutuhkan waktu sekitar 5-10 menit.</p>
        </div>
        <a href="{{ route('pengajuan.create', ['jenis' => $jenisSurat->slug]) }}" class="btn btn-primary">Ajukan surat ini</a>
      </div>
    </div>
  </section>

  {{-- footer dari partial --}}
@endsection
