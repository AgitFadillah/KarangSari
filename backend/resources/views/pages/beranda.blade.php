@extends('layouts.public')

@section('title', $profil->nama_desa.' · Layanan Digital Resmi')
@section('description', 'Website resmi '.$profil->nama_desa.'. Ajukan surat dari rumah, cek status, lihat potensi desa.')

@push('styles')
<style>
/* Seksi kontak beranda */
    .hc-card {
      display: grid; grid-template-columns: 1fr 1fr;
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: var(--radius-lg); overflow: hidden;
    }
    .hc-info { padding: 44px 48px; }
    .hc-eyebrow {
      font-size: .75rem; font-weight: 800; letter-spacing: .14em;
      text-transform: uppercase; color: var(--blue-600);
    }
    .hc-info h2 { font-size: 1.75rem; margin-top: 10px; }
    .hc-lead { color: var(--gray-600); font-size: .9375rem; margin: 10px 0 28px; max-width: 44ch; }
    .hc-row { display: flex; gap: 14px; align-items: flex-start; margin-bottom: 20px; }
    .hc-ic {
      width: 40px; height: 40px; border-radius: var(--radius-sm); flex: none;
      display: grid; place-items: center;
    }
    .hc-ic svg { width: 19px; height: 19px; }
    .hc-ic.blue { background: var(--blue-100); color: var(--blue-600); }
    .hc-ic.green { background: var(--green-100); color: var(--green-600); }
    .hc-k {
      font-size: .6875rem; font-weight: 800; letter-spacing: .1em;
      text-transform: uppercase; color: var(--gray-500); margin-bottom: 3px;
    }
    .hc-v { font-size: .9375rem; font-weight: 600; color: var(--navy-900); line-height: 1.5; }
    .hc-btns { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 28px; }

    .hc-map {
      position: relative; display: flex; flex-direction: column;
      min-height: 430px; background: #DCE9F5;
    }
    .hc-map iframe { display: block; width: 100%; flex: 1; min-height: 360px; border: 0; }
    .hc-map-bar {
      display: flex; align-items: center; justify-content: space-between; gap: 14px;
      padding: 14px 18px; background: var(--white); border-top: 1px solid var(--gray-200);
      flex-wrap: wrap;
    }
    .hc-tooltip { min-width: 0; }
    .hc-tooltip b { display: block; font-size: .875rem; color: var(--navy-900); }
    .hc-tooltip span { display: block; font-size: .75rem; color: var(--gray-500); margin-top: 2px; }
    .hc-maps {
      display: inline-flex; align-items: center; gap: 8px; flex: none;
      background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius-sm);
      padding: 9px 16px; font-size: .8125rem; font-weight: 700; color: var(--navy-900);
      box-shadow: var(--shadow-sm); text-decoration: none;
    }
    .hc-maps:hover { box-shadow: var(--shadow-md); text-decoration: none; border-color: var(--blue-300, #B9D3EA); }
    .hc-maps svg { width: 14px; height: 14px; }

    @media (max-width: 900px) {
      .hc-card { grid-template-columns: 1fr; }
      .hc-info { padding: 30px 26px; }
      .hc-map { min-height: 0; }
      .hc-map iframe { min-height: 280px; flex: none; height: 280px; }
    }

    /* Hero */
    .hero { padding-top: 76px; }
    .hero p.lead { max-width: 512px; }
    .hero-checks { gap: 24px; }
    .hero-check { font-size: .8125rem; gap: 8px; }

    /* Kartu contoh surat */
    .doc-stack { max-width: 440px; margin-left: auto; }
    .doc-card { padding: 22px 24px 26px; }
    .doc-head { border-bottom: 2px solid var(--navy-900); padding-bottom: 14px; }
    .doc-inst .brand-mark { width: 32px; height: 32px; }
    .doc-inst .brand-mark svg { width: 16px; height: 16px; }
    .doc-inst-name { font-size: .5625rem; letter-spacing: .04em; }
    .pg-draft {
      background: var(--white); border: 1px solid var(--gray-200); color: var(--gray-600);
      font-size: .625rem; padding: 4px 10px; text-transform: uppercase; letter-spacing: .08em;
    }
    .doc-title {
      font-size: .8125rem; letter-spacing: .1em; text-transform: uppercase;
      text-decoration: underline; text-underline-offset: 5px; margin-top: 18px;
    }
    .doc-number { font-size: .6875rem; margin-bottom: 14px; }
    .doc-body { font-size: .6875rem; }
    .pg-doc-rows {
      background: var(--gray-100); border-radius: var(--radius-sm);
      padding: 8px 12px; margin: 10px 0;
    }
    .pg-doc-rows .doc-row { grid-template-columns: 1fr auto; gap: 16px; padding: 5px 0; }
    .pg-doc-rows .doc-row .v { text-align: right; }
    .doc-sign { font-size: .6875rem; margin-top: 18px; }
    .doc-stamp {
      width: 64px; height: 64px; border: 1.5px solid rgba(46,109,164,.35);
      font-size: .4375rem; letter-spacing: .06em; right: 22px; bottom: 22px;
    }
    .doc-toast { left: -16px; bottom: -22px; padding: 8px 16px 8px 8px; gap: 10px; }
    .doc-toast-icon { width: 30px; height: 30px; }
    .doc-toast-icon svg { width: 16px; height: 16px; }
    .doc-toast-title { font-size: .8125rem; }
    .doc-toast-sub { font-size: .6875rem; }

    /* Sekilas desa: statistik selebar kontainer */
    .pg-stats {
      border: 1px solid var(--gray-200); border-top: 1px solid var(--gray-200);
      border-radius: var(--radius-lg); margin-top: 56px; padding: 0; overflow: hidden;
    }
    .pg-stats .stat { padding: 24px 26px; }
    .pg-stats .stat:first-child { padding-left: 26px; }
    .pg-stats .stat-value { display: inline; font-size: 1.75rem; }
    .pg-stats .stat-unit {
      display: inline; font-size: .8125rem; font-weight: 600;
      color: var(--gray-500); margin-left: 6px;
    }

    /* ---------- Sekilas Desa ----------
       Dua kolom: narasi di kiri, kisi angka di kanan. Sebelumnya kelas
       .sk-* tidak punya aturan sama sekali sehingga blok angka menumpuk
       di atas tombol CTA. */
    .sk-grid {
      display: grid;
      grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr);
      gap: 56px; align-items: center;
    }
    .sk-left { min-width: 0; }

    /* Visi desa pada beranda: kalimat kutipan, sengaja lebih kecil dari
       judul halaman supaya tidak mendominasi seksi. Visi yang panjang
       dipotong agar tetap terbaca sebagai ringkasan, bukan tembok teks. */
    .sk-visi {
      font-size: clamp(1.15rem, 1.9vw, 1.4rem);
      font-weight: 600; line-height: 1.5; letter-spacing: -.01em;
      color: var(--navy-900);
      border-left: 3px solid var(--blue-600);
      padding-left: 20px;
      /* Batasi tinggi agar visi super panjang tidak mendorong tombol CTA. */
      display: -webkit-box; -webkit-line-clamp: 5; -webkit-box-orient: vertical;
      overflow: hidden;
    }

    /* Kolom kanan Sekilas Desa: foto desa (bila ada) di atas kartu angka. */
    .sk-kanan {
      display: flex; flex-direction: column; gap: 16px;
      min-width: 0;
    }

    .sk-foto {
      margin: 0;
      border-radius: var(--radius-lg); overflow: hidden;
      border: 1px solid var(--gray-200);
      box-shadow: 0 14px 38px -18px rgba(11, 31, 58, .35);
      background: var(--navy-800);
    }
    .sk-foto img {
      display: block; width: 100%; height: auto;
      aspect-ratio: 16 / 9; object-fit: cover;
    }
    .sk-foto figcaption {
      display: flex; flex-wrap: wrap; align-items: baseline; gap: 4px 10px;
      padding: 14px 20px 16px;
      background: var(--navy-900);
    }
    .sk-foto figcaption b { font-size: .9375rem; color: var(--white); }
    .sk-foto figcaption span { font-size: .8125rem; color: rgba(255, 255, 255, .72); }

    .sk-stats {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
      min-width: 0;
    }    .sk-stat {
      background: var(--white);
      border: 1px solid #D8E2EC;
      border-radius: var(--radius-md);
      padding: 22px 24px;
      box-shadow: 0 1px 2px rgba(11, 31, 58, .05);
      display: flex; flex-direction: column; gap: 2px;
      transition: border-color .18s ease, box-shadow .18s ease;
    }
    .sk-stat:hover {
      border-color: var(--blue-500);
      box-shadow: 0 6px 22px -10px rgba(46, 109, 164, .38);
    }
    .sk-stat-value {
      font-size: 1.75rem; font-weight: 800; line-height: 1.1;
      color: var(--navy-900); letter-spacing: -.02em;
      word-break: break-word;
    }
    .sk-stat-label {
      font-size: .6875rem; font-weight: 700; letter-spacing: .1em;
      text-transform: uppercase; color: var(--gray-500);
      margin-top: 8px;
    }
    .sk-stat-unit { font-size: .8125rem; color: var(--gray-600); }

    /* Paragraf narasi di bawah kisi: batasi lebar agar tidak melebar penuh. */
    .sk-narasi {
      margin-top: 40px;
      font-size: 1rem; line-height: 1.8; color: var(--gray-600);
      max-width: 78ch;
    }

    @media (max-width: 1024px) {
      .sk-grid { grid-template-columns: 1fr; gap: 32px; }
    }
    @media (max-width: 480px) {
      .sk-stats { grid-template-columns: 1fr; }
      .sk-stat { padding: 18px 20px; }
    }

    /* Pelayanan: dua kolom */
    .pg-serv { display: grid; grid-template-columns: 1.25fr 2fr; gap: 64px; align-items: start; }
    @media (max-width: 1024px) { .pg-serv { grid-template-columns: 1fr; gap: 40px; } }

    /* Alur pengajuan */
    .pg-alur .step:first-child::after {
      content: ''; position: absolute; top: 25px; left: 52px; right: -28px;
      height: 2px; background: var(--navy-900); border-radius: 2px;
    }

    /* Informasi desa */
    .pg-info-head {
      border-bottom: 1px solid rgba(255,255,255,.12);
      padding-bottom: 24px; margin-bottom: 40px;
    }

    /* Potensi */
    .pg-pot .potensi-card:nth-child(2) { background: var(--gray-100); }
    .pg-pot .potensi-card:nth-child(2) .badge {
      background: var(--white); border: 1px solid var(--gray-200); color: var(--navy-900);
    }
    .potensi-card:not(.potensi-card-dark) h3 { color: var(--navy-900); }
    .pg-pot .potensi-card-dark .badge { background: rgba(255,255,255,.12); color: var(--white); }
    .pg-pot .potensi-owner { font-weight: 700; justify-content: space-between; gap: 12px; }
    .pg-pot .potensi-card-dark .potensi-owner {
      border-top: 1px solid rgba(255,255,255,.14); padding-top: 18px;
      font-weight: 500; color: rgba(255,255,255,.72);
    }
    .pg-pot .potensi-card-dark svg.art {
      width: 170px; height: 170px; top: -44px; right: -34px;
      color: rgba(255,255,255,.14);
    }

    /* Pengumuman + agenda */
    .pg-col-head {
      display: flex; justify-content: space-between; align-items: flex-end; gap: 24px;
      border-bottom: 1px solid var(--gray-200); padding-bottom: 16px;
    }
    .pg-col-head h2 { font-size: clamp(1.6rem, 3vw, 2.25rem); }
    .pg-ann-item {
      display: flex; align-items: center; gap: 14px; padding: 16px 0;
      border-bottom: 1px solid var(--gray-200); text-decoration: none; color: inherit;
    }
    .pg-ann-item:hover { text-decoration: none; }
    .pg-ann-item:hover .pg-ann-title { color: var(--blue-600); }
    .pg-dot { width: 9px; height: 9px; border-radius: 50%; flex: none; background: #A9CBE8; }
    .pg-dot.red { background: var(--red-600); }
    .pg-ann-body { flex: 1; }
    .pg-ann-title { font-weight: 700; font-size: .9375rem; color: var(--navy-900); transition: color .15s; }
    .pg-ann-item .news-meta { margin-top: 4px; }
    .pg-ann-arrow { color: #C3CCD8; font-size: 1rem; flex: none; }

    .pg-agenda-list { display: flex; flex-direction: column; gap: 16px; margin-top: 24px; }
    .pg-agenda-item {
      display: flex; align-items: center; gap: 15px; padding: 15px 16px;
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: var(--radius-md); box-shadow: var(--shadow-sm);
      text-decoration: none; color: inherit;
    }
    .pg-agenda-item:hover { text-decoration: none; box-shadow: var(--shadow-md); }
    .pg-agenda-date {
      width: 55px; height: 55px; flex: none; border-radius: var(--radius-sm);
      background: var(--blue-100); display: grid; place-content: center; text-align: center;
    }
    .pg-agenda-date .d { font-size: 1.25rem; font-weight: 800; color: var(--navy-900); line-height: 1; }
    .pg-agenda-date .m { font-size: .625rem; font-weight: 700; letter-spacing: .06em; color: var(--blue-600); margin-top: 4px; }
    .pg-agenda-item:first-child .pg-agenda-date { background: var(--navy-900); }
    .pg-agenda-item:first-child .pg-agenda-date .d { color: var(--white); }
    .pg-agenda-item:first-child .pg-agenda-date .m { color: var(--blue-500); }
    .pg-agenda-title { font-weight: 700; font-size: .9375rem; color: var(--navy-900); }
    .pg-agenda-meta { font-size: .8125rem; color: var(--gray-500); margin-top: 3px; }

    /* Kontak beranda */

    /* Footer: judul kolom tanpa huruf kapital */
    .footer-col h4 { text-transform: none; letter-spacing: 0; font-size: .9375rem; margin-bottom: 16px; }
    .footer-col li { margin-bottom: 16px; }

    /* ---------- Pengumuman + Agenda: kolom sejajar ----------
       Judul kiri ("Pengumuman resmi dari kantor desa") membungkus ke dua
       baris sedangkan judul kanan hanya satu baris. Tanpa penyeimbang,
       kartu kanan turun dan tidak rata dengan kartu kiri. Area judul
       diberi tinggi minimum yang sama supaya kedua kartu selalu mulai
       pada garis yang sama; pada layar kecil tumpukan dibuat wajar. */
    .dua-kolom-sejajar { align-items: stretch; }

    .kolom-seksi {
      display: flex;
      flex-direction: column;
      min-width: 0;
    }

    .kolom-kepala {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-bottom: 24px;
      /* Ruang untuk dua baris judul h2 pada ukuran terbesar. Judul yang
         lebih pendek tetap sejajar karena area ini sama tingginya. */
      min-height: 148px;
    }

    .kolom-kepala h2 {
      /* Batasi ukuran agar judul tidak terlalu mendominasi blok kartu. */
      font-size: clamp(1.5rem, 2.6vw, 2rem);
      line-height: 1.2;
      margin: 0;
    }

    .kolom-isi {
      flex: 1 1 auto;
      margin-top: 0;
    }

    @media (max-width: 1024px) {
      .doc-stack { max-width: 440px; margin: 0 auto; }

      /* Saat kolom menumpuk, tinggi minimum judul tidak lagi relevan
         dan justru menyisakan ruang kosong. */
      .kolom-kepala { min-height: 0; margin-bottom: 18px; }
    }
</style>
@endpush

@section('content')
{{-- topbar + header dari partial --}}

  <!-- Hero -->
  <section class="hero">
    <div class="container">
      <div class="hero-body">
        <div class="reveal visible">
          @php $kontenBeranda = \App\Support\KontenHalaman::ambil($kontens, 'beranda'); @endphp
          <div class="eyebrow">Pemerintah {{ $profil->nama_desa }}</div>
          <h1>{{ \App\Support\KontenHalaman::judul($kontenBeranda) ?? 'Layanan desa, kini bisa dimulai dari rumah.' }}</h1>
          <p class="lead">{!! nl2br(\App\Support\TeksTampil::e(\App\Support\KontenHalaman::pengantar($kontenBeranda, 400) ?? 'Ajukan surat keterangan, pantau perkembangan pengajuanmu, dan temukan informasi desa, semua tanpa harus datang dan antre berkali-kali di kantor desa.')) !!}</p>
          <div class="hero-actions">
            <a href="{{ route('pelayanan') }}" class="btn btn-white">
              Ajukan surat sekarang
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
            </a>
            <a href="{{ route('pelayanan') }}" class="btn btn-outline-light">Lihat jenis layanan</a>
          </div>
          <div class="hero-checks">
            <div class="hero-check">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm-2 15-5-5 1.4-1.4L10 14.2l7.6-7.6L19 8l-9 9z"/></svg>
              Tanpa akun
            </div>
            <div class="hero-check">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm-2 15-5-5 1.4-1.4L10 14.2l7.6-7.6L19 8l-9 9z"/></svg>
              Nomor referensi otomatis
            </div>
            <div class="hero-check">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm-2 15-5-5 1.4-1.4L10 14.2l7.6-7.6L19 8l-9 9z"/></svg>
              Diinformasikan via WhatsApp
            </div>
          </div>
        </div>

        <div class="hero-visual reveal visible">
          {{-- Kartu contoh surat tetap menjadi visual hero. Foto desa
               unggahan admin ditempatkan di seksi "Sekilas Desa" di bawah,
               sesuai permintaan perangkat desa. --}}
          <div class="doc-stack">
            <div class="doc-shadow"></div>
            <div class="doc-card">
              <div class="doc-head">
                <div class="doc-inst">
                  <div class="brand-mark">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M4 18h16M6 18v-8h3v8M11 18v-8h3v8M16 18v-8h3v8M12 3l9 5H3l9-5z"/></svg>
                  </div>
                  <div class="doc-inst-name">Pemerintah {{ $profil->nama_desa }}<br>Kecamatan {{ $profil->kecamatan }} · {{ $profil->kabupaten }}</div>
                </div>
                <span class="badge badge-gray">Draft</span>
              </div>
              <div class="doc-title">Surat Keterangan Domisili</div>
              <div class="doc-number">Nomor: {{ config('desa.kode_surat') }}/____/{{ date('Y') }}</div>
              <div class="doc-body">
                <p style="margin-bottom:12px">Yang bertanda tangan di bawah ini menerangkan bahwa:</p>
                <div class="doc-row"><span class="l">Nama</span><span>:</span><span class="v">Nama Pemohon</span></div>
                <div class="doc-row"><span class="l">NIK</span><span>:</span><span class="v">16 digit sesuai KTP</span></div>
                <div class="doc-row"><span class="l">Alamat</span><span>:</span><span class="v">Alamat lengkap pemohon</span></div>
                <p style="margin-top:14px">Benar yang bersangkutan berdomisili di wilayah {{ $profil->nama_desa }}. Surat ini dibuat untuk melengkapi berkas yang diperlukan.</p>
              </div>
              <div class="doc-sign">
                <div>Ditetapkan di {{ $profil->kabupaten }}<br>{{ now()->translatedFormat('j F Y') }}<br><span class="doc-sign-name">{{ $kepalaDesa?->nama ?? 'Kepala '.$profil->nama_desa }}</span><br>Kepala {{ $profil->nama_desa }}</div>
                <div class="doc-stamp">Lembar Kesatu<br>Pemerintah Desa</div>
              </div>
            </div>

            <div class="doc-toast">
              <div class="doc-toast-icon">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>
              </div>
              <div>
                <div class="doc-toast-title">Pengajuan diterima</div>
                <div class="doc-toast-sub">Ref: {{ config('desa.prefix_referensi') }}-{{ date('Y') }}-0001</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Quick Links -->
  <div class="container">
    <div class="quicklinks reveal visible">
      <div class="quicklinks-grid">
        <a href="{{ route('profil') }}" class="quicklink">
          <div class="quicklink-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M4 18h16M6 18v-8h3v8M11 18v-8h3v8M16 18v-8h3v8M12 3l9 5H3l9-5z"/></svg>
          </div>
          <div>
            <div class="quicklink-title">Profil desa
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
            </div>
            <div class="quicklink-sub">Sejarah, visi, dan wilayah</div>
          </div>
        </a>
        <a href="{{ route('informasi') }}" class="quicklink">
          <div class="quicklink-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h13a3 3 0 0 1 3 3v13H7a3 3 0 0 1-3-3V4z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
          </div>
          <div>
            <div class="quicklink-title">Berita & agenda
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
            </div>
            <div class="quicklink-sub">Kabar terbaru dari desa</div>
          </div>
        </a>
        <a href="{{ route('potensi') }}" class="quicklink">
          <div class="quicklink-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22v-8M12 14c-4 0-7-3-7-8 5 0 7 3 7 8zM12 14c4 0 7-3 7-8-5 0-7 3-7 8z"/></svg>
          </div>
          <div>
            <div class="quicklink-title">Potensi desa
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
            </div>
            <div class="quicklink-sub">UMKM, wisata, pertanian</div>
          </div>
        </a>
        <a href="{{ route('kontak') }}" class="quicklink">
          <div class="quicklink-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.1-7-11a7 7 0 0 1 14 0c0 4.9-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
          </div>
          <div>
            <div class="quicklink-title">Kontak & lokasi
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
            </div>
            <div class="quicklink-sub">Alamat dan jam pelayanan</div>
          </div>
        </a>
      </div>
    </div>
  </div>

  <!-- Sekilas Desa -->
  <section class="section">
    <div class="container">
      <div class="sk-grid">
        <div class="sk-left">
          <div class="eyebrow">Sekilas Desa</div>
          @if (filled($profil->visi))
            {{-- Visi desa adalah kalimat visi, bukan judul halaman:
                 tampil sebagai teks kutipan besar, bukan <h2>. --}}
            <p class="sk-visi">{{ $profil->visi }}</p>
          @else
            <h2>Profil {{ $profil->nama_desa }} akan dilengkapi oleh perangkat desa.</h2>
          @endif
          <a href="{{ route('profil') }}" class="btn btn-primary" style="margin-top:28px">
            Kenali {{ $profil->nama_desa }}
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
          </a>
        </div>
        <div class="sk-kanan">
          @php $fotoBeranda = $profil->fotoBerandaUrl(); @endphp
          @if ($fotoBeranda)
            {{-- Foto desa hasil unggahan perangkat desa (menu Profil &
                 Kontak). Tampil di atas kartu angka sebagai pengenal visual
                 desa; tidak ditampilkan bila belum ada foto. --}}
            <figure class="sk-foto">
              <img src="{{ $fotoBeranda }}" alt="Foto {{ $profil->nama_desa }}"
                   loading="lazy" width="720" height="400">
              <figcaption>
                <b>{{ $profil->nama_desa }}</b>
                <span>Kecamatan {{ $profil->kecamatan }} · {{ $profil->kabupaten }}</span>
              </figcaption>
            </figure>
          @endif

          <div class="sk-stats">
            @forelse ($statistiks as $statistik)
              <div class="sk-stat">
                <div class="sk-stat-value"><span data-counter="{{ $statistik->nilai }}">{{ $statistik->nilai }}</span></div>
                <div class="sk-stat-label">{{ $statistik->label }}</div>
                @if ($statistik->satuan)
                  <div class="sk-stat-unit">{{ $statistik->satuan }}</div>
                @endif
              </div>
            @empty
              <div class="sk-stat" style="grid-column:1/-1">
                <div class="sk-stat-label">Belum ada angka</div>
                <div class="sk-stat-unit">
                  Angka ringkas desa (penduduk, KK, luas wilayah) diisi perangkat desa
                  melalui menu <b>Statistik Beranda</b> di dashboard admin.
                </div>
              </div>
            @endforelse
          </div>
        </div>
      </div>

      @if (filled($profil->sejarah))
        {{-- Narasi sejarah bisa panjang; tampil dengan tipografi baca
             (lebar terbatas + jarak baris), bukan satu blok raksasa. --}}
        <p class="sk-narasi">{{ \Illuminate\Support\Str::limit(strip_tags($profil->sejarah), 420) }}</p>
      @endif
    </div>
  </section>

  <!-- Pelayanan Administrasi -->
  <section class="section section-tint">
    <div class="container">
      <div class="section-head-row" style="margin-bottom:48px">
        <div>
          <div class="eyebrow">Pelayanan</div>
          <h2>Surat keterangan yang bisa diajukan hari ini</h2>
        </div>
        <p class="page-hero-note">{{ $layananRingkas->count() }} layanan yang paling sering diajukan. Masing-masing punya syarat khusus, tapi cukup KTP dan KK untuk memulai.</p>
      </div>
      <div class="service-list">
        @foreach ($layananRingkas as $layanan)
          <a href="{{ route('layanan.detail', $layanan->slug) }}" class="service-row">
            <span class="service-code" aria-hidden="true">{{ $layanan->kode }}</span>
            <div class="service-body">
              <div class="service-name">{{ $layanan->nama }}</div>
              <div class="service-desc">{{ $layanan->deskripsi }}</div>
            </div>
            <div class="service-meta">
              <span class="service-chip">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                {{ $layanan->estimasi_hari ?: 'Sesuai antrean' }}
              </span>
              <span class="service-chip muted">{{ $layanan->persyaratan_count ?? 0 }} persyaratan</span>
            </div>
            <span class="service-arrow" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
            </span>
          </a>
        @endforeach
      </div>
      <div style="text-align:center;margin-top:40px">
        <a href="{{ route('pelayanan') }}" class="btn btn-primary">Lihat semua jenis layanan</a>
      </div>
    </div>
  </section>

  <!-- Alur Pengajuan -->
  <section class="section">
    <div class="container">
      <div class="section-head center" style="margin-bottom:56px">
        <div class="eyebrow" style="justify-content:center">Alur Pengajuan</div>
        <h2>Cukup 4 langkah, tanpa akun</h2>
        <p class="lead" style="margin:16px auto 0;max-width:560px">Setelah mengirim, simpan nomor referensi dan kode akses yang tampil di layar sebagai bukti pengajuan. Cek status kapan saja lewat halaman ini.</p>
      </div>
      <div class="steps">
        <div class="step">
          <div class="step-num">1</div>
          <h3>Pilih layanan</h3>
          <p>Klik jenis surat yang dibutuhkan dari katalog layanan.</p>
        </div>
        <div class="step">
          <div class="step-num">2</div>
          <h3>Isi formulir</h3>
          <p>Isi data diri, alamat, dan kartu identitas (KTP & KK).</p>
        </div>
        <div class="step">
          <div class="step-num">3</div>
          <h3>Dapatkan nomor</h3>
          <p>Simpan nomor referensi dan kode akses yang tampil di layar.</p>
        </div>
        <div class="step">
          <div class="step-num">4</div>
          <h3>Cek status</h3>
          <p>Masukkan nomor referensi dan kode akses untuk melihat progres surat.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Informasi Desa (dark) -->
  @if ($beritaUtama)
  <section class="section section-dark">
    <div class="container">
      <div class="section-head-row" style="margin-bottom:48px">
        <div>
          <div class="eyebrow">Informasi Desa</div>
          <h2>Kabar terbaru dari {{ $profil->nama_desa }}</h2>
        </div>
        <a href="{{ route('informasi') }}" class="btn btn-outline-light btn-sm">Semua berita & agenda</a>
      </div>
      {{-- Bila belum ada berita lain, kolom kanan tidak dirender dan kartu
           utama melebar penuh agar tidak menyisakan setengah halaman kosong. --}}
      <div @class(['news-grid', 'news-grid-tunggal' => $beritaSamping->isEmpty()])>
        <a href="{{ route('informasi.detail', $beritaUtama->slug) }}" class="news-feature">
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
        @if ($beritaSamping->isNotEmpty())
          <div class="news-side">
            @foreach ($beritaSamping as $berita)
              <a href="{{ route('informasi.detail', $berita->slug) }}" class="news-item">
                <div>
                  <div class="news-meta">{{ $berita->labelKategori() }}</div>
                  <h3>{{ $berita->judul }}</h3>
                  <div class="news-meta">{{ $berita->terbit_at->translatedFormat('d M · H:i') }} WIB</div>
                </div>
              </a>
            @endforeach
            <div style="margin-top:auto;padding-top:20px">
              <a href="{{ route('informasi') }}" class="panel-link" style="color:var(--blue-500)">Lihat arsip informasi →</a>
            </div>
          </div>
        @endif
      </div>
    </div>
  </section>
  @endif

  <!-- Potensi Desa -->
  <section class="section">
    <div class="container">
      <div class="section-head-row" style="margin-bottom:48px">
        <div>
          <div class="eyebrow">Potensi</div>
          <h2>Kekayaan {{ $profil->nama_desa }}</h2>
          <p class="lead" style="margin-top:10px">Potensi unggulan {{ $profil->nama_desa }} pada sektor ekonomi, wisata, dan kerajinan warga.</p>
        </div>
        <a href="{{ route('potensi') }}" class="btn btn-outline btn-sm">Jelajah potensi lengkap</a>
      </div>
      <div class="potensi-grid">
        @foreach ($potensis as $index => $potensi)
          <a href="{{ route('potensi') }}" @class(['potensi-card', 'potensi-card-dark' => $index === 0])>
            <svg class="art" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M12 22v-8M12 14c-4 0-7-3-7-8 5 0 7 3 7 8zM12 14c4 0 7-3 7-8-5 0-7 3-7 8z"/></svg>
            <span class="badge badge-dark">{{ $potensi->kategori ?: 'Potensi' }}</span>
            <h3>{{ $potensi->nama }}</h3>
            <p>{{ $potensi->deskripsi }}</p>
            @if ($potensi->kontak)
              <div class="potensi-owner">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/></svg>
                {{ $potensi->kontak }}
              </div>
            @endif
          </a>
        @endforeach
      </div>
    </div>
  </section>

  <!-- Pengumuman + Agenda -->
  <section class="section section-tint">
    <div class="container">
      {{--
        Dua kolom sejajar. Judul di kiri bisa lebih panjang dan membungkus
        ke baris kedua, sehingga kartu di kanan tadinya turun tidak rata.
        Tiap kolom dibuat flex dengan area judul ber-tinggi minimum sama,
        jadi kartu pengumuman dan agenda selalu mulai sejajar.
      --}}
      <div class="two-col dua-kolom-sejajar">
        <div class="kolom-seksi">
          <div class="kolom-kepala">
            <div class="eyebrow">Pengumuman</div>
            <h2>Pengumuman resmi dari kantor desa</h2>
          </div>
          <div class="card kolom-isi">
            @forelse ($pengumumans as $pengumuman)
              <div class="@if (! $loop->first) card @endif" style="@if ($loop->first) padding-top:0 @endif">
                <div class="between" style="margin-bottom:16px">
                  <h3>{{ $pengumuman->judul }}</h3>
                  <span class="badge badge-amber">{{ $pengumuman->terbit_at->translatedFormat('d M') }}</span>
                </div>
                <p class="muted small">{{ $pengumuman->ringkasan }}</p>
                <a href="{{ route('informasi.detail', $pengumuman->slug) }}" class="panel-link" style="margin-top:14px">Selengkapnya →</a>
              </div>
            @empty
              <p class="muted small">Belum ada pengumuman.</p>
            @endforelse
          </div>
        </div>
        <div class="kolom-seksi">
          <div class="kolom-kepala">
            <div class="eyebrow">Agenda</div>
            <h2>Kegiatan terdekat</h2>
          </div>
          <div class="card kolom-isi">
            @forelse ($agendas as $agenda)
              <a href="{{ route('informasi.detail', $agenda->slug) }}" class="news-item news-item-light" style="border-bottom:none;padding:0 0 18px">
                <div class="news-date">
                  <div class="news-date-num">{{ $agenda->terbit_at->format('d') }}</div>
                  <div class="news-date-mon">{{ strtoupper($agenda->terbit_at->translatedFormat('M')) }}</div>
                </div>
                <div style="flex:1">
                  <h3>{{ $agenda->judul }}</h3>
                  <div class="news-meta">{{ $agenda->ringkasan }}</div>
                </div>
              </a>
            @empty
              <p class="muted small">Belum ada agenda.</p>
            @endforelse
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Kontak -->
  <section class="section" style="padding-top:48px">
    <div class="container">
      <div class="hc-card">
        <div class="hc-info">
          <div class="hc-eyebrow">Kontak</div>
          <h2>Datang atau hubungi kami</h2>
          <p class="hc-lead">Petugas desa siap membantu pada jam pelayanan. Untuk pertanyaan singkat, WhatsApp menjadi jalur tercepat.</p>

          <div class="hc-row">
            <div class="hc-ic blue">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.1-7-11a7 7 0 0 1 14 0c0 4.9-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
            </div>
            <div>
              <div class="hc-k">Alamat</div>
              <div class="hc-v">{{ $profil->alamat_kantor }}</div>
            </div>
          </div>

          <div class="hc-row">
            <div class="hc-ic blue">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </div>
            <div>
              <div class="hc-k">Jam pelayanan</div>
              <div class="hc-v">{!! nl2br(\App\Support\TeksTampil::e($profil->jam_layanan ?: 'Senin–Kamis 08.00–15.00, Jumat 08.00–11.30')) !!}</div>
            </div>
          </div>

          <div class="hc-row">
            <div class="hc-ic green">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.4 8.4 0 0 1-8.5 8.4 8.6 8.6 0 0 1-3.8-.9L3 21l2-5.4a8.3 8.3 0 0 1-1-4A8.4 8.4 0 0 1 12.5 3.2 8.4 8.4 0 0 1 21 11.5z"/></svg>
            </div>
            <div>
              <div class="hc-k">Telepon &amp; WhatsApp</div>
              @php $kontakDesa = collect([$profil->telepon, $profil->whatsapp])->filter(fn ($nilai) => filled($nilai)); @endphp
              @if ($kontakDesa->isNotEmpty())
                <div class="hc-v">{{ $kontakDesa->implode(' · ') }}</div>
              @else
                <div class="hc-v muted">Belum diisi oleh perangkat desa</div>
              @endif
            </div>
          </div>

          <div class="hc-btns">
            @if ($profil->nomorWhatsapp())
              <a href="https://wa.me/{{ $profil->nomorWhatsapp() }}" target="_blank" rel="noopener" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.4 8.4 0 0 1-8.5 8.4 8.6 8.6 0 0 1-3.8-.9L3 21l2-5.4a8.3 8.3 0 0 1-1-4A8.4 8.4 0 0 1 12.5 3.2 8.4 8.4 0 0 1 21 11.5z"/></svg>
                Chat WhatsApp desa
              </a>
            @endif
            <a href="{{ route('kontak') }}" class="btn btn-outline">Lihat halaman kontak</a>
          </div>
        </div>

        <div class="hc-map">
          <iframe
            src="{{ $profil->embedPeta() }}"
            title="Peta lokasi Kantor {{ $profil->nama_desa }}"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            allowfullscreen></iframe>
          <div class="hc-map-bar">
            <div class="hc-tooltip">
              <b>Kantor {{ $profil->nama_desa }}</b>
              <span>{{ $profil->alamat_kantor }}</span>
            </div>
            <a class="hc-maps" href="{{ $profil->tautanPeta() }}" target="_blank" rel="noopener">
              Buka di Google Maps
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6M10 14L21 3"/></svg>
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- footer dari partial --}}
@endsection
