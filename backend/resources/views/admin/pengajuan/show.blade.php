@extends('layouts.admin')

@section('title', 'Detail Pengajuan '.$pengajuan->nomor_referensi.' · Dashboard Admin '.$profil->nama_desa)

@push('styles')
<style>
.detail-head-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-top: 8px; }
    .field-block { padding: 12px 0; border-bottom: 1px solid var(--gray-200); min-width: 0; }
    .field-block .label-cap { margin: 0 0 4px; }
    .field-block b { display: block; font-size: .95rem; font-weight: 700; color: var(--gray-900); overflow-wrap: anywhere; }
    .data-list > * { min-width: 0; }
    .doc-file .dz-meta { min-width: 0; }
    .detail-letter { font-size: .75rem; padding: 28px 26px; }
    .detail-letter .letter-inst span { display: block; }
    .detail-letter .letter-fields .doc-row { grid-template-columns: 104px 10px 1fr; }
    .action-row { display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end; }
    .status-form { border-top: 1px solid var(--gray-200); margin-top: 18px; padding-top: 18px; }
    @media print {
      .admin-sidebar, .admin-topbar, .admin-footer, .panel:not(.panel-cetak), .status-form { display: none !important; }
      .admin-main { margin: 0 !important; }
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
        <div class="notice notice-blue" role="status">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 16v-5M12 8h.01"/></svg>
          <span>{{ session('status', 'Persetujuan admin bukan pengesahan surat. Surat baru sah setelah ditandatangani basah oleh pejabat berwenang.') }}</span>
        </div>

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
            <button type="button" class="btn btn-outline btn-sm" data-copy="#waPemohon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--green-600)"><path d="M21 11.5a8.4 8.4 0 0 1-12.3 7.4L3 20.5l1.7-5.5A8.4 8.4 0 1 1 21 11.5z"/></svg>
              Salin nomor WhatsApp
            </button>
            <button type="button" class="btn btn-outline btn-sm" onclick="window.print()">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
              Cetak
            </button>
          </div>
        </div>

        <div class="admin-grid-2">
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
                <span class="badge badge-gray">{{ $pengajuan->jenis_kelamin }}</span>
              </div>
              <div class="data-list">
                <div class="field-block">
                  <div class="label-cap">NAMA LENGKAP</div>
                  <b>{{ $pengajuan->nama }}</b>
                </div>
                <div class="field-block">
                  <div class="label-cap">NIK</div>
                  <b class="code">{{ $pengajuan->nik }}</b>
                </div>
                <div class="field-block">
                  <div class="label-cap">TEMPAT LAHIR</div>
                  <b>{{ $pengajuan->tempat_lahir }}</b>
                </div>
                <div class="field-block">
                  <div class="label-cap">TANGGAL LAHIR</div>
                  <b>{{ $pengajuan->tanggal_lahir->format('Y-m-d') }}</b>
                </div>
                <div class="field-block">
                  <div class="label-cap">JENIS KELAMIN</div>
                  <b>{{ $pengajuan->jenis_kelamin }}</b>
                </div>
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
                </div>
              </div>
              <div class="field-block" style="border-bottom:none">
                <div class="label-cap">PERSETUJUAN PRIVASI</div>
                <b>{{ $pengajuan->persetujuan_data ? 'Disetujui' : 'Belum disetujui' }}
                  {{ $pengajuan->persetujuan_at ? '· '.$pengajuan->persetujuan_at->locale('id')->translatedFormat('j M Y, H.i') : '' }}</b>
              </div>
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
                  <a href="{{ route('admin.pengajuan.dokumen', [$pengajuan, $dokumen]) }}" class="btn btn-outline btn-sm" target="_blank" rel="noopener">Unduh</a>
                </div>
              @empty
                <p class="muted">Belum ada dokumen yang diunggah.</p>
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
                    <span class="timeline-dot {{ $riwayat->status->isSelesai() ? '' : 'green' }}" aria-hidden="true"></span>
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
            <div class="panel">
              <div class="panel-head">
                <div>
                  <h2>Ubah status pengajuan</h2>
                  <p>Hanya transisi yang sah menurut alur yang ditampilkan.</p>
                </div>
              </div>

              @if (empty($transisi))
                <p class="muted">Pengajuan berstatus <b>{{ $pengajuan->status->label() }}</b> dan tidak dapat diubah lagi.</p>
              @else
                <form method="POST" action="{{ route('admin.pengajuan.status', $pengajuan) }}" class="status-form" style="border-top:none;margin-top:0;padding-top:0">
                  @csrf
                  @method('PATCH')
                  <div class="field mb-16">
                    <label for="status">Status baru</label>
                    <select id="status" name="status" required>
                      @foreach ($transisi as $tujuan)
                        <option value="{{ $tujuan->value }}" @selected(old('status') === $tujuan->value)>{{ $tujuan->label() }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="field mb-16">
                    <label for="catatan">Catatan untuk pemohon</label>
                    <textarea id="catatan" name="catatan" rows="3" placeholder="Wajib diisi saat meminta perbaikan atau menolak pengajuan.">{{ old('catatan', $pengajuan->catatan_admin) }}</textarea>
                    <p class="field-help">Catatan ini tampil pada riwayat pengajuan.</p>
                  </div>
                  <div class="flex gap-8 wrap" style="justify-content:flex-end">
                    <button type="submit" class="btn btn-primary">Simpan status</button>
                  </div>
                </form>
              @endif
            </div>

            <div class="panel panel-cetak">
              <div class="panel-head">
                <div>
                  <h2>Preview surat</h2>
                  <p>Dokumen dari data pengajuan</p>
                </div>
                <span class="badge badge-amber">Draft</span>
              </div>
              <div class="letter detail-letter">
                <div class="letter-head">
                  <div class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M4 18h16M6 18v-8h3v8M11 18v-8h3v8M16 18v-8h3v8M12 3l9 5H3l9-5z"/></svg>
                  </div>
                  <div class="letter-inst">
                    <b>{{ $profil->wilayah }}</b>
                    <span>{{ $profil->alamat_kantor }}</span>
                  </div>
                  <div style="width:52px;flex:none" aria-hidden="true"></div>
                </div>
                <div class="letter-title">{{ Str::upper($pengajuan->jenisSurat->nama) }}</div>
                <div class="letter-no">Nomor: {{ $pengajuan->jenisSurat->kode_layanan ?: '470' }}/____/DS-SKM/{{ $pengajuan->created_at->year }}</div>
                <p>Yang bertanda tangan di bawah ini, Kepala {{ $profil->nama_desa }}, menerangkan bahwa:</p>
                <div class="letter-fields">
                  <div class="doc-row"><span class="l">Nama lengkap</span><span>:</span><span class="v">{{ $pengajuan->nama }}</span></div>
                  <div class="doc-row"><span class="l">NIK</span><span>:</span><span class="v">{{ $pengajuan->nik }}</span></div>
                  <div class="doc-row"><span class="l">Tempat lahir</span><span>:</span><span class="v">{{ $pengajuan->tempat_lahir }}</span></div>
                  <div class="doc-row"><span class="l">Tanggal lahir</span><span>:</span><span class="v">{{ $pengajuan->tanggal_lahir->format('j F Y') }}</span></div>
                  <div class="doc-row"><span class="l">Jenis kelamin</span><span>:</span><span class="v">{{ $pengajuan->jenis_kelamin }}</span></div>
                  <div class="doc-row"><span class="l">Alamat</span><span>:</span><span class="v">{{ $pengajuan->alamat }}</span></div>
                </div>
                <p>Surat ini dibuat untuk keperluan <b>{{ $pengajuan->keperluan }}</b>.</p>
                <p>Demikian surat keterangan ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.</p>
                <div class="between mt-16" style="align-items:flex-end">
                  <div>
                    Ditetapkan di {{ $profil->kabupaten }}
                    <div class="muted">Tanggal: {{ $pengajuan->created_at->locale('id')->translatedFormat('j F Y') }}</div>
                  </div>
                  <div class="letter-sign">
                    Kepala {{ $profil->nama_desa }}
                    <div class="letter-stamp" aria-hidden="true">{{ Str::upper($profil->nama_desa) }}</div>
                    <div class="sign-name">&nbsp;</div>
                  </div>
                </div>
                <div class="letter-foot">Dokumen ini dicetak dari sistem informasi desa. Surat resmi tetap ditandatangani basah oleh pejabat berwenang.</div>
              </div>
            </div>

            <div class="panel">
              <h2 class="summary-title">Informasi pengajuan</h2>
              <div class="kv-row"><span class="k">Referensi</span><span class="v code">{{ $pengajuan->nomor_referensi }}</span></div>
              <div class="kv-row"><span class="k">Jenis surat</span><span class="v">{{ $pengajuan->jenisSurat->nama }}</span></div>
              <div class="kv-row"><span class="k">Kode layanan</span><span class="v">{{ $pengajuan->jenisSurat->kode_layanan ?: '—' }}</span></div>
              <div class="kv-row"><span class="k">Estimasi</span><span class="v">{{ $pengajuan->jenisSurat->estimasi_hari ?: '—' }}</span></div>
              <div class="kv-row"><span class="k">Diproses oleh</span><span class="v">{{ $pengajuan->diprosesOleh?->name ?? 'Belum ditangani' }}</span></div>
              <div class="kv-row"><span class="k">Hak akses</span><span class="v">Privat admin</span></div>
            </div>
          </div>
        </div>
      </main>
@endsection
