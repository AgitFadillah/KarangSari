@extends('layouts.admin')

@section('title', 'Detail Pengajuan '.$pengajuan->nomor_referensi.' · Dashboard Admin '.$profil->nama_desa)

@push('styles')
<style>
    /* Tata letak mengikuti mockup admin/detail-pengajuan.html:
       kolom kiri = data & riwayat, kolom kanan = pratinjau surat. */
    .detail-head-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-top: 8px; }
    .field-block { padding: 12px 0; border-bottom: 1px solid var(--gray-200); min-width: 0; }
    .field-block .label-cap { margin: 0 0 4px; }
    .field-block b { display: block; font-size: .95rem; font-weight: 700; color: var(--gray-900); overflow-wrap: anywhere; }
    .data-list > * { min-width: 0; }
    .doc-file .dz-meta { min-width: 0; }
    .detail-letter { font-size: .75rem; padding: 28px 26px; }
    .detail-letter .letter-inst span { display: block; }
    .detail-letter .letter-fields .doc-row { grid-template-columns: 104px 10px 1fr; }

    /* Kolom pratinjau surat diberi porsi lebih besar daripada kolom data.
       Baris isian surat diselaraskan dengan tab; kalau kolomnya terlalu
       sempit, baris seperti ": Desa … Kecamatan …" terlipat dan titik
       duanya tidak lagi sejajar. */
    .detail-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1.25fr); }
    @media (max-width: 1280px) { .detail-grid { grid-template-columns: 1fr; } }
    /* Pratinjau surat: template resmi desa sudah memuat kop, judul, dan nomor,
       jadi tipografinya dibuat seperti surat cetak (monospace + rata kiri). */
    .detail-letter-isi {
      white-space: pre-wrap;
      font-family: ui-monospace, "Cascadia Mono", "Courier New", monospace;
      font-size: .6875rem;
      line-height: 1.8;
      margin: 0;
      /* Baris surat memakai tab untuk menyelaraskan titik dua. Lebar tab
         bawaan (8 karakter) terlalu besar untuk kolom pratinjau sehingga
         baris pendek pun ikut terlipat dan titik duanya jadi tidak sejajar.
         Dengan tab-size 4 baris isian muat dalam satu baris. */
      tab-size: 4;
      -moz-tab-size: 4;
      /* Baris yang sangat panjang (paragraf pembuka) tetap dilipat agar tidak
         perlu menggulir mendatar. */
      overflow-wrap: break-word;
    }
    .detail-letter .letter-foot { margin-top: 20px; }
    .action-row { display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end; }
    .doc-actions { display: flex; gap: 12px; }
    .doc-actions .btn { flex: 1; justify-content: center; }

    /* Dokumen: satu baris per berkas, tombol aksi dikelompokkan di kanan. */
    .doc-file-aksi { display: flex; gap: 6px; flex: none; flex-wrap: wrap; justify-content: flex-end; }
    .doc-kelompok { margin-bottom: 18px; }
    .doc-kelompok:last-of-type { margin-bottom: 0; }
    .doc-kelompok-label {
      font-size: .6875rem; font-weight: 700; letter-spacing: .09em;
      text-transform: uppercase; color: var(--gray-500);
      margin-bottom: 8px;
    }
    .doc-kosong { border: 1px dashed var(--gray-200); border-radius: var(--radius-sm); padding: 14px 16px; }

    /* Modal konfirmasi tindakan status. */
    .modal .field textarea { min-height: 92px; }
    .modal-note { margin-top: 16px; }

    @media print {
      .admin-sidebar, .admin-topbar, .admin-foot, .action-row,
      .panel:not(.panel-cetak), .modal-overlay { display: none !important; }
      .admin-main { margin: 0 !important; }
      .admin-grid-2 { display: block !important; }
    }
</style>
@endpush

@section('topbar')
@php $adminTrail = [
    ['label' => 'Admin', 'route' => 'admin.ringkasan'],
    ['label' => 'Pengajuan Surat', 'route' => 'admin.pengajuan.index'],
    ['label' => $pengajuan->nomor_referensi, 'route' => null],
]; @endphp
<x-admin-topbar :trail="$adminTrail" title="Detail Pengajuan" />
@endsection

@section('content')
<main class="admin-content">
        @if (session('status'))
          <div class="notice notice-green" role="status">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
            <span>{{ session('status') }}</span>
          </div>
        @endif

        @if ($errors->any())
          <div class="notice notice-amber" role="alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
            <div>
              @foreach ($errors->all() as $pesan)
                <div>{{ $pesan }}</div>
              @endforeach
            </div>
          </div>
        @endif

        <div class="admin-page-head greeting">
          <div>
            <a href="{{ route('admin.pengajuan.index') }}" class="back-link">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
              Kembali ke daftar pengajuan
            </a>
            <div class="detail-head-row">
              <h2>{{ $pengajuan->nomor_referensi }}</h2>
              <span class="badge {{ $pengajuan->status->badge() }}">{{ $pengajuan->status->label() }}</span>
            </div>
            <p class="sub">{{ $pengajuan->jenisSurat->nama }} · diajukan {{ $pengajuan->created_at->locale('id')->translatedFormat('j M Y, H.i') }}</p>
          </div>
          <div class="action-row">
            {{-- Petugas langsung menghubungi warga lewat WhatsApp: tombol
                 membuka percakapan berisi nomor referensi, bukan sekadar
                 menyalin nomor ke papan klip. --}}
            @if ($tautanWa)
              <a href="{{ $tautanWa }}" class="btn btn-outline btn-sm"
                 target="_blank" rel="noopener"
                 aria-label="Chat WhatsApp {{ $pengajuan->nama }} ({{ $pengajuan->whatsapp }})">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--green-600)"><path d="M21 11.5a8.4 8.4 0 0 1-12.3 7.4L3 20.5l1.7-5.5A8.4 8.4 0 1 1 21 11.5z"/></svg>
                Chat WhatsApp warga
              </a>
            @endif
            <button type="button" class="btn btn-outline btn-sm" onclick="window.print()">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
              Cetak
            </button>

            {{-- Alur cepat sesuai mockup: satu tombol per transisi yang sah.
                 Tombol yang transisinya tidak diizinkan tidak ditampilkan,
                 jadi petugas tidak pernah menekan aksi yang akan ditolak. --}}
            @if ($aksi['perbaikan'])
              <button type="button" class="btn btn-amber-outline btn-sm" data-modal-open="modalPerbaikan">Minta perbaikan</button>
            @endif
            @if ($aksi['tolak'])
              <button type="button" class="btn btn-danger-outline btn-sm" data-modal-open="modalTolak">Tolak</button>
            @endif
            @if ($aksi['setujui'])
              <button type="button" class="btn btn-primary btn-sm" data-modal-open="modalSetujui">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                Terima pengajuan
              </button>
            @endif
          </div>
        </div>

        <div class="notice notice-blue" role="status">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 16v-5M12 8h.01"/></svg>
          <span>Persetujuan admin bukan pengesahan surat. Surat baru sah setelah ditandatangani basah oleh pejabat berwenang.</span>
        </div>

        <div class="admin-grid-2 detail-grid">
          <div>
            @if ($pengajuan->catatan_admin)
              <div class="notice {{ $pengajuan->status === \App\Enums\StatusPengajuan::Ditolak ? 'notice-amber' : 'notice-blue' }}" role="note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
                <span>Catatan admin: {{ $pengajuan->catatan_admin }}</span>
              </div>
            @endif

            <div class="panel">
              <div class="panel-head">
                <div>
                  <h2>Data pemohon</h2>
                  <p>Nilai formulir tersimpan sebagai snapshot pengajuan.</p>
                </div>
                @if ($pengajuan->revisi_ke > 0)
                  <span class="badge badge-amber">Revisi ke-{{ $pengajuan->revisi_ke }}</span>
                @endif
              </div>
              <div class="data-list">
                <div class="field-block">
                  <div class="label-cap">NAMA LENGKAP</div>
                  <b>{{ $pengajuan->nama }}</b>
                </div>

                {{-- Data kependudukan bersifat opsional pada formulir warga:
                     tiap baris hanya tampil bila memang terisi. --}}
                @if (filled($pengajuan->nik))
                  <div class="field-block">
                    <div class="label-cap">NIK</div>
                    <b class="code">{{ $pengajuan->nik }}</b>
                  </div>
                @endif
                @if (filled($pengajuan->tempat_lahir))
                  <div class="field-block">
                    <div class="label-cap">TEMPAT LAHIR</div>
                    <b>{{ $pengajuan->tempat_lahir }}</b>
                  </div>
                @endif
                @if (filled($pengajuan->tanggal_lahir))
                  <div class="field-block">
                    <div class="label-cap">TANGGAL LAHIR</div>
                    <b>{{ $pengajuan->tanggal_lahir->locale('id')->translatedFormat('j F Y') }}</b>
                  </div>
                @endif
                @if (filled($pengajuan->jenis_kelamin))
                  <div class="field-block">
                    <div class="label-cap">JENIS KELAMIN</div>
                    <b>{{ $pengajuan->jenis_kelamin }}</b>
                  </div>
                @endif

                <div class="field-block">
                  <div class="label-cap">ALAMAT LENGKAP</div>
                  <b>{{ $pengajuan->alamat }}</b>
                </div>
                <div class="field-block">
                  <div class="label-cap">KEPERLUAN SURAT</div>
                  <b>{{ $pengajuan->keperluan }}</b>
                </div>
                <div class="field-block">
                  <div class="label-cap">NOMOR WHATSAPP</div>
                  <b id="waPemohon" class="code">{{ $pengajuan->whatsapp }}</b>
                  @if ($tautanWa)
                    <a href="{{ $tautanWa }}" class="small" target="_blank" rel="noopener"
                       style="display:inline-block;margin-top:6px;color:var(--green-600);font-weight:600">
                      Chat warga di WhatsApp &rarr;
                    </a>
                  @endif
                </div>
              </div>

              <div class="field-block" style="border-bottom:none">
                <div class="label-cap">PERSETUJUAN PRIVASI</div>
                <b>
                  @if ($pengajuan->persetujuan_data)
                    Versi {{ $versiPrivasi }} · disetujui saat pengiriman
                  @else
                    Belum disetujui saat pengajuan dikirim
                  @endif
                </b>
                @if ($pengajuan->persetujuan_data && $pengajuan->persetujuan_at)
                  <div class="small muted" style="margin-top:4px">
                    Disetujui {{ $pengajuan->persetujuan_at->locale('id')->translatedFormat('j M Y, H.i') }}
                  </div>
                @endif
              </div>
            </div>

            {{-- Isian surat: nilai yang diisi warga untuk penanda pada template desa. --}}
            <div class="panel">
              <div class="panel-head">
                <div>
                  <h2>Isian surat</h2>
                  <p>Diisi warga mengikuti template surat {{ $pengajuan->jenisSurat->nama }}.</p>
                </div>
                <span class="badge badge-gray">{{ count((array) ($pengajuan->isian_surat ?? [])) }} isian</span>
              </div>
              @forelse ((array) ($pengajuan->isian_surat ?? []) as $kunci => $nilai)
                <div class="field-block">
                  <div class="label-cap">{{ Str::upper(str_replace('_', ' ', $kunci)) }}</div>
                  <b>{{ $nilai }}</b>
                </div>
              @empty
                <p class="muted">Template surat ini tidak memerlukan isian tambahan dari warga.</p>
              @endforelse
            </div>

            <div class="panel">
              <div class="panel-head">
                <div>
                  <h2>Dokumen persyaratan</h2>
                  <p>File privat. Hanya dapat dibuka admin yang berwenang.</p>
                </div>
                <span class="badge badge-gray">{{ $pengajuan->dokumen->count() }} berkas</span>
              </div>

              @forelse ($pengajuan->dokumen as $dokumen)
                <div class="doc-file">
                  <div class="icon-tile" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                  </div>
                  <div class="dz-meta">
                    <div class="dz-name">{{ $dokumen->label }}</div>
                    <div class="dz-size">{{ $dokumen->nama_asli }} · {{ $dokumen->ukuranTerbaca() }}</div>
                  </div>
                  <span class="badge {{ $dokumen->badgeStatus() }}">{{ $dokumen->status_verifikasi }}</span>

                  <div class="doc-file-aksi">
                    <a href="{{ route('admin.pengajuan.dokumen', [$pengajuan, $dokumen]) }}" class="btn btn-outline btn-sm" target="_blank" rel="noopener">Lihat</a>
                    @if (auth()->user()->role->bolehVerifikasiPengajuan() && $dokumen->status_verifikasi !== \App\Models\DokumenPengajuan::TERVERIFIKASI)
                      <form method="POST" action="{{ route('admin.pengajuan.dokumen.status', [$pengajuan, $dokumen]) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status_verifikasi" value="{{ \App\Models\DokumenPengajuan::TERVERIFIKASI }}">
                        <button type="submit" class="btn btn-outline btn-sm">Setujui</button>
                      </form>
                    @endif
                    @if (auth()->user()->role->bolehVerifikasiPengajuan() && $dokumen->status_verifikasi !== \App\Models\DokumenPengajuan::DITOLAK)
                      <form method="POST" action="{{ route('admin.pengajuan.dokumen.status', [$pengajuan, $dokumen]) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status_verifikasi" value="{{ \App\Models\DokumenPengajuan::DITOLAK }}">
                        <button type="submit" class="btn btn-outline btn-sm">Tolak</button>
                      </form>
                    @endif
                  </div>
                </div>
              @empty
                <div class="doc-kosong">
                  <b style="font-size:.9375rem;color:var(--navy-900)">Belum ada dokumen yang diunggah</b>
                  <p class="small muted" style="margin-top:4px">
                    Template {{ $pengajuan->jenisSurat->nama }} tidak mewajibkan unggahan berkas,
                    atau warga mengirim pengajuan tanpa melampirkan dokumen.
                  </p>
                </div>
              @endforelse

              <p class="note-box mt-16">Dokumen asli tetap perlu dibawa saat pengambilan surat.</p>
            </div>

            <div class="panel">
              <div class="panel-head">
                <h2>Riwayat status</h2>
                <span class="badge badge-gray">Audit trail</span>
              </div>
              <div class="timeline">
                @foreach ($pengajuan->riwayat as $riwayat)
                  <div class="timeline-item">
                    <span class="timeline-dot {{ $riwayat->status->warnaTitik() }}" aria-hidden="true"></span>
                    <div class="between">
                      <b>{{ $riwayat->status->label() }}</b>
                      <span class="badge {{ $riwayat->badgeAktor() }}">{{ ucfirst($riwayat->aktor) }}</span>
                    </div>
                    <div class="timeline-meta">{{ $riwayat->created_at->locale('id')->translatedFormat('j M Y, H.i') }}{{ $riwayat->user ? ' · '.$riwayat->user->name : '' }}</div>
                    @if ($riwayat->catatan)
                      <p class="small muted mt-8">{{ $riwayat->catatan }}</p>
                    @endif
                  </div>
                @endforeach
              </div>
            </div>
          </div>

          <div>
            <div class="panel panel-cetak">
              <div class="panel-head">
                <div>
                  <h2>Preview surat</h2>
                  <p>Dokumen dari data pengajuan</p>
                </div>
                <div class="flex gap-8">
                  @if ($pengajuan->revisi_ke > 0)
                    <span class="badge badge-gray">v{{ $pengajuan->revisi_ke + 1 }}</span>
                  @endif
                  <span class="badge badge-amber">Draft</span>
                </div>
              </div>
              <div class="letter detail-letter">
                {{-- Kop resmi desa dipakai di semua surat: gambarnya sama dengan
                     yang disisipkan ke berkas Word, jadi yang tampil di layar
                     persis sama dengan hasil cetak. --}}
                <img class="letter-kop" src="/images/kop-surat.png"
                     alt="Kop surat Pemerintah Kabupaten Lampung Selatan, Kecamatan Jati Agung, Desa Karang Sari">
                <p class="detail-letter-isi">{{ $pengajuan->suratResmiTeks() }}</p>
                <div class="letter-foot">Pratinjau di layar. Berkas Word yang diunduh memakai dokumen resmi desa sehingga tata letaknya sama persis.</div>
              </div>
              <div class="panel-foot">
                <a href="{{ route('admin.pengajuan.surat-word', $pengajuan) }}" class="btn btn-primary btn-sm">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12M7 11l5 5 5-5M4 21h16"/></svg>
                  Unduh Word (.docx)
                </a>
                <span class="field-help">Format surat resmi desa: kop bergambar, Bookman Old Style, dan tabel tanda tangan 3 kolom.</span>
              </div>
            </div>

            <div class="panel">
              <h2 class="summary-title">Nomor surat</h2>
              @if (auth()->user()->role->bolehVerifikasiPengajuan())
                <form method="POST" action="{{ route('admin.pengajuan.nomor-surat', $pengajuan) }}">
                  @csrf
                  @method('PUT')
                  <input type="text" id="nomor_surat" name="nomor_surat"
                         value="{{ old('nomor_surat', $pengajuan->nomor_surat ?? $nomorSuratUsulan) }}"
                         maxlength="80" aria-label="Nomor surat"
                         placeholder="{{ $nomorSuratUsulan }}">
                  <p class="field-help">Mengikuti tata penomoran resmi desa. Nomor ini tercetak pada surat.</p>
                  <div class="doc-actions mt-16">
                    <button type="submit" class="btn btn-primary btn-sm">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                      Simpan nomor
                    </button>
                  </div>
                </form>
                <p class="field-help mt-16">
                  Tombol <b>Unduh PDF</b> membuka dialog cetak peramban. Pilih
                  &ldquo;Simpan sebagai PDF&rdquo; untuk menyimpan berkas.
                </p>
              @else
                <div class="kv-row"><span class="k">Nomor tersimpan</span><span class="v code">{{ $pengajuan->nomor_surat ?: 'Belum diisi' }}</span></div>
                <p class="field-help mt-16">Hanya verifikator dan administrator yang dapat mengubah nomor surat.</p>
              @endif
            </div>

            <div class="panel">
              <h2 class="summary-title">Ubah status</h2>
              @if (empty($transisi))
                <p class="muted">Pengajuan berstatus <b>{{ $pengajuan->status->label() }}</b> dan tidak dapat diubah lagi.</p>
              @else
                {{-- Petugas sering bertanya "di mana tombol terimanya?".
                     Status "Disetujui" adalah tindakan MENERIMA pengajuan,
                     jadi pilihannya diberi keterangan eksplisit. --}}
                <p class="field-help" style="margin-top:0;margin-bottom:12px">
                  Pilih <b>Disetujui</b> untuk <b>menerima</b> pengajuan ini, atau
                  <b>Perlu Perbaikan</b> / <b>Ditolak</b> bila masih ada yang kurang.
                </p>
                <form method="POST" action="{{ route('admin.pengajuan.status', $pengajuan) }}">
                  @csrf
                  @method('PATCH')
                  <div class="field mb-16">
                    <label for="status">Status baru</label>
                    <select id="status" name="status" required>
                      @foreach ($transisi as $tujuan)
                        <option value="{{ $tujuan->value }}" @selected(old('status') === $tujuan->value)>
                          {{ $tujuan === \App\Enums\StatusPengajuan::Disetujui
                              ? 'Disetujui — terima pengajuan'
                              : ($tujuan === \App\Enums\StatusPengajuan::PerluPerbaikan
                                  ? 'Perlu Perbaikan — minta warga membetulkan'
                                  : ($tujuan === \App\Enums\StatusPengajuan::Ditolak
                                      ? 'Ditolak — tolak pengajuan'
                                      : $tujuan->label())) }}
                        </option>
                      @endforeach
                    </select>
                  </div>
                  <div class="field mb-16">
                    <label for="catatan">Catatan untuk pemohon</label>
                    <textarea id="catatan" name="catatan" rows="3" placeholder="Wajib diisi saat meminta perbaikan atau menolak pengajuan.">{{ old('catatan', $pengajuan->catatan_admin) }}</textarea>
                    <p class="field-help">Catatan ini tampil pada riwayat pengajuan.</p>
                  </div>
                  <div class="flex gap-8 wrap" style="justify-content:flex-end">
                    <button type="submit" class="btn btn-primary btn-sm">Simpan status</button>
                  </div>
                </form>
              @endif
            </div>

            <div class="panel">
              <h2 class="summary-title">Informasi pengajuan</h2>
              <div class="kv-row"><span class="k">Referensi</span><span class="v code">{{ $pengajuan->nomor_referensi }}</span></div>
              <div class="kv-row"><span class="k">Kode akses warga</span><span class="v code" id="kodeAksesAdmin">{{ $pengajuan->kode_akses ?: '—' }}</span></div>
              <div class="kv-row"><span class="k">Jenis surat</span><span class="v">{{ $pengajuan->jenisSurat->nama }}</span></div>
              <div class="kv-row"><span class="k">Revisi</span><span class="v">{{ $pengajuan->revisi_ke > 0 ? 'ke-'.$pengajuan->revisi_ke : 'Belum ada' }}</span></div>
              <div class="kv-row"><span class="k">Status dokumen</span><span class="v">{{ $pengajuan->dokumen->count() }} berkas</span></div>
              <div class="kv-row"><span class="k">Kode layanan</span><span class="v">{{ $pengajuan->jenisSurat->kode_layanan ?: '—' }}</span></div>
              <div class="kv-row"><span class="k">Estimasi</span><span class="v">{{ $pengajuan->jenisSurat->estimasi_hari ?: '—' }}</span></div>
              <div class="kv-row"><span class="k">Diproses oleh</span><span class="v">{{ $pengajuan->diprosesOleh?->name ?? 'Belum ditangani' }}</span></div>
              <div class="kv-row"><span class="k">Hak akses</span><span class="v">Privat admin</span></div>
              <p class="note-box mt-16">Persetujuan admin bukan pengesahan surat. Surat baru sah setelah ditandatangani basah oleh pejabat berwenang.</p>
            </div>
          </div>
        </div>
      </main>

  {{-- Modal konfirmasi: satu per tindakan agar alasan/catatan terpisah
       dan tidak pernah terkirim dengan status yang salah. --}}
  @if ($aksi['setujui'])
    <div class="modal-overlay" id="modalSetujui">
      <div class="modal" role="dialog" aria-modal="true" aria-labelledby="setujuiTitle">
        <div class="modal-head">
          <h2 id="setujuiTitle">Terima dan setujui pengajuan ini?</h2>
          <button type="button" class="modal-close" data-modal-close aria-label="Tutup">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>
        <p class="muted">
          Pengajuan <b>{{ $pengajuan->nomor_referensi }}</b> milik
          <b>{{ $pengajuan->nama }}</b> akan <b>diterima</b> dan ditandai
          <b>Disetujui</b>, lalu masuk tahap pemrosesan surat.
        </p>
        <form method="POST" action="{{ route('admin.pengajuan.status', $pengajuan) }}">
          @csrf
          @method('PATCH')
          <input type="hidden" name="status" value="{{ \App\Enums\StatusPengajuan::Disetujui->value }}">
          <div class="field mt-16">
            <label for="catatanSetujui">Catatan untuk pemohon (opsional)</label>
            <textarea id="catatanSetujui" name="catatan" maxlength="1000"
                      placeholder="Contoh: Berkas lengkap, surat diproses 1 hari kerja."></textarea>
            <p class="field-help">Tampil pada riwayat pengajuan yang dapat dilihat warga.</p>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
            <button type="submit" class="btn btn-primary">Ya, terima pengajuan</button>
          </div>
        </form>
      </div>
    </div>
  @endif

  @if ($aksi['perbaikan'])
    <div class="modal-overlay" id="modalPerbaikan">
      <div class="modal" role="dialog" aria-modal="true" aria-labelledby="perbaikanTitle">
        <div class="modal-head">
          <h2 id="perbaikanTitle">Minta perbaikan dokumen?</h2>
          <button type="button" class="modal-close" data-modal-close aria-label="Tutup">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>
        <p class="muted">
          Status pengajuan <b>{{ $pengajuan->nomor_referensi }}</b> berubah menjadi
          <b>Perlu Perbaikan</b>. Warga dapat memperbaiki data dan mengunggah ulang
          dokumen memakai nomor referensi yang sama.
        </p>
        <form method="POST" action="{{ route('admin.pengajuan.status', $pengajuan) }}">
          @csrf
          @method('PATCH')
          <input type="hidden" name="status" value="{{ \App\Enums\StatusPengajuan::PerluPerbaikan->value }}">
          <div class="field mt-16">
            <label for="catatanPerbaikan">Catatan untuk pemohon <span class="req">*</span></label>
            <textarea id="catatanPerbaikan" name="catatan" maxlength="1000" required
                      placeholder="Contoh: Foto tempat usaha buram, mohon unggah ulang."></textarea>
            <p class="field-help">Wajib diisi. Catatan ini tampil pada riwayat pengajuan.</p>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
            <button type="submit" class="btn btn-amber-outline">Kirim permintaan perbaikan</button>
          </div>
        </form>
      </div>
    </div>
  @endif

  @if ($aksi['tolak'])
    <div class="modal-overlay" id="modalTolak">
      <div class="modal" role="dialog" aria-modal="true" aria-labelledby="tolakTitle">
        <div class="modal-head">
          <h2 id="tolakTitle">Tolak pengajuan ini?</h2>
          <button type="button" class="modal-close" data-modal-close aria-label="Tutup">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>
        <p class="muted">
          Pengajuan <b>{{ $pengajuan->nomor_referensi }}</b> akan ditandai
          <b>Ditolak</b>. Warga dapat mengajukan ulang dengan data yang benar.
        </p>
        <form method="POST" action="{{ route('admin.pengajuan.status', $pengajuan) }}">
          @csrf
          @method('PATCH')
          <input type="hidden" name="status" value="{{ \App\Enums\StatusPengajuan::Ditolak->value }}">
          <div class="field mt-16">
            <label for="catatanTolak">Alasan penolakan <span class="req">*</span></label>
            <textarea id="catatanTolak" name="catatan" maxlength="1000" required
                      placeholder="Contoh: NIK tidak cocok dengan Kartu Keluarga."></textarea>
            <p class="field-help">Wajib diisi. Alasan ini tampil pada riwayat pengajuan.</p>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
            <button type="submit" class="btn btn-danger-outline">Ya, tolak pengajuan</button>
          </div>
        </form>
      </div>
    </div>
  @endif
@endsection
