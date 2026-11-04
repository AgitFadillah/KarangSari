@extends('layouts.public')

@section('title', 'Profil Desa · '.$profil->nama_desa)
@section('description', 'Mengenal '.$profil->nama_desa.': sejarah, visi & misi, struktur pemerintahan, serta wilayah dan geografis.')

@push('styles')
<style>
.profile-tabs {
      background: var(--white); border-top: 1px solid var(--gray-200); border-bottom: 1px solid var(--gray-200);
      position: sticky; top: 76px; z-index: 40;
    }
    .profile-tabs .container { display: flex; gap: 28px; overflow-x: auto; }
    .profile-tabs { position: sticky; }
    @media (max-width: 720px) {
      .profile-tabs .container { padding-right: 40px; gap: 22px; }
      .profile-tabs::after {
        content: ''; position: absolute; top: 0; right: 0; bottom: 0; width: 48px;
        background: linear-gradient(90deg, transparent, var(--white)); pointer-events: none;
      }
    }
    .profile-tabs a {
      padding: 16px 2px; font-size: .9375rem; font-weight: 500; color: var(--gray-600);
      text-decoration: none; white-space: nowrap; border-bottom: 2.5px solid transparent; margin-bottom: -1px;
    }
    .profile-tabs a:hover { color: var(--navy-900); }
    .profile-tabs a.active { font-weight: 700; color: var(--navy-900); border-bottom-color: var(--navy-900); }

    .stat-cards-2x2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .stat-mini {
      background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius-md);
      padding: 22px 24px;
    }
    .stat-mini-label { font-size: .6875rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--gray-500); margin-bottom: 8px; }
    .stat-mini-value { font-size: 1.5rem; font-weight: 800; color: var(--navy-900); line-height: 1.15; }
    .stat-mini-value small { font-size: .9rem; font-weight: 600; color: var(--gray-600); }

    .misi-list { display: flex; flex-direction: column; }
    .misi-item { display: flex; gap: 18px; padding: 16px 0; border-bottom: 1px solid var(--gray-200); align-items: flex-start; }
    .misi-item:last-child { border-bottom: none; }
    .misi-num { font-size: .8125rem; font-weight: 800; color: var(--blue-600); letter-spacing: .06em; padding-top: 2px; flex: none; }
    .misi-item p { font-size: .9375rem; color: var(--gray-900); }

    .data-note { font-size: .8125rem; color: var(--gray-500); margin-top: 20px; }
</style>
@endpush

@section('content')
{{-- topbar + header dari partial --}}

  <!-- Page Hero -->
  <section class="page-hero">
    <div class="container">
      <nav class="breadcrumb">
        <a href="{{ route('beranda') }}">Beranda</a>
        <span class="sep">/</span>
        <span>Profil Desa</span>
      </nav>
      <div class="page-hero-row">
        <div>
          <div class="eyebrow">Profil Desa</div>
          <h1>Mengenal {{ $profil->nama_desa }}, dari sejarah hingga wilayahnya.</h1>
        </div>
        <p class="page-hero-note">Informasi pada halaman ini masih berupa contoh sampai pemerintah desa menyerahkan profil resmi, logo, dan dokumentasi terbaru.</p>
      </div>
    </div>
  </section>

  <!-- Tab Navigasi -->
  <nav class="profile-tabs">
    <div class="container">
      <a href="#sejarah" class="active">Sejarah</a>
      <a href="#visi-misi">Visi &amp; Misi</a>
      <a href="#struktur">Struktur Pemerintahan</a>
      <a href="#wilayah">Wilayah &amp; Geografis</a>
    </div>
  </nav>

  <!-- Sejarah -->
  <section class="section" id="sejarah">
    <div class="container">
      <div class="eyebrow">Sejarah Desa</div>
      <h2 style="margin-bottom:48px">Tumbuh dari kebun dan musyawarah warga.</h2>
      <div class="two-col">
        <div class="stat-cards-2x2">
          <div class="stat-mini">
            <div class="stat-mini-label">Berdiri</div>
            <div class="stat-mini-value">1908</div>
          </div>
          <div class="stat-mini">
            <div class="stat-mini-label">Dusun</div>
            <div class="stat-mini-value">4</div>
          </div>
          <div class="stat-mini">
            <div class="stat-mini-label">Luas</div>
            <div class="stat-mini-value">1.284 <small>ha</small></div>
          </div>
          <div class="stat-mini">
            <div class="stat-mini-label">Ketinggian</div>
            <div class="stat-mini-value">900–1.240 <small>mdpl</small></div>
          </div>
        </div>
        <div>
          <p>{{ $profil->sejarah }}</p>
          <div class="quote">
            "Tanahnya subur, airnya mengalir sepanjang tahun. Yang perlu dijaga adalah cara kita mengelolanya bersama."
          </div>
          <p>Hingga hari ini, pertanian sayur, kopi, dan wisata alam menjadi tiga pilar penghidupan warga. Pemerintah desa terus mendorong pengelolaan potensi lokal yang berkelanjutan dan pelayanan administrasi yang lebih mudah diakses.</p>
          <p class="data-note">Narasi sejarah masih menunggu validasi dan dokumen resmi dari pemerintah desa.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Visi & Misi -->
  <section class="section section-tint" id="visi-misi">
    <div class="container">
      <div class="eyebrow">Visi &amp; Misi</div>
      <h2 style="margin-bottom:48px">Arah pembangunan desa.</h2>
      <div class="two-col">
        <div class="card" style="margin-top:0">
          <div class="eyebrow" style="margin-bottom:14px">Visi</div>
          <p style="font-size:1.15rem;font-weight:600;line-height:1.55;color:var(--navy-900)">{{ $profil->visi }}</p>
        </div>
        <div class="card" style="margin-top:0">
          <div class="eyebrow" style="margin-bottom:6px">Misi</div>
          <div class="misi-list">
            @foreach (preg_split('/\r\n|\r|\n/', (string) $profil->misi) as $i => $butir)
              @continue(trim($butir) === '')
              <div class="misi-item">
                <span class="misi-num">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                <p>{{ trim($butir) }}</p>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Struktur Pemerintahan -->
  <section class="section" id="struktur">
    <div class="container">
      <div class="eyebrow">Struktur Pemerintahan</div>
      <h2>Orang-orang yang melayani di kantor desa.</h2>
      <p class="lead" style="margin:16px 0 48px">Susunan nama dan jabatan berikut adalah ilustrasi sampai daftar resmi diterima dari pemerintah desa.</p>
      <div class="people-grid">
        <div class="person-card">
          <div class="avatar navy">AS</div>
          <div class="person-name">H. Asep Sunandar</div>
          <div class="person-role">Kepala Desa</div>
        </div>
        <div class="person-card">
          <div class="avatar">DR</div>
          <div class="person-name">Dewi Ratnasari</div>
          <div class="person-role">Sekretaris Desa</div>
        </div>
        <div class="person-card">
          <div class="avatar">RH</div>
          <div class="person-name">Rudi Hartono</div>
          <div class="person-role">Kasi Pemerintahan</div>
        </div>
        <div class="person-card">
          <div class="avatar">NY</div>
          <div class="person-name">Neng Yuliana</div>
          <div class="person-role">Kasi Kesejahteraan</div>
        </div>
        <div class="person-card">
          <div class="avatar">AG</div>
          <div class="person-name">Agus Setiawan</div>
          <div class="person-role">Kasi Pelayanan</div>
        </div>
        <div class="person-card">
          <div class="avatar">LS</div>
          <div class="person-name">Lilis Suryani</div>
          <div class="person-role">Kaur Keuangan</div>
        </div>
        <div class="person-card">
          <div class="avatar">BP</div>
          <div class="person-name">Bayu Permana</div>
          <div class="person-role">Kaur Umum</div>
        </div>
        <div class="person-card">
          <div class="avatar green">DM</div>
          <div class="person-name">Deden Mulyana</div>
          <div class="person-role">Ketua BPD</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Wilayah & Geografis -->
  <section class="section section-tint" id="wilayah">
    <div class="container">
      <div class="eyebrow">Wilayah &amp; Geografis</div>
      <h2 style="margin-bottom:48px">Batas wilayah dan penggunaan lahan.</h2>
      <div class="two-col">
        <div class="card" style="margin-top:0">
          <h3 style="margin-bottom:12px">Batas wilayah</h3>
          <dl class="dl-rows">
            <div class="dl-row">
              <dt>Utara</dt>
              <dd>Desa Cibeureum</dd>
            </div>
            <div class="dl-row">
              <dt>Timur</dt>
              <dd>Desa Tugu Selatan</dd>
            </div>
            <div class="dl-row">
              <dt>Selatan</dt>
              <dd>Kecamatan Megamendung</dd>
            </div>
            <div class="dl-row">
              <dt>Barat</dt>
              <dd>Desa Cilember</dd>
            </div>
          </dl>
          <p class="data-note">Luas wilayah 1.284 hektare, berada pada ketinggian 900–1.240 meter di atas permukaan laut.</p>
        </div>
        <div class="card" style="margin-top:0">
          <h3 style="margin-bottom:24px">Penggunaan lahan</h3>
          <div class="bars">
            <div>
              <div class="bar-row-top"><b>Kebun dan pertanian</b><span>58%</span></div>
              <div class="bar-track"><div class="bar-fill" data-width="58%"></div></div>
            </div>
            <div>
              <div class="bar-row-top"><b>Hutan lindung</b><span>27%</span></div>
              <div class="bar-track"><div class="bar-fill b2" data-width="27%"></div></div>
            </div>
            <div>
              <div class="bar-row-top"><b>Permukiman</b><span>11%</span></div>
              <div class="bar-track"><div class="bar-fill b3" data-width="11%"></div></div>
            </div>
            <div>
              <div class="bar-row-top"><b>Fasilitas umum</b><span>4%</span></div>
              <div class="bar-track"><div class="bar-fill b4" data-width="4%"></div></div>
            </div>
          </div>
          <p class="data-note">Angka penggunaan lahan adalah ilustrasi dan akan diperbarui setelah data resmi desa tersedia.</p>
        </div>
      </div>
    </div>
  </section>

  {{-- footer dari partial --}}
  
@endsection

@push('scripts')
<script>
// Active state untuk tab profil saat scroll
    document.addEventListener('DOMContentLoaded', () => {
      const links = document.querySelectorAll('.profile-tabs a');
      const sections = ['sejarah', 'visi-misi', 'struktur', 'wilayah']
        .map(id => document.getElementById(id)).filter(Boolean);
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => {
          if (e.isIntersecting) {
            links.forEach(l => l.classList.toggle('active', l.getAttribute('href') === '#' + e.target.id));
          }
        });
      }, { rootMargin: '-30% 0px -60% 0px' });
      sections.forEach(s => observer.observe(s));
      links.forEach(l => l.addEventListener('click', () => {
        links.forEach(x => x.classList.remove('active'));
        l.classList.add('active');
      }));
    });
</script>
@endpush
