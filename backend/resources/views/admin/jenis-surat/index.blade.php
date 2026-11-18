@extends('layouts.admin')

@section('title', 'Jenis Surat · Dashboard Admin '.$profil->nama_desa)

@push('styles')
<style>
  .jenis-table { min-width: 900px; }
  .jenis-table td { vertical-align: middle; }

  /* Kolom "Jenis surat": kode layanan sebagai badge di kiri judul. */
  .jenis-id { display: flex; gap: 12px; align-items: flex-start; }
  .jenis-kode {
    flex: 0 0 auto; min-width: 42px; text-align: center;
    background: var(--navy-900); color: var(--white);
    border-radius: var(--radius-sm); padding: 5px 8px;
    font-size: .75rem; font-weight: 700; letter-spacing: .02em;
  }
  .jenis-judul { font-weight: 700; color: var(--navy-900); }
  .jenis-desk {
    font-size: .8125rem; color: var(--gray-500); margin-top: 3px;
    display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical;
    overflow: hidden; max-width: 420px;
  }
  .jenis-angka { font-variant-numeric: tabular-nums; white-space: nowrap; }
  /* Estimasi cukup satu baris agar tinggi baris tabel seragam. */
  .jenis-table td:nth-child(4) { white-space: nowrap; }

  /* Baris filter di atas tabel. */
  .filter-bar {
    display: flex; gap: 16px; align-items: center; justify-content: space-between;
    flex-wrap: wrap; padding: 16px 20px; border-bottom: 1px solid var(--gray-200);
  }
  .filter-cari { position: relative; flex: 1 1 280px; max-width: 420px; }
  .filter-cari svg {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
    width: 16px; height: 16px; color: var(--gray-500); pointer-events: none;
  }
  .filter-cari input { padding-left: 36px; }
  .filter-toggle { display: flex; gap: 8px; align-items: center; font-size: .875rem; color: var(--gray-600); cursor: pointer; white-space: nowrap; }
  .filter-toggle input { width: 16px; height: 16px; margin: 0; cursor: pointer; }

  /* Saklar aktif/nonaktif (bukan tombol teks) agar konsisten dengan referensi. */
  .switch { display: inline-flex; align-items: center; gap: 10px; }
  .switch button {
    position: relative; width: 40px; height: 23px; flex: 0 0 auto;
    border: 0; border-radius: var(--radius-full); cursor: pointer;
    background: var(--gray-200); transition: background .15s; padding: 0;
  }
  .switch button::after {
    content: ""; position: absolute; top: 3px; left: 3px;
    width: 17px; height: 17px; border-radius: 50%; background: var(--white);
    box-shadow: 0 1px 3px rgba(11,31,58,.25); transition: transform .15s;
  }
  .switch button[aria-checked="true"] { background: var(--green-600); }
  .switch button[aria-checked="true"]::after { transform: translateX(17px); }
  .switch button:focus-visible { outline: 2px solid var(--blue-500); outline-offset: 2px; }
  .switch-label { font-size: .8125rem; color: var(--gray-600); }

  /* Kartu ringkasan di bawah tabel. */
  .ringkas-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 22px; }
  @media (max-width: 860px) { .ringkas-grid { grid-template-columns: 1fr; } }
  .ringkas-kartu { background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius-md); padding: 18px 20px; }
  .ringkas-label { font-size: .6875rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; color: var(--gray-500); }
  .ringkas-nilai { font-size: 1.75rem; font-weight: 800; color: var(--navy-900); line-height: 1.2; margin-top: 8px; }
  .ringkas-catatan { font-size: .75rem; color: var(--gray-500); margin-top: 4px; }
</style>
@endpush

@section('topbar')
@php $adminTrail = [['label' => 'Admin', 'route' => 'admin.ringkasan'], ['label' => 'Jenis Surat', 'route' => null]]; @endphp
<x-admin-topbar :trail="$adminTrail" title="Jenis Surat" />
@endsection

@section('content')
<main class="admin-content">
        <div class="admin-page-head">
          <div>
            <p class="sub">Atur layanan yang tampil di website publik beserta formulir dan persyaratannya.</p>
            <p class="sub" style="margin-top:4px">
              <strong>{{ $ringkasan['aktif'] }}</strong> aktif dari <strong>{{ $ringkasan['total'] }}</strong> jenis surat
            </p>
          </div>
          <a href="{{ route('admin.jenis-surat.create') }}" class="btn btn-primary btn-sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="margin-right:6px"><path d="M12 5v14M5 12h14"/></svg>
            Tambah jenis surat
          </a>
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

        <div class="panel" style="padding:0; overflow:hidden">
          <form class="filter-bar" method="GET" action="{{ route('admin.jenis-surat.index') }}" role="search">
            <div class="filter-cari">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
              <input type="search" name="q" value="{{ $cari }}" placeholder="Cari nama atau kode surat..." aria-label="Cari jenis surat">
            </div>
            <label class="filter-toggle">
              <input type="checkbox" name="aktif" value="1" @checked($hanyaAktif) onchange="this.form.submit()">
              Hanya tampilkan yang aktif
            </label>
            @if ($cari !== '' || $hanyaAktif)
              <a href="{{ route('admin.jenis-surat.index') }}" class="btn btn-outline btn-sm">Atur ulang</a>
            @endif
          </form>

          <div class="table-wrap">
            <table class="data jenis-table">
              <thead>
                <tr>
                  <th>Jenis surat</th>
                  <th>Formulir</th>
                  <th>Persyaratan</th>
                  <th>Estimasi</th>
                  <th>Status</th>
                  <th class="right">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($jenisSurats as $jenis)
                  <tr>
                    <td>
                      <div class="jenis-id">
                        <span class="jenis-kode">{{ $jenis->kode_layanan ?: $jenis->kode }}</span>
                        <div>
                          <div class="jenis-judul">{{ $jenis->nama }}</div>
                          @if (filled($jenis->deskripsi))
                            <div class="jenis-desk" title="{{ $jenis->deskripsi }}">{{ $jenis->deskripsi }}</div>
                          @endif
                        </div>
                      </div>
                    </td>
                    <td class="jenis-angka">{{ $jenis->jumlah_field }} kolom</td>
                    <td class="jenis-angka">{{ $jenis->persyaratan_count }} dokumen</td>
                    <td>{{ $jenis->estimasi_hari ?: '—' }}</td>
                    <td>
                      <span class="switch">
                        <form method="POST" action="{{ route('admin.jenis-surat.toggle', $jenis) }}" style="margin:0">
                          @csrf
                          @method('PATCH')
                          <button type="submit" role="switch"
                                  aria-checked="{{ $jenis->is_aktif ? 'true' : 'false' }}"
                                  aria-label="{{ $jenis->is_aktif ? 'Nonaktifkan' : 'Aktifkan' }} {{ $jenis->nama }}"
                                  title="{{ $jenis->is_aktif ? 'Nonaktifkan' : 'Aktifkan' }}"></button>
                        </form>
                        <span class="switch-label">{{ $jenis->is_aktif ? 'Aktif' : 'Nonaktif' }}</span>
                      </span>
                    </td>
                    <td class="right action-cell">
                      <a href="{{ route('layanan.detail', $jenis) }}" target="_blank" rel="noopener"
                         class="btn btn-outline btn-sm" title="Lihat di website publik" aria-label="Lihat {{ $jenis->nama }} di website publik">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14L21 3"/></svg>
                      </a>
                      <a href="{{ route('admin.jenis-surat.edit', $jenis) }}" class="btn btn-outline btn-sm">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15" style="margin-right:5px"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                        Konfigurasi
                      </a>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6">
                      <div style="text-align:center; padding:36px 16px">
                        <p style="font-weight:700; color:var(--navy-900); margin-bottom:6px">Tidak ada jenis surat yang cocok</p>
                        <p class="muted" style="margin-bottom:14px">
                          @if ($cari !== '' || $hanyaAktif)
                            Ubah kata kunci atau matikan filter "hanya aktif".
                          @else
                            Belum ada layanan surat. Tambahkan jenis surat pertama.
                          @endif
                        </p>
                        @if ($cari !== '' || $hanyaAktif)
                          <a href="{{ route('admin.jenis-surat.index') }}" class="btn btn-outline btn-sm">Atur ulang filter</a>
                        @else
                          <a href="{{ route('admin.jenis-surat.create') }}" class="btn btn-primary btn-sm">Tambah jenis surat</a>
                        @endif
                      </div>
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="list-note" style="padding:14px 20px; margin:0; border-top:1px solid var(--gray-200)">
            Menonaktifkan jenis surat hanya menyembunyikannya dari pengajuan baru. Riwayat pengajuan lama tetap dapat dibaca melalui snapshot.
          </div>
        </div>

        <div class="ringkas-grid">
          <div class="ringkas-kartu">
            <div class="ringkas-label">Total layanan</div>
            <div class="ringkas-nilai">{{ $ringkasan['total'] }}</div>
            <div class="ringkas-catatan">{{ $ringkasan['aktif'] }} aktif</div>
          </div>
          <div class="ringkas-kartu">
            <div class="ringkas-label">Field formulir</div>
            <div class="ringkas-nilai">{{ $ringkasan['field'] }}</div>
            <div class="ringkas-catatan">Kolom isian dari seluruh layanan</div>
          </div>
          <div class="ringkas-kartu">
            <div class="ringkas-label">Persyaratan dokumen</div>
            <div class="ringkas-nilai">{{ $ringkasan['persyaratan'] }}</div>
            <div class="ringkas-catatan">Dokumen yang diunggah warga</div>
          </div>
        </div>
      </main>
@endsection
