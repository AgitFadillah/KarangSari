@extends('layouts.public')

@section('title', 'Perbaiki Pengajuan '.$pengajuan->nomor_referensi.' · '.$profil->nama_desa)
@section('description', 'Perbaiki isian surat pengajuan desa tanpa membuat pengajuan baru. Nomor referensi Anda tetap sama.')

@section('content')
{{-- topbar + header dari partial --}}

  <!-- Hero revisi -->
  <section class="page-hero page-hero-soft">
    <div class="container">
      <nav class="breadcrumb">
        <a href="{{ route('beranda') }}">Beranda</a>
        <span class="sep">/</span>
        <a href="{{ route('cek-status.form') }}">Cek status</a>
        <span class="sep">/</span>
        <span>Perbaiki pengajuan</span>
      </nav>
      <div class="page-hero-row">
        <div>
          <div class="eyebrow eyebrow-plain" style="color:var(--blue-600)">Revisi pengajuan</div>
          <h1>Perbaiki pengajuan {{ $pengajuan->nomor_referensi }}</h1>
          <p class="lead">Admin desa meminta perbaikan. Cukup perbaiki isian di bawah &mdash; nomor referensi Anda tetap sama dan tidak perlu mengajukan surat baru.</p>
        </div>
        <span class="chip">{{ $jenisSurat->nama }}</span>
      </div>
    </div>
  </section>

  <!-- Form revisi -->
  <section class="section form-page">
    <div class="container">
      <form id="pengajuanForm" class="form-layout" method="POST" action="{{ route('pengajuan.revisi.simpan', $pengajuan->nomor_referensi) }}" enctype="multipart/form-data" novalidate>
        @csrf
        {{-- Kode akses ikut dikirim di badan formulir, sehingga penyimpanan
             revisi tetap sah walau sesi perangkat tidak bertahan. --}}
        <input type="hidden" name="kode" value="{{ $kodeAkses }}">
        <div>
          @if ($errors->any())
            <div class="form-alert show" role="alert">
              Lengkapi semua kolom bertanda * sebelum mengirim perbaikan.
              <ul style="margin:8px 0 0 18px">
                @foreach ($errors->all() as $pesan)
                  <li>{{ $pesan }}</li>
                @endforeach
              </ul>
            </div>
          @else
            <div class="form-alert" id="formAlert" role="alert"></div>
          @endif

          @if ($pengajuan->catatan_admin)
            <div class="notice notice-amber" role="note" style="margin-bottom:20px">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
              <span>Permintaan perbaikan dari petugas desa: {{ $pengajuan->catatan_admin }}</span>
            </div>
          @endif

          <!-- 1. Data pemohon -->
          <div class="form-card">
            <div class="form-card-head">
              <span class="form-step-num sq">1</span>
              <div>
                <div style="font-weight:700;font-size:1.0625rem">Data pemohon</div>
                <div class="sub">Periksa kembali data berikut. Kolom bertanda * wajib diisi.</div>
              </div>
            </div>
            <hr class="form-section-hr">
            <div class="form-grid">
              <div class="field span-2">
                <label for="nama">Nama lengkap <span class="req">*</span></label>
                <input type="text" id="nama" name="nama" value="{{ old('nama', $pengajuan->nama) }}" placeholder="Sesuai KTP" required autocomplete="name" @class(['invalid' => $errors->has('nama')])>
                @error('nama') <div class="field-help" style="color:var(--red-600)">{{ $message }}</div> @enderror
              </div>
              <div class="field">
                <label for="nik">NIK <span class="req">*</span></label>
                <input type="text" id="nik" name="nik" value="{{ old('nik', $pengajuan->nik) }}" placeholder="16 digit sesuai KTP" inputmode="numeric" maxlength="16" pattern="[0-9]{16}" required @class(['invalid' => $errors->has('nik')])>
                @error('nik') <div class="field-help" style="color:var(--red-600)">{{ $message }}</div> @enderror
              </div>
              <div class="field">
                <label for="tempat_lahir">Tempat lahir <span class="req">*</span></label>
                <input type="text" id="tempat_lahir" name="tempat_lahir" value="{{ old('tempat_lahir', $pengajuan->tempat_lahir) }}" placeholder="Contoh: Lampung Selatan" required @class(['invalid' => $errors->has('tempat_lahir')])>
                @error('tempat_lahir') <div class="field-help" style="color:var(--red-600)">{{ $message }}</div> @enderror
              </div>
              <div class="field">
                <label for="tanggal_lahir">Tanggal lahir <span class="req">*</span></label>
                <input type="date" id="tanggal_lahir" name="tanggal_lahir" value="{{ old('tanggal_lahir', $pengajuan->tanggal_lahir?->toDateString()) }}" max="{{ now()->subDay()->toDateString() }}" required @class(['invalid' => $errors->has('tanggal_lahir')])>
                @error('tanggal_lahir') <div class="field-help" style="color:var(--red-600)">{{ $message }}</div> @enderror
              </div>
              <div class="field"></div>
              <div class="field span-2">
                <label for="alamat">Alamat lengkap <span class="req">*</span></label>
                <textarea id="alamat" name="alamat" placeholder="Jalan, RT/RW, dusun" required @class(['invalid' => $errors->has('alamat')])>{{ old('alamat', $pengajuan->alamat) }}</textarea>
                @error('alamat') <div class="field-help" style="color:var(--red-600)">{{ $message }}</div> @enderror
              </div>
              <div class="field span-2">
                <label for="keperluan">Keperluan surat <span class="req">*</span></label>
                <textarea id="keperluan" name="keperluan" placeholder="Contoh: pendaftaran sekolah anak" required @class(['invalid' => $errors->has('keperluan')])>{{ old('keperluan', $pengajuan->keperluan) }}</textarea>
                @error('keperluan') <div class="field-help" style="color:var(--red-600)">{{ $message }}</div> @enderror
              </div>
            </div>
            <hr class="form-mid-hr">
            <div class="form-grid">
              <div class="field">
                <label for="whatsapp">Nomor WhatsApp aktif <span class="req">*</span></label>
                <input type="tel" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $pengajuan->whatsapp) }}" placeholder="081234567890" required autocomplete="tel" @class(['invalid' => $errors->has('whatsapp')])>
                <div class="field-help">Nomor ini digunakan admin untuk memberi kabar pengambilan surat atau permintaan perbaikan.</div>
                @error('whatsapp') <div class="field-help" style="color:var(--red-600)">{{ $message }}</div> @enderror
              </div>
            </div>
          </div>

          <!-- 2. Isi surat sesuai template desa -->
          <div class="form-card">
            <div class="form-card-head">
              <span class="form-step-num sq">2</span>
              <div>
                <div style="font-weight:700;font-size:1.0625rem">Isi surat</div>
                <div class="sub">Perbaiki isian berikut. Surat akan tersusun otomatis sesuai template resmi {{ $profil->nama_desa }}.</div>
              </div>
            </div>
            <hr class="form-section-hr">

            @if (empty($penanda))
              <p class="muted">Template surat ini tidak memerlukan isian tambahan. Data pemohon di atas sudah cukup.</p>
            @else
              <div class="form-grid">
                @foreach ($penanda as $p)
                  @php
                    $tipe = $p['tipe'] ?? 'text';
                    $wajib = ($p['wajib'] ?? true) !== false;
                    $kunci = $p['kunci'];
                    $nilaiLama = old('isian_surat.'.$kunci, ($pengajuan->isian_surat ?? [])[$kunci] ?? '');
                  @endphp
                  <div class="field {{ $tipe === 'textarea' ? 'span-2' : '' }}">
                    <label for="isian_{{ $kunci }}">{{ $p['label'] }} @if ($wajib)<span class="req">*</span>@endif</label>
                    @if ($tipe === 'textarea')
                      <textarea id="isian_{{ $kunci }}"
                                name="isian_surat[{{ $kunci }}]"
                                data-isian="{{ $kunci }}"
                                rows="3" maxlength="2000" @if ($wajib) required @endif
                                @class(['invalid' => $errors->has('isian_surat.'.$kunci)])>{{ $nilaiLama }}</textarea>
                    @elseif ($tipe === 'date')
                      <input type="date" id="isian_{{ $kunci }}"
                             name="isian_surat[{{ $kunci }}]"
                             value="{{ $nilaiLama }}"
                             data-isian="{{ $kunci }}"
                             @if ($wajib) required @endif
                             @class(['invalid' => $errors->has('isian_surat.'.$kunci)])>
                    @elseif ($tipe === 'number')
                      <input type="number" id="isian_{{ $kunci }}"
                             name="isian_surat[{{ $kunci }}]"
                             value="{{ $nilaiLama }}"
                             data-isian="{{ $kunci }}"
                             inputmode="numeric" step="any"
                             @if ($wajib) required @endif
                             @class(['invalid' => $errors->has('isian_surat.'.$kunci)])>
                    @else
                      <input type="text" id="isian_{{ $kunci }}"
                             name="isian_surat[{{ $kunci }}]"
                             value="{{ $nilaiLama }}"
                             data-isian="{{ $kunci }}"
                             maxlength="200" @if ($wajib) required @endif
                             @class(['invalid' => $errors->has('isian_surat.'.$kunci)])>
                    @endif
                    @error('isian_surat.'.$kunci) <div class="field-help" style="color:var(--red-600)">{{ $message }}</div> @enderror
                  </div>
                @endforeach
              </div>
            @endif

            <hr class="form-mid-hr">
            <div class="label-cap">Pratinjau surat</div>
            {{-- Kop resmi desa memakai gambar yang sama dengan yang disisipkan
                 ke berkas Word, jadi pratinjau di layar persis seperti hasil
                 cetak. Judul dan nomor surat sudah ada di dalam teks surat. --}}
            <div class="letter letter-warga" id="pratinjauSurat" style="margin-top:12px">
              <img class="letter-kop" src="/images/kop-surat.png"
                   alt="Kop surat Pemerintah Kabupaten Lampung Selatan, Kecamatan Jati Agung, Desa Karang Sari">
              <p id="pratinjauIsi" style="white-space:pre-line">{{ $pratinjau }}</p>
            </div>
            <p class="field-help" style="margin-top:16px">Pratinjau diperbarui otomatis saat Anda mengisi kolom di atas. Isian surat disimpan bersama pengajuan dan hanya dapat dibaca admin desa.</p>
          </div>

          <!-- 3. Dokumen persyaratan (unggah ulang bila diminta) -->
          @if ($jenisSurat->persyaratan->isNotEmpty())
            <div class="form-card">
              <div class="form-card-head">
                <span class="form-step-num sq">3</span>
                <div>
                  <div style="font-weight:700;font-size:1.0625rem">Dokumen persyaratan</div>
                  <div class="sub">Unggah ulang hanya bila admin memintanya. Dokumen lama tetap tersimpan.</div>
                </div>
              </div>
              <hr class="form-section-hr">

              @foreach ($jenisSurat->persyaratan as $syarat)
                @php
                  $kunciDokumen = 'dokumen.'.$syarat->id;
                  $formatLabel = $syarat->format
                      ? strtoupper(str_replace(',', ', ', $syarat->format))
                      : 'JPG, PNG, PDF';
                  $maksLabel = $syarat->maks_kb
                      ? ($syarat->maks_kb >= 1024
                          ? rtrim(rtrim(number_format($syarat->maks_kb / 1024, 1, ',', '.'), '0'), ',').' MB'
                          : $syarat->maks_kb.' KB')
                      : '2 MB';
                  $sudahAda = $pengajuan->dokumen->firstWhere('persyaratan_surat_id', $syarat->id);
                @endphp
                <div class="field mb-16">
                  <label for="dok_{{ $syarat->id }}">
                    {{ $syarat->label }}
                    @if ($sudahAda)
                      <span class="badge badge-green" style="font-weight:400">sudah diunggah</span>
                    @endif
                  </label>
                  <input type="file" id="dok_{{ $syarat->id }}"
                         name="dokumen[{{ $syarat->id }}]"
                         accept="{{ $syarat->format ? '.'.str_replace(',', ',.', $syarat->format) : '.jpg,.jpeg,.png,.pdf' }}"
                         @class(['invalid' => $errors->has($kunciDokumen)])>
                  <div class="field-help">
                    Format {{ $formatLabel }} · maksimal {{ $maksLabel }}.
                    Kosongkan bila tidak ingin mengganti.
                  </div>
                  @error($kunciDokumen) <div class="field-help" style="color:var(--red-600)">{{ $message }}</div> @enderror
                </div>
              @endforeach
            </div>
          @endif

          <!-- 4. Persetujuan -->
          <div class="form-card">
            <div class="form-card-head">
              <span class="form-step-num sq">{{ $jenisSurat->persyaratan->isNotEmpty() ? 4 : 3 }}</span>
              <div>
                <div style="font-weight:700;font-size:1.0625rem">Persetujuan penggunaan data</div>
                <div class="sub">Setujui kembali sebelum mengirim perbaikan.</div>
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
            <a href="{{ route('cek-status.form') }}" class="back-link" id="backLink">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
              Kembali ke cek status
            </a>
            <button type="submit" class="btn btn-primary">
              Kirim perbaikan
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </button>
          </div>
          <p class="form-fine">Nomor referensi <b>{{ $pengajuan->nomor_referensi }}</b> tidak berubah. Status akan kembali menjadi &ldquo;Menunggu Verifikasi&rdquo; setelah Anda mengirim perbaikan.</p>
        </div>

        <!-- Sidebar ringkasan -->
        <aside>
          <div class="panel">
            <div class="label-cap">Ringkasan pengajuan</div>
            <div class="summary-title">{{ $jenisSurat->nama }}</div>
            <div class="kv-row"><span class="k">Nomor referensi</span><span class="v code">{{ $pengajuan->nomor_referensi }}</span></div>
            <div class="kv-row"><span class="k">Revisi ke</span><span class="v">{{ ((int) $pengajuan->revisi_ke) + 1 }}</span></div>
            <div class="kv-row"><span class="k">Status</span><span class="v"><span class="badge {{ $pengajuan->status->badge() }}">{{ $pengajuan->status->label() }}</span></span></div>
            <div class="kv-row"><span class="k">Biaya</span><span class="v green">Tidak dipungut</span></div>
          </div>
          <div class="panel panel-flat">
            <div style="font-weight:700;margin-bottom:8px">Butuh bantuan?</div>
            <p style="font-size:.9rem;color:var(--gray-600);margin-bottom:14px">Bingung bagian mana yang harus diperbaiki? Baca panduan atau hubungi petugas desa.</p>
            <a href="{{ route('panduan') }}" class="panel-link">Buka panduan pengajuan →</a>
            <a href="{{ route('kontak') }}" class="panel-link">Hubungi kantor desa →</a>
          </div>
        </aside>
      </form>
    </div>
  </section>

  {{-- footer dari partial --}}
@endsection

@push('scripts')
<script>
(() => {
  const form = document.getElementById('pengajuanForm');
  if (!form) return;

  const alertBox = document.getElementById('formAlert');

  // Pratinjau live: dibangun dari DOKUMEN WORD desa, bukan teks template,
  // sehingga tampilan di layar sama dengan berkas yang nanti dicetak.
  const pratinjau = document.getElementById('pratinjauIsi');
  const URL_PRATINJAU = @json(route('pengajuan.pratinjau', $jenisSurat));
  const CSRF = @json(csrf_token());

  let timerPratinjau = null;
  let permintaanTerakhir = 0;
  let sedangMengirim = false;
  let tertunda = false;

  function gambarPratinjau() {
    if (!pratinjau) return;

    const ambil = (id) => (document.getElementById(id)?.value || '').trim();

    const isian = {};
    form.querySelectorAll('[data-isian]').forEach((el) => {
      isian[el.dataset.isian.toLowerCase()] = (el.value || '').trim();
    });

    // Ditunda sebentar agar tidak membanjiri server saat mengetik.
    clearTimeout(timerPratinjau);
    timerPratinjau = setTimeout(kirimPratinjau, 260);

    async function kirimPratinjau() {
      // Satu permintaan pada satu waktu: bila warga masih mengetik saat
      // permintaan sebelumnya berjalan, cukup kirim sekali lagi setelahnya.
      // Ini mencegah puluhan permintaan bertumpuk yang bisa kena batas laju
      // dan membuat halaman revisi terblokir (429).
      if (sedangMengirim) {
        tertunda = true;

        return;
      }

      sedangMengirim = true;
      const urut = ++permintaanTerakhir;

      try {
        const respons = await fetch(URL_PRATINJAU, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json',
          },
          body: JSON.stringify({
            nama: ambil('nama'),
            nik: ambil('nik'),
            tempat_lahir: ambil('tempat_lahir'),
            tanggal_lahir: ambil('tanggal_lahir'),
            alamat: ambil('alamat'),
            keperluan: ambil('keperluan'),
            isian: isian,
          }),
        });

        // 429 bukan alasan menampilkan galat: biarkan pratinjau terakhir
        // tetap tampil supaya warga tidak panik saat mengisi formulir.
        if (!respons.ok) return;

        const data = await respons.json();

        if (urut === permintaanTerakhir && typeof data.teks === 'string') {
          pratinjau.textContent = data.teks;
        }
      } catch (e) {
        // Jaringan lambat: biarkan pratinjau terakhir tetap tampil.
      } finally {
        sedangMengirim = false;

        if (tertunda) {
          tertunda = false;
          clearTimeout(timerPratinjau);
          timerPratinjau = setTimeout(kirimPratinjau, 260);
        }
      }
    }
  }

  form.querySelectorAll('input, textarea, select').forEach((el) => {
    el.addEventListener('input', gambarPratinjau);
    el.addEventListener('change', gambarPratinjau);
  });

  gambarPratinjau();

  form.addEventListener('submit', (e) => {
    let firstInvalid = null;

    form.querySelectorAll('[required]').forEach((el) => {
      const ok = el.type === 'checkbox' ? el.checked : el.value.trim() !== '';
      el.classList.toggle('invalid', !ok);
      if (!ok && !firstInvalid) firstInvalid = el;
    });

    if (firstInvalid && alertBox) {
      e.preventDefault();
      alertBox.textContent = 'Lengkapi semua kolom bertanda * dan setujui pemberitahuan data sebelum mengirim perbaikan.';
      alertBox.classList.add('show');
      firstInvalid.focus();
      firstInvalid.scrollIntoView({ block: 'center', behavior: 'smooth' });
      return;
    }

    const btn = form.querySelector('button[type=submit]');
    if (btn) { btn.disabled = true; btn.style.opacity = '.7'; }
  });

  form.querySelectorAll('[required]').forEach((el) => {
    el.addEventListener('input', () => el.classList.remove('invalid'));
    el.addEventListener('change', () => el.classList.remove('invalid'));
  });
})();
</script>
@endpush
