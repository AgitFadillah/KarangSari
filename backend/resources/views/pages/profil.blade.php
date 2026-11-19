@extends('layouts.public')

@section('title', 'Profil Desa · '.$profil->nama_desa)
@section('description', 'Mengenal '.$profil->nama_desa.': sejarah, visi & misi, struktur pemerintahan, serta wilayah dan geografis.')

@push('styles')
<style>
.profile-tabs {
      background: var(--white); border-top: 1px solid var(--gray-200); border-bottom: 1px solid var(--gray-200);
      position: sticky; top: 76px; z-index: 40;
    }
    .profile-tabs .container {
      display: flex; gap: 28px; overflow-x: auto; scrollbar-width: none;
      -ms-overflow-style: none;
    }
    /* Batang gulir bar tab disembunyikan: sebelumnya overflow-x:auto
       menampilkan track + tombol panah abu-abu di ujung kanan yang terlihat
       seperti artefak, padahal isinya masih dapat digeser. */
    .profile-tabs .container::-webkit-scrollbar { display: none; width: 0; height: 0; }
    /* Bagian yang dituju tab tidak boleh tertutup bar tab yang lengket. */
    .profile-tabs ~ .section { scroll-margin-top: 130px; }
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
    /* Bila jumlah kartu ganjil, kartu terakhir dibiarkan mengisi satu baris
       penuh agar tidak menyisakan ruang kosong di sebelahnya. */
    .stat-cards-2x2 > .stat-mini:last-child:nth-child(odd) { grid-column: 1 / -1; }
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

    /* Narasi sejarah bisa panjang (beberapa paragraf). Diberi tipografi
       baca agar tidak menjadi satu blok teks raksasa tanpa jeda. */
    .sejarah-teks { color: var(--gray-900); font-size: 1rem; line-height: 1.8; }
    .sejarah-teks p + p { margin-top: 1.1em; }
    .sejarah-teks blockquote {
      border-left: 3px solid var(--blue-600); background: var(--blue-100);
      padding: 14px 20px; border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
      font-style: italic; color: var(--navy-900); margin: 1.2em 0;
    }

    /* Kartu angka demografi: otomatis mengisi baris tanpa menyisakan
       lubang bila jumlah kartunya bukan kelipatan kolom. */
    .demo-angka {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 16px;
    }

    /* Tabel data desa pada halaman publik. */
    .tabel-data { min-width: 0; }
    .tabel-data th.angka, .tabel-data td.angka { text-align: right; white-space: nowrap; }
    .tabel-data td, .tabel-data th { font-size: .9rem; }
    .card .table-wrap + .table-wrap { margin-top: 20px; }

    /* Daftar dusun bernomor. */
    .dusun-list {
      columns: 2; column-gap: 28px; margin: 0; padding-left: 20px;
      font-size: .9375rem; color: var(--gray-900); line-height: 1.9;
    }
    .dusun-list li { break-inside: avoid; }
    @media (max-width: 560px) { .dusun-list { columns: 1; } }

    /* Kartu di dalam kolom yang bertumpuk butuh jarak vertikal. */
    .two-col > div > .card + .card,
    .two-col > div > .card + div { margin-top: 24px; }
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
          @php $kontenProfil = \App\Support\KontenHalaman::ambil($kontens, 'profil'); @endphp
          <div class="eyebrow">Profil Desa</div>
          <h1>{{ \App\Support\KontenHalaman::judul($kontenProfil) ?? 'Mengenal '.$profil->nama_desa.', dari sejarah hingga wilayahnya.' }}</h1>
        </div>
        <p class="page-hero-note">{{ \App\Support\KontenHalaman::pengantar($kontenProfil, 300) ?? 'Sejarah, visi & misi, struktur pemerintahan, dan wilayah desa dikelola oleh perangkat desa melalui dashboard admin.' }}</p>
      </div>
    </div>
  </section>

  <!-- Tab Navigasi -->
  @php
    // Data tabel desa dikelompokkan per jenis; baris kosong tidak dirender
    // agar tabel tidak menampilkan baris tanpa angka.
    $umur = ($dataDesa[\App\Models\DataDesa::GOLONGAN_UMUR] ?? collect())->filter->adaIsi();
    $agama = ($dataDesa[\App\Models\DataDesa::AGAMA] ?? collect())->filter->adaIsi();
    $pendidikan = ($dataDesa[\App\Models\DataDesa::PENDIDIKAN] ?? collect())->filter->adaIsi();
    $pencaharian = ($dataDesa[\App\Models\DataDesa::PENCAHARIAN] ?? collect())->filter->adaIsi();
    $kesejahteraan = ($dataDesa[\App\Models\DataDesa::KESEJAHTERAAN] ?? collect())->filter->adaIsi();
    $riwayatKades = ($dataDesa[\App\Models\DataDesa::RIWAYAT_KADES] ?? collect())
      ->filter(fn ($baris) => filled($baris->label) || filled($baris->nilai));
    $dusun = $profil->dusun_tersusun;

    $adaDemografi = filled($profil->jumlah_penduduk) || filled($profil->jumlah_kk)
      || $umur->isNotEmpty() || $agama->isNotEmpty() || $pendidikan->isNotEmpty()
      || $pencaharian->isNotEmpty();
    $adaSosial = filled($profil->keadaan_sosial) || $kesejahteraan->isNotEmpty()
      || $riwayatKades->isNotEmpty() || $dusun !== [];
  @endphp

  <nav class="profile-tabs">
    <div class="container">
      <a href="#sejarah" class="active">Sejarah</a>
      <a href="#visi-misi">Visi &amp; Misi</a>
      <a href="#struktur">Struktur Pemerintahan</a>
      <a href="#wilayah">Wilayah &amp; Geografis</a>
      @if ($adaDemografi)
        <a href="#demografi">Demografi</a>
      @endif
      @if ($adaSosial)
        <a href="#keadaan-sosial">Keadaan Sosial</a>
      @endif
    </div>
  </nav>

  <!-- Sejarah -->
  <section class="section" id="sejarah">
    <div class="container">
      <div class="eyebrow">Sejarah Desa</div>
      <h2 style="margin-bottom:48px">Sejarah dan perkembangan {{ $profil->nama_desa }}.</h2>
      <div class="two-col">
        <div class="stat-cards-2x2">
          {{-- Kartu ringkas Sejarah diambil dari data profil desa (dokumen
               PROFIL desa.docx) lebih dulu, lalu dilengkapi statistik tambahan
               dari menu Statistik Beranda. Label yang sudah tampil dicatat agar
               statistik berlabel sama tidak muncul dua kali — dulu hal ini
               menghasilkan dua kartu "Berdiri" berdampingan. --}}
          @php
            $labelTampil = [];
            $kartuProfil = [];

            if (filled($profil->tahun_berdiri)) {
                $kartuProfil[] = ['Berdiri', $profil->tahun_berdiri, null];
            }
            if (filled($profil->jumlah_penduduk)) {
                $kartuProfil[] = ['Penduduk', $profil->jumlah_penduduk, 'jiwa'];
            }
            if ($profil->dusun_tersusun !== []) {
                $kartuProfil[] = ['Dusun', (string) count($profil->dusun_tersusun), null];
            }
            if (filled($profil->luas_wilayah)) {
                $kartuProfil[] = ['Luas wilayah', $profil->luas_wilayah, null];
            }
          @endphp
          @foreach ($kartuProfil as [$label, $nilai, $satuan])
            @php $labelTampil[] = mb_strtolower(trim($label)); @endphp
            <div class="stat-mini">
              <div class="stat-mini-label">{{ $label }}</div>
              <div class="stat-mini-value">{{ $nilai }} @if ($satuan)<small>{{ $satuan }}</small>@endif</div>
            </div>
          @endforeach
          @foreach ($statistiks as $statistik)
            @continue (in_array(mb_strtolower(trim((string) $statistik->label)), $labelTampil, true))
            @php $labelTampil[] = mb_strtolower(trim((string) $statistik->label)); @endphp
            <div class="stat-mini">
              <div class="stat-mini-label">{{ $statistik->label }}</div>
              <div class="stat-mini-value">{{ $statistik->nilai }} @if ($statistik->satuan)<small>{{ $statistik->satuan }}</small>@endif</div>
            </div>
          @endforeach
        </div>
        <div>
          @if (filled($profil->sejarah))
            {{-- Narasi sejarah bisa panjang dan tanpa baris baru. TeksBaca
                 memecahnya menjadi paragraf yang layak dibaca. --}}
            <div class="sejarah-teks">
              {!! \App\Support\TeksBaca::keHtml($profil->sejarah) !!}
            </div>
          @else
            <p class="muted">Narasi sejarah {{ $profil->nama_desa }} belum diisi.</p>
          @endif
          <p class="data-note">Narasi sejarah diisi oleh perangkat desa melalui dashboard admin.</p>
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
        <div class="card">
          <div class="eyebrow" style="margin-bottom:14px">Visi</div>
          <p style="font-size:1.15rem;font-weight:600;line-height:1.55;color:var(--navy-900)">{{ $profil->visi }}</p>
        </div>
        <div class="card">
          <div class="eyebrow" style="margin-bottom:6px">Misi</div>
          <div class="misi-list">
            @php
              // Nomor urut dihitung dari butir yang benar-benar tampil, bukan
              // dari indeks baris. Kalau memakai indeks, satu baris kosong di
              // tengah teks misi membuat penomoran melompat (01, 03, …).
              $nomorMisi = 0;
            @endphp
            @foreach (preg_split('/\r\n|\r|\n/', (string) $profil->misi) as $butir)
              @continue(trim($butir) === '')
              @php $nomorMisi++; @endphp
              <div class="misi-item">
                <span class="misi-num">{{ str_pad((string) $nomorMisi, 2, '0', STR_PAD_LEFT) }}</span>
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
      <p class="lead" style="margin:16px 0 48px">Susunan nama dan jabatan berikut dikelola oleh perangkat desa melalui dashboard admin.</p>

      @php
        // Puncak bagan adalah Kepala Desa. Bila jabatannya belum ditulis
        // dengan kata "kepala desa", entri pertama (urutan terkecil) dipakai
        // supaya bagan tetap punya satu pemimpin di atas.
        $puncak = $perangkats->first(fn ($orang) => \Illuminate\Support\Str::contains(
            \Illuminate\Support\Str::lower((string) $orang->jabatan),
            'kepala desa'
        )) ?? $perangkats->first();
        $anggota = $perangkats->reject(fn ($orang) => $puncak && $orang->is($puncak))->values();
      @endphp

      @if ($perangkats->isEmpty())
        <p class="muted">Data perangkat desa belum diisi.</p>
      @else
        {{-- Bagan organisasi: pemimpin di puncak, garis penghubung turun ke
             deretan perangkat. Di layar sempit bagan berubah menjadi daftar
             bertingkat bergaya direktori (garis di kiri, kartu bertumpuk)
             agar tetap terbaca tanpa menggeser layar ke samping. --}}
        <div class="org-chart">
          @php
            $fotoPuncak = \App\Support\BerkasPublik::ada($puncak->foto_path)
                ? \App\Support\BerkasPublik::url($puncak->foto_path)
                : null;
          @endphp
          <div class="org-puncak">
            <div class="person-card puncak">
              @if ($fotoPuncak)
                <img class="avatar avatar-foto" src="{{ $fotoPuncak }}"
                     alt="Foto {{ $puncak->nama }}" loading="lazy" width="88" height="88">
              @else
                <div class="avatar navy">{{ \App\Enums\RoleAdmin::initials($puncak->nama) }}</div>
              @endif
              <div class="person-teks">
                <div class="person-name">{{ $puncak->nama }}</div>
                <div class="person-role">{{ $puncak->jabatan }}</div>
                @if (filled($puncak->nip))
                  <div class="person-nip">NIP {{ $puncak->nip }}</div>
                @endif
              </div>
            </div>
          </div>

          @if ($anggota->isNotEmpty())
            <div class="org-cabang">
              @foreach ($anggota as $orang)
                @php
                  $fotoAnggota = \App\Support\BerkasPublik::ada($orang->foto_path)
                      ? \App\Support\BerkasPublik::url($orang->foto_path)
                      : null;
                @endphp
                <div class="org-simpul">
                  <div class="person-card">
                    @if ($fotoAnggota)
                      <img class="avatar avatar-foto" src="{{ $fotoAnggota }}"
                           alt="Foto {{ $orang->nama }}" loading="lazy" width="72" height="72">
                    @else
                      <div class="avatar">{{ \App\Enums\RoleAdmin::initials($orang->nama) }}</div>
                    @endif
                    <div class="person-teks">
                      <div class="person-name">{{ $orang->nama }}</div>
                      <div class="person-role">{{ $orang->jabatan }}</div>
                      @if (filled($orang->nip))
                        <div class="person-nip">NIP {{ $orang->nip }}</div>
                      @endif
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          @endif
        </div>
      @endif
    </div>
  </section>

  <!-- Wilayah & Geografis -->
  <section class="section section-tint" id="wilayah">
    <div class="container">
      <div class="eyebrow">Wilayah &amp; Geografis</div>
      <h2 style="margin-bottom:48px">Batas wilayah dan penggunaan lahan.</h2>

      {{-- Kondisi umum dan iklim berasal dari dokumen PROFIL desa dan diisi
           lewat menu Profil & Kontak. Bila belum diisi, kartunya tidak
           dirender supaya tidak ada kotak kosong. --}}
      @if (filled($profil->kondisi_umum) || filled($profil->iklim))
        <div class="two-col" style="margin-bottom:24px">
          @if (filled($profil->kondisi_umum))
            <div class="card">
              <h3 style="margin-bottom:12px">Kondisi umum desa</h3>
              <p>{{ $profil->kondisi_umum }}</p>
            </div>
          @endif
          @if (filled($profil->iklim))
            <div class="card">
              <h3 style="margin-bottom:12px">Iklim</h3>
              <p>{{ $profil->iklim }}</p>
            </div>
          @endif
        </div>
      @endif

      <div class="two-col">
        <div class="card">
          <h3 style="margin-bottom:12px">Batas wilayah</h3>
          <dl class="dl-rows">
            @forelse ($profil->batas_wilayah_tersusun as $arah => $tetangga)
              <div class="dl-row">
                <dt>{{ $arah }}</dt>
                <dd>{{ $tetangga }}</dd>
              </div>
            @empty
              <p class="muted small">Data batas wilayah belum diisi.</p>
            @endforelse
          </dl>
          <p class="data-note">Data batas wilayah dapat dilengkapi melalui dashboard admin.</p>
        </div>
        <div class="card">
          <h3 style="margin-bottom:24px">Penggunaan lahan</h3>
          <div class="bars">
            @forelse ($profil->penggunaan_lahan_tersusun as $lahan)
              <div>
                <div class="bar-row-top"><b>{{ $lahan['label'] }}</b><span>{{ $lahan['persen'] }}%</span></div>
                <div class="bar-track"><div @class(['bar-fill', 'b2' => $loop->index === 1, 'b3' => $loop->index === 2, 'b4' => $loop->index >= 3]) data-width="{{ $lahan['persen'] }}%"></div></div>
              </div>
            @empty
              <p class="muted small">Data penggunaan lahan belum diisi.</p>
            @endforelse
          </div>
          <p class="data-note">Perbarui angka penggunaan lahan melalui menu Profil &amp; Kontak di dashboard admin.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Demografi & Keadaan Sosial -->
  @if ($adaDemografi)
    <section class="section" id="demografi">
      <div class="container">
        <div class="eyebrow">Demografi</div>
        <h2 style="margin-bottom:16px">Jumlah dan sebaran penduduk.</h2>
        <p class="lead" style="max-width:640px;margin-bottom:40px">
          Angka kependudukan {{ $profil->nama_desa }} yang diperbarui oleh perangkat desa.
        </p>

        <div class="demo-angka">
          @if (filled($profil->jumlah_penduduk))
            <div class="stat-mini">
              <div class="stat-mini-label">Jumlah penduduk</div>
              <div class="stat-mini-value">{{ $profil->jumlah_penduduk }} <small>jiwa</small></div>
            </div>
          @endif
          @if (filled($profil->penduduk_laki_laki))
            <div class="stat-mini">
              <div class="stat-mini-label">Laki-laki</div>
              <div class="stat-mini-value">{{ $profil->penduduk_laki_laki }} <small>jiwa</small></div>
            </div>
          @endif
          @if (filled($profil->penduduk_perempuan))
            <div class="stat-mini">
              <div class="stat-mini-label">Perempuan</div>
              <div class="stat-mini-value">{{ $profil->penduduk_perempuan }} <small>jiwa</small></div>
            </div>
          @endif
          @if (filled($profil->jumlah_kk))
            <div class="stat-mini">
              <div class="stat-mini-label">Kepala keluarga</div>
              <div class="stat-mini-value">{{ $profil->jumlah_kk }} <small>KK</small></div>
            </div>
          @endif
          @if (filled($profil->luas_wilayah))
            <div class="stat-mini">
              <div class="stat-mini-label">Luas wilayah</div>
              <div class="stat-mini-value">{{ $profil->luas_wilayah }}</div>
            </div>
          @endif
        </div>

        @if (filled($profil->jarak_provinsi) || filled($profil->jarak_kabupaten) || filled($profil->jarak_kecamatan))
          <div class="card" style="margin-top:24px">
            <h3 style="margin-bottom:16px">Jarak tempuh ke ibu kota</h3>
            <dl class="dl-rows">
              @if (filled($profil->jarak_provinsi))
                <div class="dl-row"><dt>Ibu kota provinsi</dt><dd>{{ $profil->jarak_provinsi }}</dd></div>
              @endif
              @if (filled($profil->jarak_kabupaten))
                <div class="dl-row"><dt>Ibu kota kabupaten</dt><dd>{{ $profil->jarak_kabupaten }}</dd></div>
              @endif
              @if (filled($profil->jarak_kecamatan))
                <div class="dl-row"><dt>Ibu kota kecamatan</dt><dd>{{ $profil->jarak_kecamatan }}</dd></div>
              @endif
            </dl>
          </div>
        @endif

        <div class="two-col" style="margin-top:24px">
          @if ($umur->isNotEmpty())
            <div class="card">
              <h3 style="margin-bottom:20px">Menurut golongan umur</h3>
              <div class="table-wrap">
                <table class="data tabel-data">
                  <thead>
                    <tr>
                      <th>Golongan umur</th>
                      <th class="angka">L</th>
                      <th class="angka">P</th>
                      <th class="angka">Jumlah</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($umur as $baris)
                      <tr>
                        <td>{{ $baris->label }}</td>
                        <td class="angka">{{ $baris->kolomTampil($baris->nilai_l) }}</td>
                        <td class="angka">{{ $baris->kolomTampil($baris->nilai_p) }}</td>
                        <td class="angka"><b>{{ $baris->jumlahTampil() }}</b></td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          @endif

          @if ($agama->isNotEmpty() || $pendidikan->isNotEmpty())
            <div>
              @if ($agama->isNotEmpty())
                <div class="card">
                  <h3 style="margin-bottom:20px">Menurut agama</h3>
                  <div class="table-wrap">
                    <table class="data tabel-data">
                      <thead><tr><th>Agama</th><th class="angka">Jumlah</th></tr></thead>
                      <tbody>
                        @foreach ($agama as $baris)
                          <tr><td>{{ $baris->label }}</td><td class="angka">{{ $baris->nilaiTampil() }}</td></tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                </div>
              @endif

              @if ($pendidikan->isNotEmpty())
                <div class="card">
                  <h3 style="margin-bottom:20px">Menurut tingkat pendidikan</h3>
                  <div class="table-wrap">
                    <table class="data tabel-data">
                      <thead><tr><th>Tingkat pendidikan</th><th class="angka">Jumlah</th></tr></thead>
                      <tbody>
                        @foreach ($pendidikan as $baris)
                          <tr><td>{{ $baris->label }}</td><td class="angka">{{ $baris->nilaiTampil() }}</td></tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                </div>
              @endif
            </div>
          @endif
        </div>

        @if ($pencaharian->isNotEmpty())
          <div class="card" style="margin-top:24px">
            <h3 style="margin-bottom:20px">Menurut mata pencaharian</h3>
            <div class="table-wrap">
              <table class="data tabel-data">
                <thead><tr><th>Mata pencaharian</th><th class="angka">Jumlah</th></tr></thead>
                <tbody>
                  @foreach ($pencaharian as $baris)
                    <tr><td>{{ $baris->label }}</td><td class="angka">{{ $baris->nilaiTampil() }}</td></tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        @endif
      </div>
    </section>
  @endif

  @if ($adaSosial)
    <section class="section section-tint" id="keadaan-sosial">
      <div class="container">
        <div class="eyebrow">Keadaan Sosial</div>
        <h2 style="margin-bottom:40px">Kehidupan sosial dan kesejahteraan warga.</h2>

        <div class="two-col">
          <div>
            @if (filled($profil->keadaan_sosial))
              <div class="card">
                <div class="sejarah-teks">{!! \App\Support\TeksBaca::keHtml($profil->keadaan_sosial) !!}</div>
              </div>
            @endif

            @if ($dusun !== [])
              <div class="card">
                <h3 style="margin-bottom:16px">Dusun di {{ $profil->nama_desa }}</h3>
                <ol class="dusun-list">
                  @foreach ($dusun as $namaDusun)
                    <li>{{ $namaDusun }}</li>
                  @endforeach
                </ol>
              </div>
            @endif
          </div>

          <div>
            @if ($kesejahteraan->isNotEmpty())
              <div class="card">
                <h3 style="margin-bottom:20px">Tingkat kesejahteraan masyarakat</h3>
                <div class="table-wrap">
                  <table class="data tabel-data">
                    <thead><tr><th>Uraian</th><th class="angka">Jumlah</th></tr></thead>
                    <tbody>
                      @foreach ($kesejahteraan as $baris)
                        <tr>
                          <td>{{ $baris->label }}</td>
                          <td class="angka">{{ $baris->nilaiTampil() }} {{ $baris->keterangan }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            @endif

            @if ($riwayatKades->isNotEmpty())
              <div class="card">
                <h3 style="margin-bottom:20px">Riwayat kepala desa</h3>
                <div class="table-wrap">
                  <table class="data tabel-data tabel-riwayat">
                    <thead><tr><th>Nama</th><th>Masa jabatan</th><th>Keterangan</th></tr></thead>
                    <tbody>
                      @foreach ($riwayatKades as $baris)
                        <tr>
                          <td data-label="Nama"><div class="cell-main">{{ $baris->label }}</div></td>
                          <td data-label="Masa jabatan">{{ $baris->nilai ?: '—' }}</td>
                          <td data-label="Keterangan">{{ $baris->keterangan ?: '—' }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            @endif
          </div>
        </div>
      </div>
    </section>
  @endif

  {{-- footer dari partial --}}
  
@endsection

@push('scripts')
<script>
// Active state untuk tab profil saat scroll
    document.addEventListener('DOMContentLoaded', () => {
      const links = document.querySelectorAll('.profile-tabs a');
      const sections = ['sejarah', 'visi-misi', 'struktur', 'wilayah', 'demografi', 'keadaan-sosial']
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
