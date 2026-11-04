@extends('layouts.public')

@section('title', 'Informasi Desa · '.$profil->nama_desa)
@section('description', 'Berita, agenda, dan pengumuman resmi Pemerintah '.$profil->nama_desa.', Kecamatan '.$profil->kecamatan.', '.$profil->kabupaten.'.')

@section('content')
{{-- topbar + header dari partial --}}

  <!-- Page Hero -->
  <section class="page-hero">
    <div class="container">
      <nav class="breadcrumb">
        <a href="{{ route('beranda') }}">Beranda</a>
        <span class="sep">/</span>
        <span>Informasi</span>
      </nav>
      <div class="page-hero-row">
        <div>
          <div class="eyebrow">Informasi Desa</div>
          <h1>Kabar, pengumuman, dan kegiatan warga.</h1>
          <p class="lead" style="margin-top:14px;max-width:560px">Semua informasi resmi desa dipublikasikan di halaman ini. Konten yang tampil saat ini masih berupa contoh.</p>
        </div>
      </div>
      <div class="tabs" style="margin-top:32px" data-info-tabs>
        <button class="tab active" data-filter="semua">Semua</button>
        <button class="tab" data-filter="berita">Berita</button>
        <button class="tab" data-filter="agenda">Agenda</button>
        <button class="tab" data-filter="pengumuman">Pengumuman</button>
      </div>
    </div>
  </section>

  <!-- Berita Utama + Side -->
  <section class="section" style="padding-top:56px">
    <div class="container">
      <div class="news-grid">
        <a href="#" class="news-feature" style="text-decoration:none" data-cat="berita">
          <div class="news-feature-img">
            <svg class="art" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M3 21h18M4 18h16M6 18v-8h3v8M11 18v-8h3v8M16 18v-8h3v8M12 3l9 5H3l9-5z"/></svg>
            <span class="badge badge-navy" style="background:var(--blue-500)">Berita Utama</span>
          </div>
          <div class="news-feature-body">
            <h3>Musyawarah Desa Penyusunan RKPDes 2027 Digelar 8 November</h3>
            <p>Hasil musyawarah menetapkan prioritas pembangunan irigasi dan perbaikan jalan lingkung di Dusun Krajan. Seluruh ketua RT/RW dan tokoh masyarakat diundang hadir di Aula Balai Desa.</p>
            <div class="news-meta">
              <span>Pemerintah Desa</span><span class="dot"></span><span>28 Oktober 2026</span>
            </div>
          </div>
        </a>
        <div class="news-side">
          <a href="#" class="news-item news-item-light" data-cat="berita">
            <div>
              <div class="news-meta">Berita</div>
              <h3>Vaksinasi DH Booster Digelar di Poskesdes</h3>
              <div class="news-meta">26 Okt 2026 · 09.00 WIB</div>
            </div>
          </a>
          <a href="#" class="news-item news-item-light" data-cat="agenda">
            <div>
              <div class="news-meta">Agenda</div>
              <h3>Gotong Royong Bersih Saluran Irigasi</h3>
              <div class="news-meta">01 Nov 2026 · 07.00 WIB</div>
            </div>
          </a>
          <a href="#" class="news-item news-item-light" data-cat="berita">
            <div>
              <div class="news-meta">Berita</div>
              <h3>Kelompok Tani Panen Raya, Hasil Naik 15%</h3>
              <div class="news-meta">24 Okt 2026 · Laporan Panen</div>
            </div>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Arsip Berita -->
  <section class="section section-tint">
    <div class="container">
      <div class="section-head-row" style="margin-bottom:8px">
        <div>
          <div class="eyebrow">Arsip</div>
          <h2>Arsip berita & agenda</h2>
        </div>
        <p class="page-hero-note">Daftar terbitan sebelumnya. Arsip lengkap tersedia di papan informasi kantor desa.</p>
      </div>
      <div class="card" style="padding:0 28px">
        <a href="#" class="news-item news-item-light" data-cat="berita">
          <div class="news-date">
            <div class="news-date-num">20</div>
            <div class="news-date-mon">OKT</div>
          </div>
          <div style="flex:1">
            <div class="news-meta">Berita</div>
            <h3>Perbaikan Jalan Lingkung Dusun Cimanggu Dimulai</h3>
            <div class="news-meta">Pemerintah Desa · 20 Okt 2026</div>
          </div>
        </a>
        <a href="#" class="news-item news-item-light" data-cat="agenda">
          <div class="news-date">
            <div class="news-date-num">14</div>
            <div class="news-date-mon">OKT</div>
          </div>
          <div style="flex:1">
            <div class="news-meta">Agenda</div>
            <h3>Posyandu Balita & Imamunisasi Bulan Oktober</h3>
            <div class="news-meta">Poskesdes · 14–15 Okt 2026</div>
          </div>
        </a>
        <a href="#" class="news-item news-item-light" style="border-bottom:none" data-cat="berita">
          <div class="news-date">
            <div class="news-date-num">05</div>
            <div class="news-date-mon">OKT</div>
          </div>
          <div style="flex:1">
            <div class="news-meta">Berita</div>
            <h3>Bantuan Bibit Kopi Disalurkan ke 120 Petani</h3>
            <div class="news-meta">Pemerintah Desa · 05 Okt 2026</div>
          </div>
        </a>
      </div>
    </div>
  </section>

  <!-- Pengumuman Resmi -->
  <section class="section" id="pengumuman" data-cat="pengumuman">
    <div class="container">
      <div class="section-head-row" style="margin-bottom:40px">
        <div>
          <div class="eyebrow">Pengumuman</div>
          <h2>Pengumuman resmi dari kantor desa</h2>
        </div>
        <p class="page-hero-note">Pengumuman bernomor surat resmi ditempel di papan desa dan dibacakan lewat pengeras masjid tiap Jumat.</p>
      </div>
      <div class="two-col">
        <div class="card">
          <div class="between" style="margin-bottom:16px">
            <h3>Penutupan pelayanan 17–19 November (libur tahsin)</h3>
            <span class="badge badge-amber">Segera</span>
          </div>
          <p class="muted small">Kantor desa tutup selama tiga hari. Pengajuan surat darurat dapat disampaikan lewat WhatsApp resmi desa dan akan diproses kembali mulai Kamis, 20 November 2026.</p>
          <div class="panel-link" style="margin-top:14px">Selengkapnya →</div>
        </div>
        <div class="card">
          <div class="between" style="margin-bottom:16px">
            <h3>Pengisian data keluarga (Prodeskel) mulai 10 November</h3>
            <span class="badge badge-blue">Penting</span>
          </div>
          <p class="muted small">Seluruh kepala keluarga diminta menyiapkan Kartu Keluarga dan KTP untuk pendataan ulang oleh petugas RT/RW, 10–30 November 2026.</p>
          <div class="panel-link" style="margin-top:14px">Selengkapnya →</div>
        </div>
      </div>
    </div>
  </section>

  {{-- footer dari partial --}}
@endsection
