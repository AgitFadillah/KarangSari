@extends('layouts.public')

@section('title', $berita->judul.' · '.$profil->nama_desa)
@section('description', \Illuminate\Support\Str::limit(strip_tags($berita->ringkasan ?: $berita->isi), 155))

@push('styles')
<style>
/* Halaman baca: lebar nyaman ±68 karakter agar mudah dibaca petugas
   maupun warga di layar kecil. */
.baca-wrap { max-width: 760px; margin: 0 auto; }
.baca-meta {
  display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
  color: var(--gray-600); font-size: .875rem;
}
.baca-meta .dot { width: 4px; height: 4px; border-radius: 50%; background: var(--gray-500); }

/* Isi artikel: tipografi yang layak baca, bukan blok teks polos. */
.baca-isi { color: var(--gray-900); font-size: 1.0625rem; line-height: 1.8; }
.baca-isi > * + * { margin-top: 1.15em; }
.baca-isi p { margin: 0; }
.baca-isi h2, .baca-isi h3, .baca-isi h4 {
  color: var(--navy-900); line-height: 1.3; margin-top: 1.9em; margin-bottom: .55em;
}
.baca-isi h2 { font-size: 1.4rem; }
.baca-isi h3 { font-size: 1.2rem; }
.baca-isi h4 { font-size: 1.05rem; }
.baca-isi ul, .baca-isi ol { padding-left: 1.4em; }
.baca-isi li + li { margin-top: .45em; }
.baca-isi strong, .baca-isi b { color: var(--navy-900); font-weight: 700; }
.baca-isi a { color: var(--blue-600); text-decoration: underline; text-underline-offset: 2px; }
.baca-isi blockquote {
  border-left: 4px solid var(--navy-900); background: var(--blue-100);
  padding: 16px 22px; border-radius: 0 var(--radius-md) var(--radius-md) 0;
  font-style: italic; color: var(--navy-900);
}
.baca-isi img { border-radius: var(--radius-md); width: 100%; height: auto; }
.baca-isi hr { border: none; border-top: 1px solid var(--gray-200); margin: 2em 0; }

/* Sorotan pembuka */
.baca-lead {
  font-size: 1.125rem; line-height: 1.7; color: var(--navy-900); font-weight: 600;
  border-left: 3px solid var(--blue-600); padding-left: 18px;
}

/* Kotak berbagi tautan */
.baca-bagi {
  display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
  border-top: 1px solid var(--gray-200); margin-top: 40px; padding-top: 22px;
}
.baca-bagi span { font-size: .875rem; font-weight: 600; color: var(--gray-600); }

/* Kartu berita terkait */
.terkait-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
@media (max-width: 860px) { .terkait-grid { grid-template-columns: 1fr; } }
.terkait-kartu {
  display: block; padding: 22px 24px; background: var(--white);
  border: 1px solid #D8E2EC; border-radius: var(--radius-md);
  text-decoration: none; color: inherit;
  transition: border-color .18s, box-shadow .18s;
}
.terkait-kartu:hover {
  border-color: var(--blue-500);
  box-shadow: 0 6px 22px -8px rgba(46, 109, 164, .34);
  text-decoration: none;
}
.terkait-kartu:hover h3 { color: var(--blue-600); }
.terkait-kartu h3 { font-size: 1rem; margin-top: 10px; color: var(--navy-900); transition: color .15s; }
</style>
@endpush

@section('content')
  <!-- Page Hero -->
  <section class="page-hero">
    <div class="container">
      <nav class="breadcrumb">
        <a href="{{ route('beranda') }}">Beranda</a>
        <span class="sep">/</span>
        <a href="{{ route('informasi') }}">Informasi</a>
        <span class="sep">/</span>
        <span>{{ $berita->labelKategori() }}</span>
      </nav>
      <div class="baca-wrap">
        <div class="page-hero-row">
          <div>
            <div class="baca-meta" style="margin-bottom:16px">
              <span class="badge badge-blue">{{ $berita->labelKategori() }}</span>
              <span>{{ $berita->terbit_at->translatedFormat('j F Y') }}</span>
              <span class="dot"></span>
              <span>{{ $berita->penulis ?: 'Pemerintah Desa' }}</span>
            </div>
            <h1>{{ $berita->judul }}</h1>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Isi berita -->
  <section class="section" style="padding-top:56px">
    <div class="container">
      <div class="baca-wrap">
        @if (filled($berita->ringkasan))
          <p class="baca-lead" style="margin-bottom:32px">{{ $berita->ringkasan }}</p>
        @endif

        @if (filled($berita->isi))
          {{--
            Isi berita diisi perangkat desa lewat editor dashboard admin.
            Hanya tag teks dasar yang dibiarkan, sehingga aman ditampilkan
            mentah tanpa membuka celah skrip.

            Bila isi ditulis sebagai satu blok tanpa baris baru (mis. hasil
            tempel dari Word), paragrafnya dipecah otomatis agar tidak tampil
            sebagai tembok teks raksasa.
          --}}
          <div class="baca-isi">
            {!! \App\Support\TeksBaca::keHtml((string) $berita->isi) !!}
          </div>
        @else
          <div class="notice notice-blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
            <span>Isi lengkap untuk {{ strtolower($berita->labelKategori()) }} ini belum diisi oleh perangkat desa.</span>
          </div>
        @endif

        <div class="baca-bagi">
          <span>Bagikan:</span>
          <a class="btn btn-outline btn-sm"
             href="https://wa.me/?text={{ urlencode($berita->judul.' — '.request()->url()) }}"
             target="_blank" rel="noopener">WhatsApp</a>
          <a class="btn btn-outline btn-sm" href="{{ route('informasi') }}">← Semua informasi</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Berita terkait -->
  @if ($terkait->isNotEmpty())
    <section class="section section-tint">
      <div class="container">
        <div class="baca-wrap">
          <div class="eyebrow">Lainnya</div>
          <h2 style="margin-bottom:28px">{{ $berita->labelKategori() }} lainnya</h2>
          <div class="terkait-grid">
            @foreach ($terkait as $lain)
              <a href="{{ route('informasi.detail', $lain->slug) }}" class="terkait-kartu">
                <div class="news-meta">{{ $lain->terbit_at->translatedFormat('d M Y') }}</div>
                <h3>{{ $lain->judul }}</h3>
              </a>
            @endforeach
          </div>
        </div>
      </div>
    </section>
  @endif
@endsection
