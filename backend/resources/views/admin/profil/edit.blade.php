@extends('layouts.admin')

@section('title', 'Profil & Kontak · Dashboard Admin '.$profil->nama_desa)

@push('styles')
<style>
.preview-box {
      display: flex; gap: 14px; align-items: center;
      border: 1px solid var(--gray-200); border-radius: var(--radius-md);
      padding: 16px;
    }
    .preview-box .brand-mark { width: 46px; height: 46px; flex: none; }
    .preview-box b { display: block; font-size: .9375rem; color: var(--navy-900); line-height: 1.35; }
    .preview-box span { font-size: .8125rem; color: var(--gray-500); }
    .preview-box img { width: 46px; height: 46px; object-fit: contain; flex: none; }

    .pub-link {
      display: flex; justify-content: space-between; align-items: center; gap: 12px;
      border: 1px solid var(--gray-200); border-radius: var(--radius-sm);
      padding: 13px 16px; margin-bottom: 10px;
      font-size: .9375rem; font-weight: 600; color: var(--navy-900); text-decoration: none;
      transition: border-color .15s ease, background .15s ease;
    }
    .pub-link:last-child { margin-bottom: 0; }
    .pub-link:hover { border-color: var(--navy-900); background: var(--gray-100); text-decoration: none; }
    .pub-link svg { width: 16px; height: 16px; color: var(--gray-500); flex: none; }

    /* Narasi profil: tiap kolom diberi jarak yang sama dan tinggi berbeda
       sesuai panjang isi, supaya tidak terlihat sempit atau tidak rata. */
    .panel textarea {
      min-height: 0;
      line-height: 1.7;
      padding: 13px 15px;
    }
    .panel textarea + .field-help { margin-top: 8px; }

    /* Jarak antar kolom di panel narasi.
       HANYA field yang menjadi anak langsung panel: field di dalam
       .form-grid sudah diberi jarak oleh "gap" grid. Sebelumnya aturan ini
       memakai pemilih keturunan (.panel .field + .field) sehingga kolom
       KEDUA pada setiap baris grid ikut terdorong 20px ke bawah — itulah
       sebab "Kabupaten" dan "Kode pos" tampak turun dibanding kolom kirinya. */
    .panel > .field + .field { margin-top: 20px; }
    .panel > .field > label { margin-bottom: 9px; }

    /* Tautan hapus foto desa: sengaja kecil dan berwarna peringatan, agar
       tidak tertekan tanpa sengaja seperti tombol biasa. */
    .tautan-hapus-foto {
      font-size: .8125rem; font-weight: 600;
      color: #a3413b; text-decoration: underline;
      text-underline-offset: 3px;
    }
    .tautan-hapus-foto:hover { color: #8a322d; }

    /* Tabel isian data desa (golongan umur, agama, dst). Kolom angka
       dibuat sempit dan input di dalam sel dibuat penuh agar sejajar.
       table-layout: fixed dipakai supaya lebar kolom mengikuti aturan di
       bawah — sebelumnya tabel selebar 480px di dalam panel 429px sehingga
       kolom "Keterangan" menjorok keluar batas panel. */
    .data-desa { width: 100%; min-width: 400px; table-layout: fixed; }
    .data-desa th, .data-desa td { vertical-align: middle; }
    .data-desa .kolom-angka { width: 108px; text-align: right; }
    .data-desa .kolom-teks { width: 118px; }
    .data-desa input {
      width: 100%; padding: 8px 10px; font-size: .875rem;
    }
    /* Input angka rata kanan agar digit sejajar; input teks rata kiri. */
    .data-desa .kolom-angka input { text-align: right; }
    .data-desa .kolom-teks input { text-align: left; }
    .data-desa .sel-jumlah {
      display: inline-block; font-weight: 700; color: var(--navy-900);
      font-size: .9375rem; font-variant-numeric: tabular-nums;
    }

    /* Kolom kanan (pratinjau, logo, tautan publik) ikut menempel saat kolom
       isian yang panjang digulir, supaya separuh layar di kanan tidak kosong
       begitu saja. */
    .profil-sisi { position: sticky; top: 96px; align-self: start; }
    @media (max-width: 1280px) { .profil-sisi { position: static; } }

    /* Blok tabel data & narasi diletakkan di bawah dua kolom isian dan
       disusun dua kolom pada layar lebar, supaya halaman tidak menjadi
       satu kolom panjang dengan separuh layar kosong. */
    .data-grid {
      display: grid; grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 22px; align-items: start;
    }
    .data-grid > .panel { margin-bottom: 0; }
    /* Item grid tidak boleh melebar mengikuti isinya: tabel data desa punya
       min-width 400px, dan tanpa ini kolom grid ikut melar sehingga halaman
       meluber horizontal di layar sempit (tabel seharusnya bisa digulir). */
    .data-grid > * { min-width: 0; }
    .data-grid > .panel.lebar { grid-column: 1 / -1; }
    /* Tabel golongan umur punya empat kolom (label, L, P, jumlah) sehingga
       dibiarkan selebar penuh agar angkanya tidak berdesakan. */
    .data-grid > .panel:has(th.kolom-angka + th.kolom-angka) { grid-column: 1 / -1; }
    @media (max-width: 1100px) { .data-grid { grid-template-columns: 1fr; } }

    /* Kolom keterangan diberi ruang secukupnya, tetapi tidak sampai
       memepetkan kolom label (mis. "Jumlah penduduk sangat miskin" dulu
       terpecah menjadi tiga baris). Kolom angka tetap sempit. */
    .data-desa th:last-child:not(.kolom-angka) { width: 34%; }

    /* Panel narasi disusun dua kolom: kiri narasi utama (sejarah, visi,
       misi) dan kanan data wilayah, supaya tidak menjadi satu tumpukan
       kolom isian yang sangat panjang. */
    .narasi-grid {
      display: grid; grid-template-columns: 1fr 1fr;
      gap: 20px 24px; align-items: start;
    }
    .narasi-grid > div > .field + .field { margin-top: 20px; }
    @media (max-width: 1100px) { .narasi-grid { grid-template-columns: 1fr; } }

    /* Editor baris (batas wilayah, penggunaan lahan): tiap baris punya
       tombol hapus sendiri, dan tombol "+ Tambah" menambah baris baru.
       Dulu keduanya hanya satu kotak teks besar berformat "Arah: nama",
       sehingga perangkat desa harus menghafal tanda titik dua. */
    .baris-editor { display: grid; gap: 8px; }
    .baris-item { display: flex; gap: 8px; align-items: center; }
    .baris-item input { flex: 1 1 0; min-width: 0; }
    .baris-item input[type="number"] { flex: 0 0 88px; text-align: right; }
    .baris-hapus {
      flex: 0 0 auto; display: grid; place-items: center;
      width: 38px; height: 38px; border-radius: var(--radius-sm);
      border: 1.5px solid #E4C9C6; background: var(--white); color: #a3413b;
      cursor: pointer; transition: background .15s, border-color .15s;
    }
    .baris-hapus:hover { background: var(--red-100); border-color: #a3413b; }
    .baris-hapus svg { width: 16px; height: 16px; }
    .tautan-tambah {
      border: 0; background: none; padding: 0; cursor: pointer;
      font: inherit; font-size: .8125rem; font-weight: 700; color: var(--blue-600);
    }
    .tautan-tambah:hover { text-decoration: underline; }
    /* Di layar sempit, kolom "arah" dan "nama desa" ditumpuk agar isiannya
       tidak menjadi sangat sempit. */
    @media (max-width: 560px) {
      .baris-item { flex-wrap: wrap; }
      .baris-item input { flex: 1 1 100%; }
      .baris-item input[type="number"] { flex: 1 1 100%; text-align: left; }
      .baris-hapus { width: 100%; }
    }
  </style>
@endpush

@section('topbar')
@php $adminTrail = [['label' => 'Admin', 'route' => 'admin.ringkasan'], ['label' => 'Profil & Kontak', 'route' => null]]; @endphp
<x-admin-topbar :trail="$adminTrail" title="Profil & Kontak" />
@endsection

@section('content')
<main class="admin-content">
        <form method="POST" action="{{ route('admin.profil.update') }}" enctype="multipart/form-data">
          @csrf
          @method('PUT')

          <div class="admin-page-head">
            <div>
              <p class="sub">Informasi ini tampil pada halaman profil, kontak, dan footer website publik.</p>
            </div>
            <button type="submit" class="btn btn-primary">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
              Simpan perubahan
            </button>
          </div>

          @if (session('status'))
            <div class="notice notice-green" role="status" style="margin-bottom:22px">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
              <span>{{ session('status') }}</span>
            </div>
          @endif

          @if ($errors->any())
            <div class="notice notice-amber" role="alert" style="margin-bottom:22px">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
              <div>
                @foreach ($errors->all() as $pesan)
                  <div>{{ $pesan }}</div>
                @endforeach
              </div>
            </div>
          @endif

          <div class="admin-grid-2">
            <div>
              <div class="panel">
                <div class="panel-head">
                  <div>
                    <h2>Identitas desa</h2>
                    <p>Nama dan wilayah administratif sesuai data resmi.</p>
                  </div>
                </div>
                <div class="field mb-16">
                  <label for="nama_desa">Nama desa <span class="req">*</span></label>
                  <input type="text" id="nama_desa" name="nama_desa" value="{{ old('nama_desa', $profil->nama_desa) }}" required>
                </div>
                <div class="form-grid">
                  <div class="field">
                    <label for="kecamatan">Kecamatan</label>
                    <input type="text" id="kecamatan" name="kecamatan" value="{{ old('kecamatan', $profil->kecamatan) }}">
                  </div>
                  <div class="field">
                    <label for="kabupaten">Kabupaten</label>
                    <input type="text" id="kabupaten" name="kabupaten" value="{{ old('kabupaten', $profil->kabupaten) }}">
                  </div>
                  <div class="field">
                    <label for="provinsi">Provinsi</label>
                    <input type="text" id="provinsi" name="provinsi" value="{{ old('provinsi', $profil->provinsi) }}">
                  </div>
                  <div class="field">
                    <label for="kode_pos">Kode pos</label>
                    <input type="text" id="kode_pos" name="kode_pos" value="{{ old('kode_pos', $profil->kode_pos) }}" inputmode="numeric">
                  </div>
                </div>
                <div class="field mt-16">
                  <label for="alamat_kantor">Alamat kantor</label>
                  <textarea id="alamat_kantor" name="alamat_kantor" rows="3">{{ old('alamat_kantor', $profil->alamat_kantor) }}</textarea>
                </div>
              </div>

              <div class="panel">
                <div class="panel-head">
                  <div>
                    <h2>Kontak &amp; pelayanan</h2>
                    <p>Nomor yang dapat dihubungi warga dan jam pelayanan kantor.</p>
                  </div>
                </div>
                <div class="form-grid">
                  <div class="field">
                    <label for="telepon">Telepon kantor</label>
                    <input type="tel" id="telepon" name="telepon" value="{{ old('telepon', $profil->telepon) }}">
                  </div>
                  <div class="field">
                    <label for="whatsapp">WhatsApp desa</label>
                    <input type="tel" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $profil->whatsapp) }}">
                    <p class="field-help">Format lokal, contoh 081234567890.</p>
                  </div>
                  <div class="field span-2">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $profil->email) }}">
                  </div>
                  <div class="field span-2">
                    <label for="jam_layanan">Jam pelayanan</label>
                    <input type="text" id="jam_layanan" name="jam_layanan" value="{{ old('jam_layanan', $profil->jam_layanan) }}"
                           placeholder="mis. Senin - Minggu 08.00 - 17.00">
                    <p class="field-help">
                      Tampil di topbar, footer, panduan, dan halaman cek status.
                      Bila kantor buka setiap hari, tulis <b>Senin - Minggu 08.00 - 17.00</b>.
                    </p>
                  </div>
                  <div class="field span-2">
                    <label for="jam_kunjungan">Jam kunjungan kantor</label>
                    <input type="text" id="jam_kunjungan" name="jam_kunjungan" value="{{ old('jam_kunjungan', $profil->jam_kunjungan) }}"
                           placeholder="mis. Senin-Kamis 08.00-15.00, Jumat 08.00-11.30">
                    <p class="field-help">
                      Rincian per hari untuk kartu jam di halaman kontak. Pisahkan dengan koma
                      atau baris baru; tulis <code>Sabtu - Minggu Tutup</code> untuk hari libur.
                      Bila dikosongkan, kartu memakai <b>Jam pelayanan</b> di atas.
                    </p>
                  </div>
                  <div class="field span-2">
                    <label for="kueri_peta">Kueri lokasi peta</label>
                    <input type="text" id="kueri_peta" name="kueri_peta" value="{{ old('kueri_peta', $profil->kueri_peta) }}"
                           placeholder="mis. Kantor Desa Karang Sari, Jati Agung">
                    <p class="field-help">
                      Dipakai untuk peta dan tombol "Buka di Google Maps" di beranda dan halaman kontak.
                      Tulis nama tempat atau alamat yang mudah ditemukan di Google Maps.
                      Bila dikosongkan, peta memakai <b>Alamat kantor</b> dan wilayah desa di atas.
                    </p>
                  </div>

                  {{-- Koordinat: cara paling pasti agar peta menunjuk tepat ke
                       kantor desa. Tanpa koordinat, Google Maps menebak lokasi
                       dari teks sehingga pin bisa jatuh di jalan atau desa lain. --}}
                  <div class="field">
                    <label for="peta_lat">Koordinat peta — lintang</label>
                    <input type="text" id="peta_lat" name="peta_lat"
                           value="{{ old('peta_lat', $profil->peta_lat) }}"
                           maxlength="30" inputmode="decimal" placeholder="mis. -5.358421">
                    <p class="field-help">Angka pertama dari koordinat Google Maps.</p>
                  </div>
                  <div class="field">
                    <label for="peta_lng">Koordinat peta — bujur</label>
                    <input type="text" id="peta_lng" name="peta_lng"
                           value="{{ old('peta_lng', $profil->peta_lng) }}"
                           maxlength="30" inputmode="decimal" placeholder="mis. 105.294715">
                    <p class="field-help">Angka kedua dari koordinat Google Maps.</p>
                  </div>
                  <div class="field span-2">
                    <div class="notice notice-blue" role="note">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
                      <div>
                        <b>Cara mengambil koordinat:</b>
                        buka <b>Google Maps</b>, cari kantor desa, lalu klik kanan tepat di titik kantor.
                        Salin angka pertama (lintang) dan angka kedua (bujur) yang muncul paling atas.
                        <br>
                        Bila kedua kolom ini diisi, peta di beranda dan halaman kontak memakai titik
                        tersebut (skala 17) sehingga pin tepat di lokasi kantor. Bila dikosongkan,
                        peta kembali memakai <b>Kueri lokasi peta</b> di atas.
                      </div>
                    </div>
                    @if ($profil->koordinatPeta())
                      <p class="field-help">
                        Koordinat tersimpan: <b>{{ $profil->koordinatPeta() }}</b> ·
                        <a href="{{ $profil->tautanPeta() }}" target="_blank" rel="noopener">cek di Google Maps</a>
                      </p>
                    @endif
                  </div>
                  <div class="field span-2">
                    <label for="tahun_berdiri">Tahun berdiri desa</label>
                    <input type="text" id="tahun_berdiri" name="tahun_berdiri"
                           value="{{ old('tahun_berdiri', $profil->tahun_berdiri) }}"
                           maxlength="20" inputmode="numeric" placeholder="mis. 1912">
                    <p class="field-help">
                      Tampil pada kartu <b>Berdiri</b> di bagian Sejarah halaman Profil Desa.
                      Kosongkan bila belum diketahui — kartu tidak ditampilkan.
                    </p>
                  </div>
                </div>
              </div>

            </div>

            <div class="profil-sisi">
              <div class="panel">
                <div class="label-cap">Pratinjau identitas</div>
                <div class="preview-box mb-16">
                  @if ($profil->logoUrl())
                    <img src="{{ $profil->logoUrl() }}" alt="Logo {{ $profil->nama_desa }}">
                  @else
                    <div class="brand-mark" aria-hidden="true">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M4 18h16M6 18v-8h3v8M11 18v-8h3v8M16 18v-8h3v8M12 3l9 5H3l9-5z"/></svg>
                    </div>
                  @endif
                  <div>
                    <b>{{ $profil->nama_desa }}</b>
                    <span>Kecamatan {{ $profil->kecamatan }}, {{ $profil->kabupaten }}</span>
                  </div>
                </div>
                <p class="field-help">
                  @if ($profil->logoUrl())
                    Logo ini tampil di header, footer, dan panel admin.
                  @else
                    Lambang bawaan hanya penanda sementara sampai logo resmi diunggah.
                  @endif
                </p>
              </div>

              <div class="panel">
                <div class="panel-head">
                  <div>
                    <h2>Logo desa</h2>
                    <p>Unggah logo resmi berformat PNG, JPG, atau WEBP.</p>
                  </div>
                </div>
                {{-- Dropzone diberi tahu bila sudah ada berkas tersimpan,
                     supaya kotaknya menampilkan berkas itu (bukan ajakan
                     memilih lagi) setiap kali halaman dibuka. --}}
                <div class="dropzone" data-dropzone data-size-hint="klik untuk mengganti"
                     @if ($profil->logoUrl()) data-berkas-ada="1"
                       data-berkas-nama="Logo desa tersimpan"
                       data-berkas-ukuran="Klik untuk mengganti" @endif>
                  <input type="file" id="logo" name="logo" accept=".png,.jpg,.jpeg,.webp" hidden>
                  <input type="hidden" name="logo_hapus" value="0" class="dz-hapus">
                  <div class="dz-default">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>
                    <div style="font-weight:600">Pilih file logo</div>
                    <div class="small muted" style="margin-top:4px">PNG, JPG, WEBP · maks. 1 MB</div>
                  </div>
                  <div class="dropzone-file" style="display:none">
                    @if ($profil->logoUrl())
                      {{-- Pratinjau gambar agar perangkat desa langsung yakin
                           berkas yang benar sudah tersimpan. --}}
                      <img src="{{ $profil->logoUrl() }}" alt="Pratinjau logo desa"
                           style="width:44px;height:44px;object-fit:contain;border-radius:6px;border:1px solid var(--gray-200);background:#fff;flex:none">
                    @else
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                    @endif
                    <div class="dz-meta">
                      <div class="dz-name"></div>
                      <div class="dz-size"></div>
                    </div>
                    <button type="button" class="dz-remove">Hapus</button>
                  </div>
                </div>

                <div class="panel-head mt-24">
                  <div>
                    <h2>Foto desa</h2>
                    <p>Foto lanskap desa. Tampil di beranda, pada seksi
                       <b>Sekilas Desa</b> di atas kartu angka.</p>
                  </div>
                </div>
                <div class="dropzone" data-dropzone data-size-hint="klik untuk mengganti"
                     @if ($profil->fotoBerandaUrl()) data-berkas-ada="1"
                       data-berkas-nama="Foto desa tersimpan"
                       data-berkas-ukuran="Klik untuk mengganti" @endif>
                  <input type="file" id="foto_beranda" name="foto_beranda" accept=".jpg,.jpeg,.png,.webp" hidden>
                  <input type="hidden" name="foto_beranda_hapus" value="0" class="dz-hapus">
                  <div class="dz-default">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                    <div style="font-weight:600">Pilih foto desa</div>
                    <div class="small muted" style="margin-top:4px">JPG, PNG, WEBP · maks. 5 MB</div>
                  </div>
                  <div class="dropzone-file" style="display:none">
                    @if ($profil->fotoBerandaUrl())
                      <img src="{{ $profil->fotoBerandaUrl() }}" alt="Pratinjau foto desa"
                           style="width:52px;height:38px;object-fit:cover;border-radius:6px;border:1px solid var(--gray-200);flex:none">
                    @else
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                    @endif
                    <div class="dz-meta">
                      <div class="dz-name"></div>
                      <div class="dz-size"></div>
                    </div>
                    {{-- Tombol "Ganti" hanya memilih berkas baru. Penghapusan
                         sengaja dipisah ke tautan kecil di bawah, supaya tidak
                         tertekan tanpa sengaja dan menghapus foto desa. --}}
                    <button type="button" class="dz-remove">Ganti</button>
                  </div>
                </div>
                @if ($profil->fotoBerandaUrl())
                  <p class="field-help mt-16">
                    <a href="#" class="tautan-hapus-foto" data-hapus-foto>Hapus foto desa</a>
                  </p>
                @else
                  <p class="field-help mt-16">File gambar diunggah saat tombol simpan ditekan.</p>
                @endif
              </div>

              <div class="panel">
                <div class="panel-head">
                  <div>
                    <h2>Tautan publik</h2>
                    <p>Periksa tampilan setelah menyimpan perubahan.</p>
                  </div>
                </div>
                <a href="{{ route('profil') }}" target="_blank" rel="noopener" class="pub-link">
                  Halaman profil
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6M10 14L21 3"/></svg>
                </a>
                <a href="{{ route('kontak') }}" target="_blank" rel="noopener" class="pub-link">
                  Halaman kontak
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6M10 14L21 3"/></svg>
                </a>
              </div>
            </div>
          </div>

          {{-- Kondisi umum, tabel data desa, dan narasi profil memakai lebar
               penuh di bawah dua kolom isian, supaya kolom angka tabel tidak
               terjepit dan halaman tidak memanjang tanpa isi. --}}
          <div class="profil-lebar">
            <div class="panel">
              <div class="panel-head">
                <div>
                  <h2>Kondisi umum desa</h2>
                  <p>Bagian "A. Kondisi Umum Desa" pada dokumen profil desa.</p>
                </div>
              </div>

              <div class="form-grid">
                <div class="field">
                  <label for="luas_wilayah">Luas wilayah</label>
                  <input type="text" id="luas_wilayah" name="luas_wilayah"
                         value="{{ old('luas_wilayah', $profil->luas_wilayah) }}"
                         maxlength="30" placeholder="mis. 725 ha">
                </div>
                <div class="field">
                  <label for="jumlah_kk">Jumlah kepala keluarga</label>
                  <input type="text" id="jumlah_kk" name="jumlah_kk"
                         value="{{ old('jumlah_kk', $profil->jumlah_kk) }}"
                         maxlength="20" inputmode="numeric" placeholder="mis. 1986">
                </div>
                <div class="field">
                  <label for="penduduk_laki_laki">Penduduk laki-laki</label>
                  <input type="text" id="penduduk_laki_laki" name="penduduk_laki_laki"
                         value="{{ old('penduduk_laki_laki', $profil->penduduk_laki_laki) }}"
                         maxlength="20" inputmode="numeric" placeholder="mis. 5333">
                </div>
                <div class="field">
                  <label for="penduduk_perempuan">Penduduk perempuan</label>
                  <input type="text" id="penduduk_perempuan" name="penduduk_perempuan"
                         value="{{ old('penduduk_perempuan', $profil->penduduk_perempuan) }}"
                         maxlength="20" inputmode="numeric" placeholder="mis. 4805">
                  <p class="field-help">Jumlah penduduk total dihitung otomatis dari kedua angka ini.</p>
                </div>
              </div>

              <div class="form-grid mt-16">
                <div class="field">
                  <label for="jarak_provinsi">Jarak ke ibu kota provinsi</label>
                  <input type="text" id="jarak_provinsi" name="jarak_provinsi"
                         value="{{ old('jarak_provinsi', $profil->jarak_provinsi) }}"
                         maxlength="20" placeholder="mis. 15 Km">
                </div>
                <div class="field">
                  <label for="jarak_kabupaten">Jarak ke ibu kota kabupaten</label>
                  <input type="text" id="jarak_kabupaten" name="jarak_kabupaten"
                         value="{{ old('jarak_kabupaten', $profil->jarak_kabupaten) }}"
                         maxlength="20" placeholder="mis. 80 Km">
                </div>
                <div class="field">
                  <label for="jarak_kecamatan">Jarak ke ibu kota kecamatan</label>
                  <input type="text" id="jarak_kecamatan" name="jarak_kecamatan"
                         value="{{ old('jarak_kecamatan', $profil->jarak_kecamatan) }}"
                         maxlength="20" placeholder="mis. 2,5 Km">
                </div>
              </div>

              <div class="field mt-16">
                <label for="kondisi_umum">Gambaran umum kondisi desa</label>
                <textarea id="kondisi_umum" name="kondisi_umum" rows="4" maxlength="5000"
                          placeholder="Uraian singkat kondisi desa: letak, batas, dan potensi dasarnya."
                >{{ old('kondisi_umum', $profil->kondisi_umum) }}</textarea>
              </div>

              <div class="field">
                <label for="iklim">Iklim</label>
                <textarea id="iklim" name="iklim" rows="3" maxlength="2000"
                          placeholder="mis. Iklim kemarau dan penghujan, berpengaruh pada pola tanam."
                >{{ old('iklim', $profil->iklim) }}</textarea>
              </div>

              <div class="field">
                <label for="daftar_dusun">Daftar dusun (satu per baris)</label>
                <textarea id="daftar_dusun" name="daftar_dusun" rows="5" maxlength="2000"
                          placeholder="Dusun I B&#10;Dusun II B&#10;Dusun Warung Gunung"
                >{{ old('daftar_dusun', $profil->daftar_dusun) }}</textarea>
                <p class="field-help">Setiap baris menjadi satu butir pada bagian Sejarah di halaman Profil Desa.</p>
              </div>

              <div class="field">
                <label for="keadaan_sosial">Keadaan sosial masyarakat</label>
                <textarea id="keadaan_sosial" name="keadaan_sosial" rows="4" maxlength="5000"
                          placeholder="Uraian mata pencaharian, organisasi masyarakat, dan catatan sosial desa."
                >{{ old('keadaan_sosial', $profil->keadaan_sosial) }}</textarea>
              </div>
            </div>

            <div class="data-grid">
            {{-- Tabel data desa mengikuti tabel pada dokumen PROFIL desa.docx.
                 Label baris tidak diubah dari sini agar tetap sama dengan
                 dokumen resmi; perangkat desa hanya mengisi angkanya. --}}
            @foreach ($kelompokData as $kelompok => $judul)
              @php $barisKelompok = $dataDesa[$kelompok] ?? collect(); @endphp
              @continue($barisKelompok->isEmpty())
              {{-- Tabel riwayat kepala desa punya tiga kolom isian sehingga
                   diberi lebar penuh; kalau dipaksa setengah lebar, teks
                   "Kepala Desa Pertama" dan "Empat bulan" terpotong. --}}
              <div @class(['panel', 'lebar' => $kelompok === \App\Models\DataDesa::RIWAYAT_KADES])>
                <div class="panel-head">
                  <div>
                    <h2>{{ $judul }}</h2>
                    <p>Isi angka sesuai data resmi desa. Baris yang dibiarkan kosong tidak ditampilkan di halaman publik.</p>
                  </div>
                </div>

                @if ($kelompok === \App\Models\DataDesa::GOLONGAN_UMUR)
                  <div class="table-wrap">
                    <table class="data data-desa">
                      <thead>
                        <tr>
                          <th>Golongan umur</th>
                          <th class="kolom-angka">L</th>
                          <th class="kolom-angka">P</th>
                          <th class="kolom-angka">Jumlah</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach ($barisKelompok as $baris)
                          <tr>
                            <td><div class="cell-main">{{ $baris->label }}</div></td>
                            <td class="kolom-angka">
                              <input type="text" inputmode="numeric" maxlength="20"
                                     aria-label="Jumlah laki-laki {{ $baris->label }}"
                                     name="data[{{ $baris->id }}][nilai_l]"
                                     value="{{ old('data.'.$baris->id.'.nilai_l', $baris->nilai_l) }}">
                            </td>
                            <td class="kolom-angka">
                              <input type="text" inputmode="numeric" maxlength="20"
                                     aria-label="Jumlah perempuan {{ $baris->label }}"
                                     name="data[{{ $baris->id }}][nilai_p]"
                                     value="{{ old('data.'.$baris->id.'.nilai_p', $baris->nilai_p) }}">
                            </td>
                            <td class="kolom-angka">
                              <output class="sel-jumlah" data-jumlah>{{ $baris->jumlahTampil() }}</output>
                            </td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                  <p class="field-help mt-16">Kolom jumlah dihitung otomatis dari L + P saat halaman publik dibuka.</p>
                @elseif ($kelompok === \App\Models\DataDesa::RIWAYAT_KADES)
                  <div class="table-wrap">
                    <table class="data data-desa">
                      <thead>
                        <tr>
                          <th>Nama kepala desa</th>
                          <th>Masa jabatan</th>
                          <th>Keterangan</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach ($barisKelompok as $baris)
                          <tr>
                            <td><div class="cell-main">{{ $baris->label }}</div></td>
                            <td>
                              <input type="text" maxlength="40"
                                     aria-label="Masa jabatan {{ $baris->label }}"
                                     name="data[{{ $baris->id }}][nilai]"
                                     value="{{ old('data.'.$baris->id.'.nilai', $baris->nilai) }}"
                                     placeholder="mis. 2002 - 2008">
                            </td>
                            <td>
                              <input type="text" maxlength="120"
                                     aria-label="Keterangan {{ $baris->label }}"
                                     name="data[{{ $baris->id }}][keterangan]"
                                     value="{{ old('data.'.$baris->id.'.keterangan', $baris->keterangan) }}"
                                     placeholder="mis. Kepala Desa">
                            </td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                @else
                  <div class="table-wrap">
                    <table class="data data-desa">
                      <thead>
                        <tr>
                          <th>{{ $kelompok === \App\Models\DataDesa::KESEJAHTERAAN ? 'Uraian' : 'Kategori' }}</th>
                          <th class="kolom-angka">Jumlah</th>
                          <th>Keterangan</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach ($barisKelompok as $baris)
                          <tr>
                            <td><div class="cell-main">{{ $baris->label }}</div></td>
                            <td class="kolom-angka">
                              <input type="text" inputmode="numeric" maxlength="40"
                                     aria-label="Jumlah {{ $baris->label }}"
                                     name="data[{{ $baris->id }}][nilai]"
                                     value="{{ old('data.'.$baris->id.'.nilai', $baris->nilai) }}">
                            </td>
                            <td>
                              <input type="text" maxlength="120"
                                     aria-label="Keterangan {{ $baris->label }}"
                                     name="data[{{ $baris->id }}][keterangan]"
                                     value="{{ old('data.'.$baris->id.'.keterangan', $baris->keterangan) }}"
                                     placeholder="mis. orang">
                            </td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                @endif
              </div>
            @endforeach
            </div>

            <div class="panel">
              <div class="panel-head">
                <div>
                  <h2>Narasi profil</h2>
                  <p>Sejarah, visi, dan misi desa untuk halaman profil publik.</p>
                </div>
              </div>

              {{-- Narasi disusun dua kolom agar panel tidak menjadi satu
                   tumpukan kolom isian yang sangat panjang: kiri narasi
                   utama (sejarah, visi, misi), kanan data wilayah. --}}
              <div class="narasi-grid">
                <div>
                  <div class="field">
                    <label for="sejarah">Sejarah singkat</label>
                    <textarea id="sejarah" name="sejarah" rows="8" maxlength="5000"
                              placeholder="Tulis sejarah desa. Pisahkan antar paragraf dengan satu baris kosong."
                    >{{ old('sejarah', $profil->sejarah) }}</textarea>
                    <p class="field-help">Pisahkan antar paragraf dengan satu baris kosong. Maks. 5.000 karakter.</p>
                  </div>

                  <div class="field">
                    <label for="visi">Visi</label>
                    <textarea id="visi" name="visi" rows="4" maxlength="2000"
                              placeholder="Satu kalimat visi desa."
                    >{{ old('visi', $profil->visi) }}</textarea>
                  </div>

                  <div class="field">
                    <label for="misi">Misi (satu per baris)</label>
                    <textarea id="misi" name="misi" rows="6" maxlength="5000"
                              placeholder="Meningkatkan pelayanan administrasi desa&#10;Mengembangkan potensi pertanian"
                    >{{ old('misi', $profil->misi) }}</textarea>
                    <p class="field-help">Setiap baris menjadi satu butir misi bernomor.</p>
                  </div>
                </div>

                <div>
                  {{-- Batas wilayah & penggunaan lahan disunting sebagai baris
                       yang bisa DITAMBAH dan DIHAPUS, bukan satu kotak teks
                       besar. Perangkat desa cukup mengisi arah dan nama desa
                       tetangga pada baris terpisah. --}}
                  <div class="field">
                    <div class="field-label-row">
                      <label for="batas-0-arah">Batas wilayah</label>
                      <button type="button" class="tautan-tambah" data-tambah-baris="batas">
                        + Tambah arah
                      </button>
                    </div>
                    @php
                      $barisBatas = old('batas');
                      if (! is_array($barisBatas)) {
                          $barisBatas = [];
                          foreach ($profil->batas_wilayah_tersusun as $arah => $tetangga) {
                              $barisBatas[] = ['arah' => $arah, 'tetangga' => $tetangga];
                          }
                      }
                      if ($barisBatas === []) {
                          $barisBatas = [['arah' => '', 'tetangga' => '']];
                      }
                    @endphp
                    <div class="baris-editor" data-baris-editor="batas">
                      @foreach ($barisBatas as $i => $baris)
                        <div class="baris-item">
                          <input type="text" name="batas[{{ $i }}][arah]"
                                 value="{{ is_array($baris) ? ($baris['arah'] ?? '') : '' }}"
                                 maxlength="60" placeholder="Arah, mis. Utara" aria-label="Arah batas wilayah">
                          <input type="text" name="batas[{{ $i }}][tetangga]"
                                 value="{{ is_array($baris) ? ($baris['tetangga'] ?? '') : '' }}"
                                 maxlength="160" placeholder="Desa/kecamatan tetangga" aria-label="Nama desa tetangga">
                          <button type="button" class="baris-hapus" data-hapus-baris aria-label="Hapus baris ini">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                          </button>
                        </div>
                      @endforeach
                    </div>
                    <p class="field-help">Kosongkan kedua kolom pada baris yang tidak dipakai. Baris kosong otomatis diabaikan.</p>
                  </div>

                  <div class="field">
                    <div class="field-label-row">
                      <label for="lahan-0-label">Penggunaan lahan</label>
                      <button type="button" class="tautan-tambah" data-tambah-baris="lahan">
                        + Tambah lahan
                      </button>
                    </div>
                    @php
                      $barisLahan = old('lahan');
                      if (! is_array($barisLahan)) {
                          $barisLahan = [];
                          foreach ($profil->penggunaan_lahan_tersusun as $lahan) {
                              $barisLahan[] = ['label' => $lahan['label'], 'persen' => $lahan['persen']];
                          }
                      }
                      if ($barisLahan === []) {
                          $barisLahan = [['label' => '', 'persen' => '']];
                      }
                    @endphp
                    <div class="baris-editor" data-baris-editor="lahan">
                      @foreach ($barisLahan as $i => $baris)
                        <div class="baris-item">
                          <input type="text" name="lahan[{{ $i }}][label]"
                                 value="{{ is_array($baris) ? ($baris['label'] ?? '') : '' }}"
                                 maxlength="60" placeholder="Jenis lahan, mis. Sawah" aria-label="Jenis penggunaan lahan">
                          <input type="number" name="lahan[{{ $i }}][persen]"
                                 value="{{ is_array($baris) ? ($baris['persen'] ?? '') : '' }}"
                                 min="0" max="100" inputmode="numeric" placeholder="%" aria-label="Persentase">
                          <button type="button" class="baris-hapus" data-hapus-baris aria-label="Hapus baris ini">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                          </button>
                        </div>
                      @endforeach
                    </div>
                    <p class="field-help">Persen 0–100. Total disarankan 100%.</p>
                  </div>
                </div>
              </div>

              <div class="notice notice-blue" style="margin-top:16px">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/></svg>
                <div>
                  <b>Struktur pemerintahan desa</b><br>
                  Daftar perangkat desa (kepala desa, sekretaris, kaur, kadus, dst) dikelola terpisah dan tampil pada halaman Profil Desa.
                  <div style="margin-top:10px"><a href="{{ route('admin.perangkat.index') }}" class="btn btn-outline btn-sm">Kelola struktur pemerintahan →</a></div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </main>
@endsection

@push('scripts')
<script>
  // Editor baris untuk "Batas wilayah" dan "Penggunaan lahan":
  // tombol "+ Tambah" menyalin baris terakhir (kosong), tombol tong sampah
  // menghapus barisnya. Indeks nama field sengaja tidak dirapikan ulang —
  // Laravel menerima kunci array apa pun, dan indeks yang bolong tidak
  // menjadi masalah karena controller menyaring baris kosong.
  (function () {
    function barisBaru(jenis) {
      var contoh = document.querySelector('[data-baris-editor="' + jenis + '"] .baris-item');
      if (! contoh) return null;

      var salinan = contoh.cloneNode(true);
      salinan.querySelectorAll('input').forEach(function (input) {
        input.value = '';
      });
      return salinan;
    }

    document.querySelectorAll('[data-tambah-baris]').forEach(function (tombol) {
      tombol.addEventListener('click', function () {
        var jenis = tombol.dataset.tambahBaris;
        var wadah = document.querySelector('[data-baris-editor="' + jenis + '"]');
        if (! wadah) return;

        var baru = barisBaru(jenis);
        if (! baru) return;

        // Indeks harus unik: ambil nomor terbesar yang sudah ada lalu +1.
        var nomor = 0;
        wadah.querySelectorAll('input[name]').forEach(function (input) {
          var cocok = /\[(\d+)\]/.exec(input.name);
          if (cocok) nomor = Math.max(nomor, parseInt(cocok[1], 10) + 1);
        });

        baru.querySelectorAll('input[name]').forEach(function (input) {
          input.name = input.name.replace(/\[\d+\]/, '[' + nomor + ']');
        });

        wadah.appendChild(baru);
        baru.querySelector('input')?.focus();
      });
    });

    // Satu pendengar di dokumen: tetap bekerja untuk baris yang baru dibuat.
    document.addEventListener('click', function (e) {
      var hapus = e.target.closest('[data-hapus-baris]');
      if (! hapus) return;

      var wadah = hapus.closest('[data-baris-editor]');
      var baris = hapus.closest('.baris-item');
      if (! wadah || ! baris) return;

      // Sisakan minimal satu baris supaya formulir tidak menjadi kosong
      // tanpa jalan untuk menambah lagi.
      if (wadah.querySelectorAll('.baris-item').length > 1) {
        baris.remove();
      } else {
        baris.querySelectorAll('input').forEach(function (input) {
          input.value = '';
        });
      }
    });
  })();
</script>
@endpush
