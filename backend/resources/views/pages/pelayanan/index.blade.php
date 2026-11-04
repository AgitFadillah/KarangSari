@extends('layouts.public')

@section('title', 'Pelayanan Surat · '.$profil->nama_desa)
@section('description', 'Katalog jenis surat desa, persyaratan umum, dan cara mengajukan surat secara online di '.$profil->nama_desa.', Kecamatan '.$profil->kecamatan.', '.$profil->kabupaten.'.')

@section('content')
{{-- topbar + header dari partial --}}

  <!-- Page Hero -->
  <section class="page-hero page-hero-dark">
    <div class="container">
      <nav class="breadcrumb">
        <a href="{{ route('beranda') }}">Beranda</a>
        <span class="sep">/</span>
        <span>Pelayanan</span>
      </nav>
      <div class="page-hero-row">
        <div>
          <div class="eyebrow">Pelayanan Administrasi</div>
          <h1>Ajukan surat desa, mulai dari sini.</h1>
          <p class="lead" style="max-width:560px">Pilih jenis surat untuk membaca persyaratan, lalu isi formulir sesuai data Anda. Tidak perlu membuat akun.</p>
        </div>
        <div class="hero-stats">
          <div class="stat-cell">
            <div class="cell-label">Layanan</div>
            <div class="cell-value">{{ $jenisSurats->count() }}</div>
          </div>
          <div class="stat-cell">
            <div class="cell-label">Akun</div>
            <div class="cell-value">Tanpa</div>
          </div>
          <div class="stat-cell">
            <div class="cell-label">Biaya</div>
            <div class="cell-value">Gratis</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Katalog jenis surat -->
  <section class="section" id="katalog">
    <div class="container">
      <div class="section-head-row">
        <div>
          <h2>Katalog jenis surat</h2>
          <p class="muted small" style="margin-top:10px">Daftar berikut berasal dari konfigurasi layanan desa.</p>
        </div>
        <div class="search-box" style="min-width:320px">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
          <input type="search" id="catalogSearch" placeholder="Cari surat, misalnya domisili" aria-label="Cari jenis surat">
        </div>
      </div>

      <div class="catalog-divider"></div>
      <p class="muted small" id="catalogCount" aria-live="polite">{{ $jenisSurats->count() }} dari {{ $jenisSurats->count() }} jenis surat ditampilkan</p>

      <div class="catalog-grid" style="margin-top:20px">
        @forelse ($jenisSurats as $jenis)
          <a href="{{ route('layanan.detail', $jenis->slug) }}" class="catalog-card">
            <div class="catalog-top">
              @if ($jenis->kode_layanan)
                <span class="badge badge-gray">KODE {{ $jenis->kode_layanan }}</span>
              @endif
              @if ($jenis->estimasi_hari)
                <span class="badge badge-gray">{{ $jenis->estimasi_hari }}</span>
              @endif
            </div>
            <h3>{{ $jenis->nama }}</h3>
            <p>{{ $jenis->deskripsi }}</p>
            <div class="catalog-foot">
              <span>{{ $jenis->persyaratan_count }} persyaratan dokumen</span>
              <span class="panel-link">Lihat detail &#8594;</span>
            </div>
          </a>
        @empty
          <p class="muted">Belum ada jenis surat aktif. Silakan hubungi kantor desa.</p>
        @endforelse
      </div>
      <div class="catalog-empty" id="catalogEmpty" role="status">
        <h3>Jenis surat tidak ditemukan</h3>
        <p>Tidak ada surat yang cocok dengan kata kunci Anda. Coba kata lain, misalnya “domisili” atau “ktp”.</p>
        <button type="button" class="btn btn-outline btn-sm" id="catalogReset">Tampilkan semua surat</button>
      </div>

      <div class="notice notice-gray notice-row" style="margin-top:32px">
        <span>Daftar jenis surat dan persyaratan dikelola langsung oleh admin desa melalui dashboard. Perubahan persyaratan berlaku untuk pengajuan berikutnya.</span>
        <a href="{{ route('kontak') }}" class="panel-link">Tanyakan layanan lain →</a>
      </div>
    </div>
  </section>

  <!-- Cara mengajukan -->
  <section class="section section-tint" id="cara">
    <div class="container">
      <div class="eyebrow">Cara Mengajukan</div>
      <h2 style="max-width:640px;margin-top:14px">Dari memilih surat sampai mengambil surat fisik.</h2>
      <div class="step-cards" style="margin-top:44px">
        <div class="step-card">
          <div class="step-card-label">Langkah 01</div>
          <h3>Pilih jenis surat</h3>
          <p>Baca deskripsi dan persyaratan yang berlaku untuk kebutuhan Anda.</p>
        </div>
        <div class="step-card">
          <div class="step-card-label">Langkah 02</div>
          <h3>Isi formulir</h3>
          <p>Lengkapi data pemohon dan nomor WhatsApp yang aktif digunakan.</p>
        </div>
        <div class="step-card">
          <div class="step-card-label">Langkah 03</div>
          <h3>Unggah dokumen</h3>
          <p>Unggah KTP, KK, dan dokumen lain sesuai jenis surat.</p>
        </div>
        <div class="step-card">
          <div class="step-card-label">Langkah 04</div>
          <h3>Tunggu konfirmasi</h3>
          <p>Admin memeriksa, mencetak, lalu menghubungi Anda melalui WhatsApp.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Persyaratan umum -->
  <section class="section" id="persyaratan">
    <div class="container">
      <div class="two-col-wide">
        <div>
          <div class="eyebrow">Sebelum Mengajukan</div>
          <h2 style="margin-top:14px">Persyaratan umum dan hal yang perlu diperhatikan.</h2>
          <p class="lead muted" style="margin-top:18px">Setiap jenis surat memiliki persyaratan tambahan. Pastikan dokumen terbaca dan sesuai nama pemohon.</p>
        </div>
        <div class="req-list">
          <div class="req-item">
            <span class="req-icon green" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
            </span>
            <div>
              <div class="req-title">Dokumen berformat JPG, PNG, atau PDF</div>
              <div class="req-desc">Ukuran maksimal mengikuti ketentuan tiap dokumen, umumnya 2 MB.</div>
            </div>
          </div>
          <div class="req-item">
            <span class="req-icon green" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
            </span>
            <div>
              <div class="req-title">Nomor WhatsApp aktif</div>
              <div class="req-desc">Admin menghubungi nomor ini ketika surat siap diambil atau ada perbaikan.</div>
            </div>
          </div>
          <div class="req-item">
            <span class="req-icon green" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
            </span>
            <div>
              <div class="req-title">Data sesuai dokumen resmi</div>
              <div class="req-desc">Perbedaan nama atau NIK dapat menyebabkan pengajuan dikembalikan untuk perbaikan.</div>
            </div>
          </div>
          <div class="req-item">
            <span class="req-icon amber" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </span>
            <div>
              <div class="req-title">Surat tetap diambil secara fisik</div>
              <div class="req-desc">Website tidak menyediakan unduhan surat final atau pelacakan status publik.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- footer dari partial --}}
@endsection
