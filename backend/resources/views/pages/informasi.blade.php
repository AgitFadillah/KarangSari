@extends('layouts.public')

@section('title', 'Informasi Desa · '.$profil->nama_desa)
@section('description', 'Berita, agenda, dan pengumuman resmi Pemerintah '.$profil->nama_desa.', Kecamatan '.$profil->kecamatan.', '.$profil->kabupaten.'.')

@push('styles')
<style>
  /* Pil kategori: ditulis sebagai tautan agar tetap bekerja tanpa JavaScript
     (server menyaring berdasarkan ?kategori=). JavaScript hanya mempercepat
     perpindahan tanpa memuat ulang halaman. */
  .tabs .tab {
    text-decoration: none; display: inline-flex; align-items: center; gap: 7px;
  }
  .tabs .tab:focus-visible { outline: 2px solid var(--blue-600); outline-offset: 2px; }
  .tabs .tab .tab-jumlah {
    font-size: .75rem; font-weight: 700; line-height: 1;
    padding: 4px 7px; border-radius: var(--radius-full);
    background: var(--gray-200); color: var(--gray-700);
  }
  .tabs .tab.active .tab-jumlah { background: rgba(255,255,255,.22); color: #fff; }

  /* Jarak antara deretan pil dan bagian pertama dipersempit: hero sudah
     memberi padding bawah 64px, ditambah padding atas .section 96px membuat
     hamparan kosong ±160px terlihat seperti bagian yang gagal dimuat. */
  .page-hero + .section { padding-top: 40px; }
  .page-hero + .section.section-tint { padding-top: 40px; }

  /* Bila hanya ada satu pengumuman, kartunya melebar penuh supaya tidak
     menyisakan separuh halaman kosong di sebelahnya. */
  .info-satu { grid-template-columns: 1fr; }
</style>
@endpush

@section('content')
{{-- topbar + header dari partial --}}

@php
  // Filter aktif datang dari server (?kategori=…), sehingga tautan pil dapat
  // dibuka langsung, dibagikan, dan tetap bekerja bila JavaScript dimatikan.
  $aktifKategori = $aktifKategori ?? 'semua';

  // Bagian mana yang perlu dirender untuk filter aktif.
  $tampil = fn (string $kategori): bool => $aktifKategori === 'semua' || $aktifKategori === $kategori;

  $beritaUtama = $beritas->first();
  $beritaSamping = $beritas->slice(1, 3);

  // Arsip memuat terbitan yang belum tampil di blok utama + samping.
  // Batasnya dihitung dari jumlah yang benar-benar tampil, bukan angka
  // tetap, supaya arsip tidak selalu kosong saat berita masih sedikit.
  $sudahTampil = ($beritaUtama ? 1 : 0) + $beritaSamping->count();
  $arsip = $beritas->slice($sudahTampil);

  $jumlahKategori = $jumlahKategori ?? [
    'semua' => $beritas->count() + $agendas->count() + $pengumumans->count(),
    'berita' => $beritas->count(),
    'agenda' => $agendas->count(),
    'pengumuman' => $pengumumans->count(),
  ];

  $pil = [
    'semua' => 'Semua',
    'berita' => 'Berita',
    'agenda' => 'Agenda',
    'pengumuman' => 'Pengumuman',
  ];

  $adaIsi = $jumlahKategori[$aktifKategori] > 0;
@endphp

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
          @php $kontenInformasi = \App\Support\KontenHalaman::ambil($kontens, 'informasi'); @endphp
          <div class="eyebrow">Informasi Desa</div>
          <h1>{{ \App\Support\KontenHalaman::judul($kontenInformasi) ?? 'Kabar, pengumuman, dan kegiatan warga.' }}</h1>
          <p class="lead" style="margin-top:14px;max-width:560px">{{ \App\Support\KontenHalaman::pengantar($kontenInformasi) ?? 'Semua informasi resmi '.$profil->nama_desa.' dipublikasikan di halaman ini.' }}</p>
        </div>
      </div>
      <div class="tabs" style="margin-top:32px" data-info-tabs role="group" aria-label="Saring informasi">
        @foreach ($pil as $kunci => $label)
          <a href="{{ route('informasi', $kunci === 'semua' ? [] : ['kategori' => $kunci]) }}"
             @class(['tab', 'active' => $aktifKategori === $kunci])
             data-filter="{{ $kunci }}"
             @if ($aktifKategori === $kunci) aria-current="true" @endif>
            {{ $label }}
            <span class="tab-jumlah">{{ $jumlahKategori[$kunci] }}</span>
          </a>
        @endforeach
      </div>
    </div>
  </section>

  @unless ($adaIsi)
    <section class="section">
      <div class="container">
        <div class="notice notice-blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
          <span>
            Belum ada {{ $aktifKategori === 'semua' ? 'informasi' : $pil[$aktifKategori] }} yang diterbitkan.
            <a href="{{ route('informasi') }}">Tampilkan semua informasi</a>.
          </span>
        </div>
      </div>
    </section>
  @endunless

  <!-- Sorotan (banner besar) -->
  @if ($sorotan && $tampil($sorotan->kategori))
    <section class="section" style="padding-top:0" data-bagian="sorotan">
      <div class="container">
        <a href="{{ route('informasi.detail', $sorotan->slug) }}" class="card" style="display:block;text-decoration:none;margin-top:40px;padding:44px 40px;background:linear-gradient(135deg,var(--blue-600),var(--blue-500));color:#fff;border:none">
          <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:16px">
            <span class="badge badge-amber">Sorotan</span>
            <span class="badge badge-navy" style="background:rgba(255,255,255,.18)">{{ $sorotan->labelKategori() }}</span>
            <span style="font-size:.8rem;opacity:.85">{{ $sorotan->terbit_at->translatedFormat('j F Y') }}</span>
          </div>
          <h2 style="color:#fff;font-size:clamp(1.5rem,3.4vw,2.25rem);line-height:1.2;margin:0 0 14px">{{ $sorotan->judul }}</h2>
          @if ($sorotan->ringkasan)
            <p style="color:rgba(255,255,255,.9);max-width:720px;margin:0;font-size:1rem;line-height:1.7">{{ $sorotan->ringkasan }}</p>
          @endif
          <div style="margin-top:20px;font-weight:600;font-size:.9rem">Baca selengkapnya →</div>
        </a>
      </div>
    </section>
  @endif

  <!-- Berita Utama + Side -->
  @if ($tampil('berita') && $beritas->isNotEmpty())
    <section class="section" style="padding-top:56px" id="arsip" data-bagian="berita">
      <div class="container">
        <div @class(['news-grid', 'news-grid-tunggal' => $beritaSamping->isEmpty()])>
          <a href="{{ route('informasi.detail', $beritaUtama->slug) }}" class="news-feature" style="text-decoration:none" data-cat="berita">
            <div class="news-feature-img">
              <svg class="art" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M3 21h18M4 18h16M6 18v-8h3v8M11 18v-8h3v8M16 18v-8h3v8M12 3l9 5H3l9-5z"/></svg>
              <span class="badge badge-navy" style="background:var(--blue-500)">Berita Utama</span>
            </div>
            <div class="news-feature-body">
              <h3>{{ $beritaUtama->judul }}</h3>
              <p>{{ $beritaUtama->ringkasan }}</p>
              <div class="news-meta">
                <span>{{ $beritaUtama->penulis ?: 'Pemerintah Desa' }}</span><span class="dot"></span><span>{{ $beritaUtama->terbit_at->translatedFormat('j F Y') }}</span>
              </div>
            </div>
          </a>
          <div class="news-side">
            @forelse ($beritaSamping as $berita)
              <a href="{{ route('informasi.detail', $berita->slug) }}" class="news-item news-item-light" data-cat="{{ $berita->kategori }}">
                <div>
                  <div class="news-meta">{{ $berita->labelKategori() }}</div>
                  <h3>{{ $berita->judul }}</h3>
                  <div class="news-meta">{{ $berita->terbit_at->translatedFormat('d M Y · H:i') }} WIB</div>
                </div>
              </a>
            @empty
              {{-- Hanya ada satu berita: kartu utama melebar penuh dan
                   keterangan ini mengisi kolom kanan agar tidak kosong. --}}
              <div class="news-side-kosong">
                <b>Belum ada berita lain</b>
                <span>Berita, pengumuman, dan agenda baru akan tampil di sini setelah perangkat desa menerbitkannya.</span>
                <a href="{{ route('panduan') }}" class="btn btn-outline btn-sm" style="margin-top:14px">Panduan layanan warga</a>
              </div>
            @endforelse
          </div>
        </div>
      </div>
    </section>
  @endif

  <!-- Arsip Berita -->
  @if ($tampil('berita') && $beritas->isNotEmpty())
    <section class="section section-tint" data-bagian="campuran">
      <div class="container">
        <div class="section-head-row" style="margin-bottom:8px">
          <div>
            <div class="eyebrow">Arsip</div>
            <h2>Arsip berita &amp; agenda</h2>
          </div>
          <p class="page-hero-note">Daftar terbitan sebelumnya. Arsip lengkap tersedia di papan informasi kantor desa.</p>
        </div>
        <div class="card" style="padding:0 28px">
          @forelse ($arsip as $berita)
            <a href="{{ route('informasi.detail', $berita->slug) }}" @class(['news-item', 'news-item-light', 'last' => $loop->last]) style="{{ $loop->last ? 'border-bottom:none' : '' }}" data-cat="{{ $berita->kategori }}">
              <div class="news-date">
                <div class="news-date-num">{{ $berita->terbit_at->format('d') }}</div>
                <div class="news-date-mon">{{ strtoupper($berita->terbit_at->translatedFormat('M')) }}</div>
              </div>
              <div style="flex:1">
                <div class="news-meta">{{ $berita->labelKategori() }}</div>
                <h3>{{ $berita->judul }}</h3>
                <div class="news-meta">{{ $berita->penulis ?: 'Pemerintah Desa' }} · {{ $berita->terbit_at->translatedFormat('d M Y') }}</div>
              </div>
            </a>
          @empty
            {{-- Semua terbitan sudah tampil di atas. Beri keterangan yang jelas
                 alih-alih kalimat "tidak ada arsip" yang membingungkan. --}}
            <p class="muted small" style="padding:20px 0">
              @if ($beritas->isNotEmpty())
                Seluruh terbitan saat ini sudah ditampilkan di bagian atas halaman.
                Arsip akan bertambah saat perangkat desa menerbitkan berita baru.
              @else
                Belum ada berita yang diterbitkan. Perangkat desa dapat menambah berita
                melalui menu <b>Berita &amp; Agenda</b> di dashboard admin.
              @endif
            </p>
          @endforelse
        </div>
      </div>
    </section>
  @endif

  <!-- Agenda -->
  @if ($tampil('agenda') && $agendas->isNotEmpty())
    <section class="section" data-bagian="agenda">
      <div class="container">
        <div class="section-head-row" style="margin-bottom:40px">
          <div>
            <div class="eyebrow">Agenda</div>
            <h2>Kegiatan terdekat</h2>
          </div>
        </div>
        <div class="card" style="padding:0 28px">
          @foreach ($agendas as $agenda)
            <a href="{{ route('informasi.detail', $agenda->slug) }}" @class(['news-item', 'news-item-light']) style="{{ $loop->last ? 'border-bottom:none' : '' }}" data-cat="agenda">
              <div class="news-date">
                <div class="news-date-num">{{ $agenda->terbit_at->format('d') }}</div>
                <div class="news-date-mon">{{ strtoupper($agenda->terbit_at->translatedFormat('M')) }}</div>
              </div>
              <div style="flex:1">
                <h3>{{ $agenda->judul }}</h3>
                <div class="news-meta">{{ $agenda->ringkasan }}</div>
              </div>
            </a>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  <!-- Pengumuman Resmi -->
  @if ($tampil('pengumuman'))
    <section class="section section-tint" id="pengumuman" data-bagian="pengumuman">
      <div class="container">
        <div class="section-head-row" style="margin-bottom:40px">
          <div>
            <div class="eyebrow">Pengumuman</div>
            <h2>Pengumuman resmi dari kantor desa</h2>
          </div>
          <p class="page-hero-note">Pengumuman resmi ditempel di papan desa dan dibacakan lewat pengeras masjid tiap Jumat.</p>
        </div>
        <div @class(['two-col', 'info-satu' => $pengumumans->count() === 1])>
          @forelse ($pengumumans as $pengumuman)
            <a href="{{ route('informasi.detail', $pengumuman->slug) }}" class="card" style="text-decoration:none;color:inherit" data-cat="pengumuman">
              <div class="between" style="margin-bottom:16px">
                <h3>{{ $pengumuman->judul }}</h3>
                <span class="badge badge-amber">{{ $pengumuman->terbit_at->translatedFormat('d M') }}</span>
              </div>
              <p class="muted small">{{ $pengumuman->ringkasan }}</p>
              <div class="panel-link" style="margin-top:14px">Selengkapnya →</div>
            </a>
          @empty
            <p class="muted">Belum ada pengumuman resmi.</p>
          @endforelse
        </div>
      </div>
    </section>
  @endif

  {{-- footer dari partial --}}
@endsection
