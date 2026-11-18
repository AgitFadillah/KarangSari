@extends('layouts.public')

@section('title', 'Cek Status Pengajuan · '.$profil->nama_desa)
@section('description', 'Cek status pengajuan surat desa dengan nomor referensi. Tidak perlu membuat akun.')

@section('content')

  <section class="page-hero page-hero-soft">
    <div class="container">
      <nav class="breadcrumb">
        <a href="{{ route('beranda') }}">Beranda</a>
        <span class="sep">/</span>
        <a href="{{ route('pelayanan') }}">Pelayanan</a>
        <span class="sep">/</span>
        <span>Cek status</span>
      </nav>
      <div class="page-hero-row">
        <div>
          <div class="eyebrow eyebrow-plain" style="color:var(--blue-600)">Pelacakan pengajuan</div>
          <h1>Cek status pengajuan surat</h1>
          <p class="lead">Masukkan nomor referensi <b>dan kode akses</b> yang Anda terima setelah mengirim pengajuan, misalnya {{ config('desa.prefix_referensi') }}-{{ now()->year }}-0001.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="section form-page">
    <div class="container">
      <form method="POST" action="{{ route('cek-status.cari') }}" class="form-layout" style="grid-template-columns:1fr">
        @csrf
        <div>
          @if ($errors->any())
            <div class="form-alert show" role="alert">
              <ul style="margin:0 0 0 18px">
                @foreach ($errors->all() as $pesan)
                  <li>{{ $pesan }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <div class="form-card">
            <div class="form-card-head">
              <span class="form-step-num sq">1</span>
              <div>
                <div style="font-weight:700;font-size:1.0625rem">Nomor referensi &amp; kode akses</div>
                <div class="sub">Nomor referensi sudah terisi otomatis. Anda hanya perlu menempel kode akses dari halaman konfirmasi.</div>
              </div>
            </div>
            <hr class="form-section-hr">
            @php
              // Nomor referensi terisi otomatis dari pengajuan yang baru dikirim
              // (disimpan di sesi). Warga hanya perlu menempelkan kode akses —
              // satu hal untuk disalin, bukan dua.
              $nomorTersimpan = old('nomor_referensi', session('nomor_referensi_terakhir'));
              $nomorTerkunci = filled($nomorTersimpan) && ! $errors->any();
            @endphp
            <div class="form-grid">
              <div class="field span-2">
                <label for="nomor_referensi">Nomor referensi <span class="req">*</span></label>
                <input type="text" id="nomor_referensi" name="nomor_referensi"
                       value="{{ $nomorTersimpan }}"
                       placeholder="{{ config('desa.prefix_referensi') }}-{{ now()->year }}-0001"
                       required autocomplete="off" spellcheck="false"
                       style="text-transform:uppercase"
                       @readonly($nomorTerkunci)
                       @class(['invalid' => $errors->has('nomor_referensi'), 'is-locked' => $nomorTerkunci])>
                <div class="field-help" id="nomorTerkunciInfo" @if (! $nomorTerkunci) hidden @endif>
                  Terisi otomatis dari pengajuan yang baru Anda kirim.
                  <a href="#" id="ubahNomor">Bukan pengajuan ini? Ketik nomor lain</a>
                </div>
              </div>
              <div class="field span-2">
                <label for="kode_akses">Kode akses <span class="req">*</span></label>
                <input type="text" id="kode_akses" name="kode_akses"
                       value="{{ old('kode_akses') }}"
                       placeholder="Contoh: K7QM3FZP"
                       required autocomplete="off" autocapitalize="characters" spellcheck="false"
                       maxlength="32" inputmode="text"
                       @if ($nomorTerkunci) autofocus @endif
                       style="text-transform:uppercase;letter-spacing:.12em;font-family:ui-monospace,SFMono-Regular,Menlo,monospace"
                       @class(['invalid' => $errors->has('kode_akses')])>
                <div class="field-help">Hanya kode akses yang perlu Anda salin. Tempel di sini &mdash; huruf kecil, spasi, atau tanda hubung otomatis dirapikan. Demi keamanan, halaman ini hanya menampilkan status dan catatan admin, bukan NIK, alamat, atau dokumen Anda. Kode akses bersifat rahasia &mdash; jangan dibagikan ke orang lain.</div>
                <div class="field-help" style="margin-top:8px">
                  Lupa atau kehilangan kode akses?
                  <a href="{{ route('cek-status.lupa.form') }}"><b>Pulihkan dengan tanggal lahir</b></a>.
                </div>
              </div>
            </div>
            <div class="form-actions" style="margin-top:20px">
              <a href="{{ route('pelayanan') }}" class="back-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                Kembali ke daftar layanan
              </a>
              <button type="submit" class="btn btn-primary">
                Cek status
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
              </button>
            </div>
          </div>

          @if ($pengajuan)
            <div class="form-card">
              <div class="form-card-head">
                <span class="form-step-num sq">2</span>
                <div>
                  <div style="font-weight:700;font-size:1.0625rem">Hasil pencarian</div>
                  <div class="sub">{{ $pengajuan->jenisSurat->nama }}</div>
                </div>
              </div>
              <hr class="form-section-hr">

              @if ($dipulihkan ?? false)
                <div class="notice notice-blue" style="margin-bottom:16px" role="note">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                  <span>Data ditemukan. Catat kembali nomor referensi dan kode akses di bawah ini, lalu simpan di tempat yang aman.</span>
                </div>
              @endif

              <div class="kv-row"><span class="k">Nomor referensi</span><span class="v code" id="refHasil">{{ $pengajuan->nomor_referensi }}</span></div>
              @if (filled($kodeAkses))
                <div class="kv-row">
                  <span class="k">Kode akses</span>
                  <span class="v code" id="kodeAksesHasil">{{ $kodeAkses }}</span>
                </div>
                <div class="form-actions" style="margin-top:8px;justify-content:flex-start">
                  <button type="button" class="btn btn-outline btn-sm" data-copy="#kodeAksesHasil">Salin kode akses</button>
                  <button type="button" class="btn btn-outline btn-sm" data-copy="#refHasil">Salin nomor referensi</button>
                </div>
              @endif
              <div class="kv-row"><span class="k">Nama pemohon</span><span class="v">{{ $pengajuan->nama }}</span></div>
              <div class="kv-row"><span class="k">Diajukan</span><span class="v">{{ $pengajuan->created_at->locale('id')->translatedFormat('j F Y, H.i') }} WIB</span></div>
              <div class="kv-row"><span class="k">Status</span><span class="v"><span class="badge {{ $pengajuan->status->badge() }}">{{ $pengajuan->status->label() }}</span></span></div>
              <div class="kv-row"><span class="k">Estimasi</span><span class="v">{{ $pengajuan->jenisSurat->estimasi_hari ?: 'Menyesuaikan' }}</span></div>

              @if ($pengajuan->catatan_admin)
                <div class="notice notice-amber" style="margin-top:16px" role="note">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
                  <span>Catatan dari petugas desa: {{ $pengajuan->catatan_admin }}</span>
                </div>
              @endif

              @if ($pengajuan->perluPerbaikan())
                <div class="notice notice-blue" style="margin-top:16px" role="note">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                  <span>Pengajuan ini perlu diperbaiki. Perbaiki langsung di halaman revisi &mdash; nomor referensi Anda tidak berubah dan tidak perlu mengajukan surat baru.</span>
                </div>
                <div class="form-actions" style="margin-top:16px">
                  <a href="{{ route('pengajuan.revisi', $pengajuan->nomor_referensi) }}" class="btn btn-primary">
                    Perbaiki pengajuan
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                  </a>
                </div>
              @endif

              {{-- Surat boleh diunduh setelah disetujui, termasuk saat sedang
                   diproses/siap diambil. Berkasnya memakai dokumen resmi desa,
                   jadi tampilannya sama dengan surat yang dicetak. --}}
              @if ($pengajuan->suratBolehDiunduh())
                <div class="notice notice-blue" style="margin-top:16px" role="note">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                  <span>Surat Anda sudah disetujui dan dapat diunduh. Berkas Word ini memakai format resmi desa.</span>
                </div>
                <div class="form-actions" style="margin-top:16px">
                  <a href="{{ route('pengajuan.surat', $pengajuan->nomor_referensi) }}" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12M7 11l5 5 5-5M4 21h16"/></svg>
                    Unduh surat (.docx)
                  </a>
                </div>
              @endif

              <div class="label-cap" style="margin-top:24px">Riwayat pengajuan</div>
              <div class="timeline">
                @foreach ($pengajuan->riwayat as $riwayat)
                  <div class="timeline-item">
                    <span class="timeline-dot {{ $riwayat->status->isSelesai() ? '' : 'green' }}" aria-hidden="true"></span>
                    <div class="between">
                      <b>{{ $riwayat->status->label() }}</b>
                      <span class="badge {{ $riwayat->badgeAktor() }}">{{ ucfirst($riwayat->aktor) }}</span>
                    </div>
                    <div class="timeline-meta">{{ $riwayat->created_at->locale('id')->translatedFormat('j M Y, H.i') }}</div>
                    @if ($riwayat->catatan)
                      <p class="small muted mt-8">{{ $riwayat->catatan }}</p>
                    @endif
                  </div>
                @endforeach
              </div>

              @if ($pengajuan->revisi_ke > 0)
                <p class="field-help" style="margin-top:16px">Anda sudah mengirim perbaikan sebanyak {{ $pengajuan->revisi_ke }} kali untuk pengajuan ini.</p>
              @endif

              <p class="field-help" style="margin-top:16px">Bila status sudah <b>Siap Diambil</b>, bawa identitas asli ke kantor desa {{ $profil->jam_layanan }}.</p>
            </div>
          @endif
        </div>
      </form>
    </div>
  </section>

  @push('scripts')
    <script>
      // Nomor referensi terisi otomatis: warga cukup menempel kode akses.
      // Bila ternyata bukan pengajuan itu, nomor bisa dibuka kembali.
      (function () {
        const nomor = document.getElementById('nomor_referensi');
        const info = document.getElementById('nomorTerkunciInfo');
        const ubah = document.getElementById('ubahNomor');
        if (!nomor || !info || !ubah) return;

        ubah.addEventListener('click', function (e) {
          e.preventDefault();
          nomor.readOnly = false;
          nomor.classList.remove('is-locked');
          nomor.focus();
          nomor.select();
          info.hidden = true;
        });
      })();
    </script>
  @endpush

@endsection
