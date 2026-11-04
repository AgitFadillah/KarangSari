@extends('layouts.public')

@section('title', 'Formulir Pengajuan '.$jenisSurat->nama.' · '.$profil->nama_desa)
@section('description', 'Formulir pengajuan surat desa: data pemohon, unggah dokumen persyaratan, dan persetujuan penggunaan data.')

@section('content')
{{-- topbar + header dari partial --}}

  <!-- Hero formulir -->
  <section class="page-hero page-hero-soft">
    <div class="container">
      <nav class="breadcrumb">
        <a href="{{ route('beranda') }}">Beranda</a>
        <span class="sep">/</span>
        <a href="{{ route('pelayanan') }}">Pelayanan</a>
        <span class="sep">/</span>
        <a href="{{ route('layanan.detail', $jenisSurat->slug) }}">{{ $jenisSurat->nama }}</a>
        <span class="sep">/</span>
        <span>Formulir pengajuan</span>
      </nav>
      <div class="page-hero-row">
        <div>
          <div class="eyebrow eyebrow-plain" style="color:var(--blue-600)">Formulir Pengajuan</div>
          <h1>{{ $jenisSurat->nama }}</h1>
          <p class="lead">{{ $jenisSurat->deskripsi }}</p>
        </div>
        @if ($jenisSurat->estimasi_hari)
          <span class="chip">Estimasi {{ $jenisSurat->estimasi_hari }}</span>
        @endif
      </div>
    </div>
  </section>

  <!-- Form -->
  <section class="section form-page">
    <div class="container">
      <form id="pengajuanForm" class="form-layout" method="POST" action="{{ route('pengajuan.store', $jenisSurat->slug) }}" enctype="multipart/form-data" novalidate>
        @csrf
        <div>
          @if ($errors->any())
            <div class="form-alert show" role="alert">
              Lengkapi semua kolom bertanda *, unggah dokumen persyaratan, dan setujui pemberitahuan data sebelum mengirim pengajuan.
              <ul style="margin:8px 0 0 18px">
                @foreach ($errors->all() as $pesan)
                  <li>{{ $pesan }}</li>
                @endforeach
              </ul>
            </div>
          @else
            <div class="form-alert" id="formAlert" role="alert"></div>
          @endif

          <!-- 1. Data pemohon -->
          <div class="form-card">
            <div class="form-card-head">
              <span class="form-step-num sq">1</span>
              <div>
                <div style="font-weight:700;font-size:1.0625rem">Data pemohon</div>
                <div class="sub">Isi sesuai dokumen resmi. Kolom bertanda * wajib diisi.</div>
              </div>
            </div>
            <hr class="form-section-hr">
            <div class="form-grid">
              <div class="field">
                <label for="nama">Nama lengkap <span class="req">*</span></label>
                <input type="text" id="nama" name="nama" value="{{ old('nama') }}" placeholder="Sesuai KTP" required autocomplete="name" @class(['invalid' => $errors->has('nama')])>
                @error('nama') <div class="field-help" style="color:var(--red-600)">{{ $message }}</div> @enderror
              </div>
              <div class="field">
                <label for="nik">NIK <span class="req">*</span></label>
                <input type="text" id="nik" name="nik" value="{{ old('nik') }}" placeholder="16 digit angka" required inputmode="numeric" pattern="[0-9]*" maxlength="16" @class(['invalid' => $errors->has('nik')])>
                @error('nik') <div class="field-help" style="color:var(--red-600)">{{ $message }}</div> @enderror
              </div>
              <div class="field">
                <label for="tempat_lahir">Tempat lahir <span class="req">*</span></label>
                <input type="text" id="tempat_lahir" name="tempat_lahir" value="{{ old('tempat_lahir') }}" required @class(['invalid' => $errors->has('tempat_lahir')])>
                @error('tempat_lahir') <div class="field-help" style="color:var(--red-600)">{{ $message }}</div> @enderror
              </div>
              <div class="field">
                <label for="tanggal_lahir">Tanggal lahir <span class="req">*</span></label>
                <input type="date" id="tanggal_lahir" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}" required max="{{ now()->subDay()->toDateString() }}" @class(['invalid' => $errors->has('tanggal_lahir')])>
                @error('tanggal_lahir') <div class="field-help" style="color:var(--red-600)">{{ $message }}</div> @enderror
              </div>
              <div class="field">
                <label for="jenis_kelamin">Jenis kelamin <span class="req">*</span></label>
                <select id="jenis_kelamin" name="jenis_kelamin" required @class(['invalid' => $errors->has('jenis_kelamin')])>
                  <option value="">Pilih salah satu</option>
                  <option value="Laki-laki" @selected(old('jenis_kelamin') === 'Laki-laki')>Laki-laki</option>
                  <option value="Perempuan" @selected(old('jenis_kelamin') === 'Perempuan')>Perempuan</option>
                </select>
                @error('jenis_kelamin') <div class="field-help" style="color:var(--red-600)">{{ $message }}</div> @enderror
              </div>
              <div class="field span-2">
                <label for="alamat">Alamat lengkap <span class="req">*</span></label>
                <textarea id="alamat" name="alamat" placeholder="Jalan, RT/RW, dusun" required @class(['invalid' => $errors->has('alamat')])>{{ old('alamat') }}</textarea>
                @error('alamat') <div class="field-help" style="color:var(--red-600)">{{ $message }}</div> @enderror
              </div>
              <div class="field span-2">
                <label for="keperluan">Keperluan surat <span class="req">*</span></label>
                <textarea id="keperluan" name="keperluan" placeholder="Contoh: pendaftaran sekolah anak" required @class(['invalid' => $errors->has('keperluan')])>{{ old('keperluan') }}</textarea>
                @error('keperluan') <div class="field-help" style="color:var(--red-600)">{{ $message }}</div> @enderror
              </div>
            </div>
            <hr class="form-mid-hr">
            <div class="form-grid">
              <div class="field">
                <label for="whatsapp">Nomor WhatsApp aktif <span class="req">*</span></label>
                <input type="tel" id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" placeholder="081234567890" required autocomplete="tel" @class(['invalid' => $errors->has('whatsapp')])>
                <div class="field-help">Nomor ini digunakan admin untuk memberi kabar pengambilan surat atau permintaan perbaikan.</div>
                @error('whatsapp') <div class="field-help" style="color:var(--red-600)">{{ $message }}</div> @enderror
              </div>
            </div>
          </div>

          <!-- 2. Dokumen persyaratan -->
          <div class="form-card">
            <div class="form-card-head">
              <span class="form-step-num sq">2</span>
              <div>
                <div style="font-weight:700;font-size:1.0625rem">Dokumen persyaratan</div>
                <div class="sub">Unggah file JPG, PNG, atau PDF. Pastikan seluruh teks terbaca.</div>
              </div>
            </div>
            <hr class="form-section-hr">
            <div class="form-grid" id="docFields">
              @forelse ($jenisSurat->persyaratan as $index => $syarat)
                <div class="field">
                  <div class="field-label-row">
                    <label for="doc{{ $index }}">{{ $syarat->label }} <span class="req">*</span></label>
                    <span class="muted small">JPG, PNG, PDF · 2 MB</span>
                  </div>
                  <div class="dropzone" data-dropzone data-size-hint="klik untuk mengganti">
                    <input type="file" id="doc{{ $index }}" name="dokumen[{{ $index }}]" accept=".jpg,.jpeg,.png,.pdf" required hidden>
                    <div class="dz-default">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>
                      <div style="font-weight:600">Pilih file</div>
                      <div class="small muted" style="margin-top:4px">{{ $syarat->petunjuk ?: 'Pastikan dokumen terbaca jelas.' }}</div>
                    </div>
                    <div class="dropzone-file" style="display:none">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                      <div class="dz-meta">
                        <div class="dz-name"></div>
                        <div class="dz-size"></div>
                      </div>
                      <button type="button" class="dz-remove">Hapus</button>
                    </div>
                  </div>
                  @error('dokumen.'.$index) <div class="field-help" style="color:var(--red-600)">{{ $message }}</div> @enderror
                </div>
              @empty
                <p class="muted">Layanan ini belum memiliki persyaratan dokumen. Anda tetap dapat mengirim pengajuan.</p>
              @endforelse
            </div>
            @error('dokumen') <div class="field-help" style="color:var(--red-600);margin-top:12px">{{ $message }}</div> @enderror
            <p class="field-help" style="margin-top:16px">File disimpan di penyimpanan privat dan hanya dapat diakses admin desa yang berwenang. Nama file asli tidak digunakan sebagai lokasi penyimpanan.</p>
          </div>

          <!-- 3. Persetujuan -->
          <div class="form-card">
            <div class="form-card-head">
              <span class="form-step-num sq">3</span>
              <div>
                <div style="font-weight:700;font-size:1.0625rem">Persetujuan penggunaan data</div>
                <div class="sub">Baca pemberitahuan privasi sebelum mengirim pengajuan.</div>
              </div>
            </div>
            <hr class="form-section-hr">
            <details class="privacy-details" id="privacyBox">
              <summary>
                Pemberitahuan privasi
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
              </summary>
              <div class="privacy-body">
                <p>Data yang Anda isi digunakan hanya untuk memproses permohonan surat pada jenis layanan yang dipilih.</p>
                <p>Dokumen persyaratan disimpan di penyimpanan privat, tidak dapat diakses pengunjung umum, dan hanya diperiksa oleh admin desa.</p>
                <p>Nomor WhatsApp digunakan admin untuk menginformasikan hasil pemeriksaan, permintaan perbaikan, dan pengambilan surat.</p>
                <p>Selengkapnya pada <a href="{{ route('privasi.pemberitahuan') }}">pemberitahuan data</a> dan <a href="{{ route('privasi.kebijakan') }}">kebijakan privasi</a>.</p>
              </div>
            </details>
            <div class="checkbox-row">
              <input type="checkbox" id="persetujuan" name="persetujuan" value="1" required @checked(old('persetujuan'))>
              <label for="persetujuan">Saya telah membaca pemberitahuan privasi dan menyetujui penggunaan data saya untuk memproses pengajuan ini.</label>
            </div>
            @error('persetujuan') <div class="field-help" style="color:var(--red-600);margin-top:8px">{{ $message }}</div> @enderror
          </div>

          <div class="form-actions">
            <a href="{{ route('layanan.detail', $jenisSurat->slug) }}" class="back-link" id="backLink">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
              Kembali ke detail layanan
            </a>
            <button type="submit" class="btn btn-primary">
              Kirim pengajuan
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </button>
          </div>
          <p class="form-fine">Dengan mengirim, Anda memahami bahwa surat tetap diambil secara fisik di kantor desa.</p>
        </div>

        <!-- Sidebar ringkasan -->
        <aside>
          <div class="panel">
            <div class="label-cap" style="margin-top:0">Ringkasan layanan</div>
            <div class="summary-title">{{ $jenisSurat->nama }}</div>
            <div class="kv-row"><span class="k">Estimasi</span><span class="v">{{ $jenisSurat->estimasi_hari ?: 'Menyesuaikan' }}</span></div>
            <div class="kv-row"><span class="k">Data diisi</span><span class="v">7 kolom</span></div>
            <div class="kv-row"><span class="k">Dokumen</span><span class="v">{{ $jenisSurat->persyaratan->count() }} berkas</span></div>
            <div class="kv-row"><span class="k">Biaya</span><span class="v green">Tidak dipungut</span></div>
            <div class="label-cap">Dokumen wajib</div>
            <ul class="doc-bullets">
              @foreach ($jenisSurat->persyaratan as $syarat)
                <li>{{ $syarat->label }}</li>
              @endforeach
            </ul>
          </div>
          <div class="panel panel-flat">
            <div style="font-weight:700;margin-bottom:8px">Butuh bantuan?</div>
            <p style="font-size:.9rem;color:var(--gray-600);margin-bottom:14px">Jika dokumen tidak tersedia dalam bentuk digital, tanyakan ke petugas desa terlebih dahulu.</p>
            <a href="{{ route('kontak') }}" class="panel-link">Hubungi kantor desa →</a>
          </div>
          <div class="panel">
            <div class="label-cap" style="margin-top:0">Ganti jenis surat</div>
            <ul class="doc-bullets">
              @foreach ($jenisSurats as $lain)
                <li>
                  @if ($lain->is($jenisSurat))
                    <b>{{ $lain->nama }}</b>
                  @else
                    <a href="{{ route('pengajuan.create', ['jenis' => $lain->slug]) }}">{{ $lain->nama }}</a>
                  @endif
                </li>
              @endforeach
            </ul>
          </div>
        </aside>
      </form>
    </div>
  </section>

  {{-- footer dari partial --}}
@endsection

@push('scripts')
<script>
(fn () {
  const form = document.getElementById('pengajuanForm');
  const alertBox = document.getElementById('formAlert');

  // Tandai kolom invalid di sisi klien; server tetap memvalidasi ulang.
  form.addEventListener('submit', (e) => {
    let firstInvalid = null;
    form.querySelectorAll('[required]').forEach((el) => {
      let ok;
      if (el.type === 'file') ok = el.files.length > 0;
      else if (el.type === 'checkbox') ok = el.checked;
      else ok = el.value.trim() !== '';
      el.classList.toggle('invalid', !ok);
      if (!ok && !firstInvalid) firstInvalid = el;
    });

    if (firstInvalid && alertBox) {
      e.preventDefault();
      alertBox.textContent = 'Lengkapi semua kolom bertanda *, unggah dokumen persyaratan, dan setujui pemberitahuan data sebelum mengirim pengajuan.';
      alertBox.classList.add('show');
      const target = firstInvalid.type === 'file' ? firstInvalid.closest('[data-dropzone]') : firstInvalid;
      if (target) {
        if (target.tagName === 'DIV') target.setAttribute('tabindex', '-1');
        target.focus();
        target.scrollIntoView({ block: 'center', behavior: 'smooth' });
      }
    }

    // Cegah kiriman ganda.
    if (!e.defaultPrevented) {
      const btn = form.querySelector('button[type=submit]');
      if (btn) { btn.disabled = true; btn.style.opacity = '.7'; }
    }
  });

  form.querySelectorAll('[required]').forEach((el) => {
    el.addEventListener('input', () => el.classList.remove('invalid'));
    el.addEventListener('change', () => el.classList.remove('invalid'));
  });
})();
</script>
@endpush
