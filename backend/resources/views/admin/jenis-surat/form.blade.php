@extends('layouts.admin')

@php
  $baru = ! $jenisSurat->exists;
  // Nilai awal baris dinamis: dari old() saat validasi gagal, atau dari data tersimpan.
  $fieldAwal = old('field', $jenisSurat->fieldFormulir());
  $syaratAwal = old('persyaratan', $jenisSurat->persyaratan->map(fn ($p) => [
      'id' => $p->id,
      'label' => $p->label,
      'format' => $p->format,
      'maks_kb' => $p->maks_kb ?? $p->petunjuk,
      'wajib' => (bool) $p->wajib,
  ])->all());
@endphp

@section('title', ($baru ? 'Tambah Jenis Surat' : 'Konfigurasi '.$jenisSurat->nama).' · Dashboard Admin '.$profil->nama_desa)

@push('styles')
<style>
  /* Sub-panel di dalam form: field formulir & persyaratan. */
  .sub-panel { border: 1px solid var(--gray-200); border-radius: var(--radius-md); padding: 18px 20px; margin-bottom: 18px; }
  .sub-panel-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 14px; flex-wrap: wrap; }
  .sub-panel-head h3 { font-size: .9375rem; }
  .sub-panel-head .hitung { font-size: .8125rem; color: var(--gray-500); font-weight: 600; }

  /* Baris dinamis: kisi kolom tetap agar sejajar antarbaris. */
  .baris-dinamis { display: grid; gap: 10px; align-items: end; margin-bottom: 10px; }
  .baris-dinamis:last-child { margin-bottom: 0; }
  .baris-field { grid-template-columns: 1.4fr 1fr 0.9fr auto auto; }
  .baris-syarat { grid-template-columns: 1.6fr 1fr 0.7fr auto auto; }
  @media (max-width: 900px) {
    .baris-field, .baris-syarat { grid-template-columns: 1fr 1fr; }
  }
  .baris-dinamis .field { margin-bottom: 0; }
  .baris-dinamis .field label { font-size: .75rem; color: var(--gray-500); font-weight: 600; margin-bottom: 4px; }
  .baris-dinamis .centang { display: flex; align-items: center; gap: 7px; font-size: .8125rem; color: var(--gray-600); padding-bottom: 10px; white-space: nowrap; }
  .baris-dinamis .centang input { width: 15px; height: 15px; margin: 0; cursor: pointer; }
  .baris-hapus {
    background: var(--white); border: 1px solid var(--gray-200); color: var(--gray-500);
    width: 36px; height: 36px; border-radius: var(--radius-sm); cursor: pointer;
    display: grid; place-items: center; margin-bottom: 1px;
  }
  .baris-hapus:hover { border-color: var(--red-600); color: var(--red-600); background: var(--red-100); }
  .baris-hapus svg { width: 16px; height: 16px; }
  .kosong { color: var(--gray-500); font-size: .8125rem; padding: 12px 0; }

  /* Editor template: monospace, bisa diubah ukurannya. */
  .template-editor {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: .8125rem; line-height: 1.65; min-height: 260px; resize: vertical;
    background: #fcfdfe;
  }
  .template-bantuan { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; align-items: center; }
  .penanda-chip {
    font-family: ui-monospace, monospace; font-size: .71875rem;
    background: var(--blue-100); color: var(--navy-800);
    border: 1px solid transparent; border-radius: var(--radius-full);
    padding: 3px 9px; cursor: pointer;
  }
  .penanda-chip:hover { border-color: var(--blue-500); }
  .penanda-chip.otomatis { background: var(--green-100); color: var(--green-600); }
  .penanda-chip.frase { background: var(--amber-100); color: var(--amber-600); }
</style>
@endpush

@section('topbar')
@php $adminTrail = [
    ['label' => 'Admin', 'route' => 'admin.ringkasan'],
    ['label' => 'Jenis Surat', 'route' => 'admin.jenis-surat.index'],
    ['label' => $baru ? 'Baru' : $jenisSurat->kode, 'route' => null],
]; @endphp
<x-admin-topbar :trail="$adminTrail" title="{{ $baru ? 'Tambah Jenis Surat' : 'Konfigurasi Jenis Surat' }}" />
@endsection

@section('content')
<main class="admin-content">
        <div class="admin-page-head">
          <div>
            <p class="sub">Konfigurasi berlaku untuk pengajuan baru. Pengajuan lama menggunakan snapshot.</p>
          </div>
          <a href="{{ route('admin.jenis-surat.index') }}" class="btn btn-outline btn-sm">Kembali ke daftar</a>
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

        <div class="panel">
          <form method="POST"
                action="{{ $baru ? route('admin.jenis-surat.store') : route('admin.jenis-surat.update', $jenisSurat) }}">
            @csrf
            @if (! $baru)
              @method('PUT')
            @endif

            <div class="form-grid">
              <div class="field">
                <label for="nama">Nama jenis surat</label>
                <input type="text" id="nama" name="nama" value="{{ old('nama', $jenisSurat->nama) }}" required maxlength="255" placeholder="mis. Surat Keterangan Domisili">
              </div>
              <div class="field">
                <label for="kode">Kode layanan</label>
                <input type="text" id="kode" name="kode" value="{{ old('kode', $jenisSurat->kode) }}" required maxlength="20" placeholder="mis. SKD">
                <p class="field-help">Kode unik, dipakai pada nomor surat.</p>
              </div>
              <div class="field">
                <label for="kode_layanan">Kode klasifikasi (opsional)</label>
                <input type="text" id="kode_layanan" name="kode_layanan" value="{{ old('kode_layanan', $jenisSurat->kode_layanan) }}" maxlength="20" placeholder="mis. 470">
                <p class="field-help">Angka klasifikasi yang tampil sebagai badge di daftar.</p>
              </div>
              <div class="field">
                <label for="estimasi_hari">Estimasi penyelesaian</label>
                <input type="text" id="estimasi_hari" name="estimasi_hari" value="{{ old('estimasi_hari', $jenisSurat->estimasi_hari) }}" maxlength="30" placeholder="mis. 2 hari kerja">
              </div>
            </div>

            <div class="field mb-16">
              <label for="deskripsi">Deskripsi untuk warga</label>
              <textarea id="deskripsi" name="deskripsi" rows="3" maxlength="2000" placeholder="Jelaskan singkat kegunaan surat ini.">{{ old('deskripsi', $jenisSurat->deskripsi) }}</textarea>
              <p class="field-help">Tampil pada kartu katalog layanan dan halaman detail.</p>
            </div>

            <div class="field mb-16">
              <label for="is_aktif">Tampilkan jenis surat ini di website publik</label>
              <select id="is_aktif" name="is_aktif">
                <option value="1" @selected(\App\Support\TeksTampil::skalar(old('is_aktif', $jenisSurat->is_aktif ? '1' : '0')) === '1')>Aktif — tampil di katalog</option>
                <option value="0" @selected(\App\Support\TeksTampil::skalar(old('is_aktif', $jenisSurat->is_aktif ? '1' : '0')) === '0')>Nonaktif — disembunyikan</option>
              </select>
            </div>

            {{-- ---------- Field formulir ---------- --}}
            <div class="sub-panel">
              <div class="sub-panel-head">
                <div>
                  <h3>Field formulir (<span id="hitungField">{{ is_array($fieldAwal) ? count($fieldAwal) : 0 }}</span>)</h3>
                  <p class="field-help">Kolom isian tambahan yang diisi warga selain data diri utama.</p>
                </div>
                <button type="button" class="btn btn-outline btn-sm" id="tambahField">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15" style="margin-right:5px"><path d="M12 5v14M5 12h14"/></svg>
                  Tambah field
                </button>
              </div>

              <div id="wadahField">
                @foreach (is_array($fieldAwal) ? $fieldAwal : [] as $i => $f)
                  <div class="baris-dinamis baris-field">
                    <div class="field">
                      <label for="field-{{ $i }}-label">Label</label>
                      <input type="text" id="field-{{ $i }}-label" name="field[{{ $i }}][label]" value="{{ $f['label'] ?? '' }}" placeholder="mis. Lama tinggal">
                    </div>
                    <div class="field">
                      <label for="field-{{ $i }}-kunci">Kunci field</label>
                      <input type="text" id="field-{{ $i }}-kunci" name="field[{{ $i }}][kunci]" value="{{ $f['kunci'] ?? '' }}" placeholder="lama_tinggal" pattern="[a-z0-9_]*">
                    </div>
                    <div class="field">
                      <label for="field-{{ $i }}-tipe">Tipe</label>
                      <select id="field-{{ $i }}-tipe" name="field[{{ $i }}][tipe]">
                        @foreach ($tipeField as $t)
                          <option value="{{ $t }}" @selected(($f['tipe'] ?? 'text') === $t)>{{ ucfirst($t) }}</option>
                        @endforeach
                      </select>
                    </div>
                    <label class="centang" for="field-{{ $i }}-wajib"><input type="checkbox" id="field-{{ $i }}-wajib" name="field[{{ $i }}][wajib]" value="1" @checked($f['wajib'] ?? false)> Wajib</label>
                    <button type="button" class="baris-hapus" aria-label="Hapus field">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                    </button>
                  </div>
                @endforeach
              </div>
              <p class="field-help" style="margin-top:10px">Kunci field harus unik dalam satu jenis surat dan tidak boleh diubah setelah pengajuan pertama masuk.</p>
            </div>

            {{-- ---------- Persyaratan dokumen ---------- --}}
            <div class="sub-panel">
              <div class="sub-panel-head">
                <div>
                  <h3>Persyaratan (<span id="hitungSyarat">{{ is_array($syaratAwal) ? count($syaratAwal) : 0 }}</span>)</h3>
                  <p class="field-help">Dokumen yang harus diunggah warga.</p>
                </div>
                <button type="button" class="btn btn-outline btn-sm" id="tambahSyarat">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15" style="margin-right:5px"><path d="M12 5v14M5 12h14"/></svg>
                  Tambah persyaratan
                </button>
              </div>

              <div id="wadahSyarat">
                @foreach (is_array($syaratAwal) ? $syaratAwal : [] as $i => $s)
                  <div class="baris-dinamis baris-syarat">
                    {{-- ID baris dikirim balik agar penyimpanan dapat MEMPERBARUI
                         persyaratan yang sudah ada. Tanpa ini, seluruh baris
                         dihapus lalu dibuat ulang dengan ID baru, sehingga
                         dokumen warga yang menunjuk ID lama kehilangan
                         tautannya. --}}
                    <input type="hidden" name="persyaratan[{{ $i }}][id]" value="{{ $s['id'] ?? '' }}">
                    <div class="field">
                      <label for="syarat-{{ $i }}-label">Nama dokumen</label>
                      <input type="text" id="syarat-{{ $i }}-label" name="persyaratan[{{ $i }}][label]" value="{{ $s['label'] ?? '' }}" placeholder="mis. Fotokopi KTP">
                    </div>
                    <div class="field">
                      <label for="syarat-{{ $i }}-format">Format</label>
                      <input type="text" id="syarat-{{ $i }}-format" name="persyaratan[{{ $i }}][format]" value="{{ $s['format'] ?? '' }}" placeholder="pdf,jpg,png">
                    </div>
                    <div class="field">
                      <label for="syarat-{{ $i }}-maks">Maks. KB</label>
                      <input type="number" id="syarat-{{ $i }}-maks" name="persyaratan[{{ $i }}][maks_kb]" value="{{ $s['maks_kb'] ?? '' }}" min="64" max="10240" placeholder="2048">
                    </div>
                    <label class="centang" for="syarat-{{ $i }}-wajib"><input type="checkbox" id="syarat-{{ $i }}-wajib" name="persyaratan[{{ $i }}][wajib]" value="1" @checked($s['wajib'] ?? false)> Wajib</label>
                    <button type="button" class="baris-hapus" aria-label="Hapus persyaratan">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                    </button>
                  </div>
                @endforeach
              </div>
              <p class="field-help" style="margin-top:10px">Validasi format dan ukuran diterapkan saat warga mengunggah dokumen.</p>
            </div>

            {{-- ---------- Template surat ---------- --}}
            <div class="field mb-16">
              <label for="template_surat">Template surat</label>

              @php $sumberDokumen = $baru ? null : \App\Support\TemplateSuratDesa::sumber((string) $jenisSurat->slug); @endphp
              @if ($sumberDokumen)
                <div class="notice notice-green" style="margin-bottom:12px">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                  <span>
                    Template ini disalin dari dokumen resmi desa
                    <b>{{ $sumberDokumen }}</b>. Ubah hanya bila ada perubahan dari perangkat desa.
                  </span>
                </div>

                @php
                  $petaDokumen = \App\Support\PetaSuratDesa::peta((string) $jenisSurat->slug);
                  $labelBaris = [
                      'nama' => 'Nama',
                      'nik' => 'NIK',
                      'tempat_tanggal_lahir' => 'Tempat & tanggal lahir',
                      'agama' => 'Agama',
                      'status_perkawinan' => 'Status perkawinan',
                      'pekerjaan' => 'Pekerjaan',
                      'dusun' => 'Alamat (Dusun …)',
                      'nama_anak' => 'Nama anak asuh',
                      'nik_anak' => 'NIK anak asuh',
                      'tempat_tanggal_lahir_anak' => 'Tempat & tanggal lahir anak asuh',
                      'agama_anak' => 'Agama anak asuh',
                      'pekerjaan_anak' => 'Pekerjaan anak asuh',
                      'alamat_anak' => 'Alamat anak asuh (Dusun …)',
                      'nama_usaha' => 'Nama/jenis usaha',
                      'alamat_usaha' => 'Alamat usaha (Dusun …)',
                      'tanggal_meninggal' => 'Tanggal meninggal',
                      'tempat_makam' => 'Tempat dimakamkan',
                      'sejak' => 'Menjadi orang tua asuh sejak',
                      'pekerjaan_sebenarnya' => 'Pekerjaan sebenarnya',
                  ];
                  $barisCetak = collect($petaDokumen['identitas'] ?? [])
                      ->merge($petaDokumen['identitas_kedua'] ?? [])
                      ->merge($petaDokumen['titik'] ?? [])
                      ->pluck('kunci')
                      ->unique()
                      ->values();
                @endphp

                <div class="sub-panel" style="margin-bottom:14px;background:var(--gray-50,#f8fafc)">
                  <div class="sub-panel-head" style="margin-bottom:8px">
                    <div>
                      <h3>Ringkasan dokumen resmi</h3>
                      <p class="field-help">Susunan ini yang tercetak pada berkas Word warga.</p>
                    </div>
                  </div>
                  <div class="form-grid" style="gap:10px 22px">
                    <div>
                      <div class="label-cap" style="margin:0 0 4px">Judul surat</div>
                      <b>{{ \App\Support\TemplateSuratDesa::judul((string) $jenisSurat->slug) }}</b>
                    </div>
                    <div>
                      <div class="label-cap" style="margin:0 0 4px">Kepala desa tercetak</div>
                      <b>{{ \App\Support\PetaSuratDesa::NAMA_KEPALA_DESA }}</b>
                    </div>
                    <div>
                      <div class="label-cap" style="margin:0 0 4px">Kop surat</div>
                      <b>Gambar kop resmi desa (semua kategori)</b>
                    </div>
                    <div>
                      <div class="label-cap" style="margin:0 0 4px">Kerangka cetak</div>
                      <b>resources/surat/{{ $jenisSurat->slug }}</b>
                    </div>
                  </div>
                  <div style="margin-top:12px">
                    <div class="label-cap" style="margin:0 0 6px">Baris yang dicetak dari formulir warga</div>
                    <ul class="doc-bullets">
                      @foreach ($barisCetak as $kunciBaris)
                        <li>{{ $labelBaris[$kunciBaris] ?? ucfirst(str_replace('_', ' ', $kunciBaris)) }}</li>
                      @endforeach
                      @if (! empty($petaDokumen['frasa']))
                        <li>Pekerjaan pada KTP/KK dan pekerjaan sebenarnya (kalimat penjelas)</li>
                      @endif
                    </ul>
                  </div>
                </div>
              @elseif (! $baru)
                <div class="notice notice-amber" style="margin-bottom:12px">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
                  <span>
                    Template ini masih template bawaan sistem. Tempelkan isi surat dari dokumen resmi
                    desa (folder <code>docs/</code>) agar kop dan kalimatnya sesuai.
                  </span>
                </div>
              @endif

              <textarea id="template_surat" name="template_surat" rows="14" class="template-editor" spellcheck="false">{{ old('template_surat', $jenisSurat->template_surat) }}</textarea>
              <p class="field-help">
                Tulis kerangka surat. Setiap penanda <code>@{{nama_penanda}}</code> yang bukan data otomatis
                akan otomatis menjadi kolom isian warga. Klik penanda di bawah untuk menyisipkan.
              </p>

              <div class="template-bantuan">
                <span class="field-help" style="margin:0">Penanda otomatis:</span>
                @foreach (\App\Services\TemplateSuratService::PENANDA_OTOMATIS as $kunci => $label)
                  <button type="button" class="penanda-chip otomatis" data-penanda="{{ $kunci }}" title="{{ $label }}">{{ '{'.$kunci.'}' }}</button>
                @endforeach
              </div>
              <div class="template-bantuan">
                <span class="field-help" style="margin:0">Penanda frasa (muncul hanya bila data terisi):</span>
                <button type="button" class="penanda-chip frase" data-penanda="yang_berdomisili_di" title="Menjadi ' yang berdomisili di dusun …' bila isian dusun diisi.">{{ '{yang_berdomisili_di}' }}</button>
              </div>
              <div class="template-bantuan">
                <span class="field-help" style="margin:0">Contoh penanda kustom resmi:</span>
                <button type="button" class="penanda-chip" data-penanda="nama_usaha">{{ '{nama_usaha}' }}</button>
                <button type="button" class="penanda-chip" data-penanda="alamat_usaha">{{ '{alamat_usaha}' }}</button>
                <button type="button" class="penanda-chip" data-penanda="tanggal_meninggal">{{ '{tanggal_meninggal}' }}</button>
                <button type="button" class="penanda-chip" data-penanda="pekerjaan_sebenarnya">{{ '{pekerjaan_sebenarnya}' }}</button>
              </div>
            </div>

            <div class="panel-foot">
              <span>Perubahan konfigurasi tidak mengubah snapshot pengajuan lama.</span>
              <div style="display:flex; gap:10px; flex-wrap:wrap">
                @if (! $baru)
                  <button type="button" class="btn btn-outline" id="btnHapusJenis" data-nama="{{ $jenisSurat->nama }}">Hapus</button>
                @endif
                <a href="{{ route('admin.jenis-surat.index') }}" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan konfigurasi</button>
              </div>
            </div>
          </form>

          @if (! $baru)
            <form method="POST" action="{{ route('admin.jenis-surat.destroy', $jenisSurat) }}" id="formHapusJenis" style="display:none">
              @csrf
              @method('DELETE')
            </form>
          @endif
        </div>
      </main>
@endsection

@push('scripts')
<script>
  (function () {
    // ---------- Baris dinamis: field formulir & persyaratan ----------
    var TIPE = @json($tipeField);

    function buatBarisField(i) {
      var opsi = TIPE.map(function (t) {
        return '<option value="' + t + '">' + t.charAt(0).toUpperCase() + t.slice(1) + '</option>';
      }).join('');

      var d = document.createElement('div');
      d.className = 'baris-dinamis baris-field';
      d.innerHTML =
        '<div class="field"><label for="field-' + i + '-label">Label</label><input type="text" id="field-' + i + '-label" name="field[' + i + '][label]" placeholder="mis. Lama tinggal"></div>' +
        '<div class="field"><label for="field-' + i + '-kunci">Kunci field</label><input type="text" id="field-' + i + '-kunci" name="field[' + i + '][kunci]" placeholder="lama_tinggal" pattern="[a-z0-9_]*"></div>' +
        '<div class="field"><label for="field-' + i + '-tipe">Tipe</label><select id="field-' + i + '-tipe" name="field[' + i + '][tipe]">' + opsi + '</select></div>' +
        '<label class="centang" for="field-' + i + '-wajib"><input type="checkbox" id="field-' + i + '-wajib" name="field[' + i + '][wajib]" value="1"> Wajib</label>' +
        '<button type="button" class="baris-hapus" aria-label="Hapus field"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg></button>';
      return d;
    }

    function buatBarisSyarat(i) {
      var d = document.createElement('div');
      d.className = 'baris-dinamis baris-syarat';
      d.innerHTML =
        '<input type="hidden" name="persyaratan[' + i + '][id]" value="">' +
        '<div class="field"><label for="syarat-' + i + '-label">Nama dokumen</label><input type="text" id="syarat-' + i + '-label" name="persyaratan[' + i + '][label]" placeholder="mis. Fotokopi KTP"></div>' +
        '<div class="field"><label for="syarat-' + i + '-format">Format</label><input type="text" id="syarat-' + i + '-format" name="persyaratan[' + i + '][format]" placeholder="pdf,jpg,png"></div>' +
        '<div class="field"><label for="syarat-' + i + '-maks">Maks. KB</label><input type="number" id="syarat-' + i + '-maks" name="persyaratan[' + i + '][maks_kb]" min="64" max="10240" placeholder="2048"></div>' +
        '<label class="centang" for="syarat-' + i + '-wajib"><input type="checkbox" id="syarat-' + i + '-wajib" name="persyaratan[' + i + '][wajib]" value="1" checked> Wajib</label>' +
        '<button type="button" class="baris-hapus" aria-label="Hapus persyaratan"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg></button>';
      return d;
    }

    var wadahField = document.getElementById('wadahField');
    var wadahSyarat = document.getElementById('wadahSyarat');
    var hitungField = document.getElementById('hitungField');
    var hitungSyarat = document.getElementById('hitungSyarat');

    // Indeks terus bertambah agar nama input tidak pernah bentrok.
    var idxField = wadahField ? wadahField.children.length : 0;
    var idxSyarat = wadahSyarat ? wadahSyarat.children.length : 0;

    function perbaruiHitungan() {
      if (hitungField && wadahField) hitungField.textContent = wadahField.children.length;
      if (hitungSyarat && wadahSyarat) hitungSyarat.textContent = wadahSyarat.children.length;
    }

    document.getElementById('tambahField')?.addEventListener('click', function () {
      var baris = buatBarisField(idxField++);
      wadahField.appendChild(baris);
      baris.querySelector('input')?.focus();
      perbaruiHitungan();
    });

    document.getElementById('tambahSyarat')?.addEventListener('click', function () {
      var baris = buatBarisSyarat(idxSyarat++);
      wadahSyarat.appendChild(baris);
      baris.querySelector('input')?.focus();
      perbaruiHitungan();
    });

    // Hapus baris (delegasi agar berlaku untuk baris lama maupun baru).
    document.addEventListener('click', function (e) {
      var tombol = e.target.closest('.baris-hapus');
      if (! tombol) return;
      tombol.closest('.baris-dinamis')?.remove();
      perbaruiHitungan();
    });

    // ---------- Sisipkan penanda ke template ----------
    var template = document.getElementById('template_surat');
    document.querySelectorAll('[data-penanda]').forEach(function (chip) {
      chip.addEventListener('click', function () {
        if (! template) return;
        var teks = '@{{' + chip.dataset.penanda + '}}';
        var awal = template.selectionStart ?? template.value.length;
        var akhir = template.selectionEnd ?? template.value.length;
        template.value = template.value.slice(0, awal) + teks + template.value.slice(akhir);
        template.focus();
        template.selectionStart = template.selectionEnd = awal + teks.length;
      });
    });

    // ---------- Hapus jenis surat ----------
    document.getElementById('btnHapusJenis')?.addEventListener('click', function () {
      var nama = this.dataset.nama || 'jenis surat ini';
      if (window.confirm('Hapus "' + nama + '" secara permanen?\n\nBila sudah ada pengajuan warga, penghapusan akan ditolak. Nonaktifkan saja bila ragu.')) {
        document.getElementById('formHapusJenis')?.submit();
      }
    });
  })();
</script>
@endpush
