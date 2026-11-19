@extends('layouts.admin')

@section('title', 'Log Aktivitas · Dashboard Admin '.$profil->nama_desa)

@push('styles')
<style>
.log-search { max-width: 430px; margin-bottom: 20px; }
    .log-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 36px; }
    @media (max-width: 860px) { .log-cols { grid-template-columns: 1fr; } }

    /* Tabel log punya enam kolom. Hanya kolom yang isinya benar-benar pendek
       (waktu dan tingkat) yang dikunci satu baris; kolom lain dibiarkan
       menyesuaikan lebar panel. Dengan begitu tabel tidak melebar melewati
       tepi panel, tetapi "17 Nov 2026, 13.05" tetap utuh dalam satu baris. */
    .log-table { table-layout: auto; min-width: 0; }
    .log-table th, .log-table td { padding: 12px 10px; }
    .log-table th:first-child, .log-table td:first-child { padding-left: 0; }
    .log-table th:last-child, .log-table td:last-child { padding-right: 0; }
    .log-table td:nth-child(1),
    .log-table td:nth-child(5) { white-space: nowrap; }
    .log-table th:nth-child(5), .log-table td:nth-child(5) { width: 1%; }
    /* Kolom pelaku memuat avatar + nama; nama tidak boleh terlipat menjadi
       dua baris hanya karena kolomnya sempit. */
    .log-table td:nth-child(2) { white-space: nowrap; }
    /* Avatar pelaku jangan menyusut saat kolomnya sempit. */
    .log-table .cell-avatar { gap: 10px; }
    .log-table .cell-avatar .avatar { flex: none; width: 34px; height: 34px; }
    .log-table .cell-avatar .cell-main { min-width: 0; }

    /* Ringkasan aktivitas per hari: kartu kecil yang bisa diklik.
       Dibuat bisa digulir mendatar di layar sempit, bukan mengecil sampai
       tanggalnya tidak terbaca. */
    .hari-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      gap: 10px;
    }
    .hari-kartu {
      display: grid; gap: 2px; padding: 12px 14px;
      border: 1px solid #D8E2EC; border-radius: var(--radius-sm);
      background: var(--white); text-decoration: none; color: inherit;
      transition: border-color .15s, box-shadow .15s;
    }
    .hari-kartu:hover {
      border-color: var(--blue-500); text-decoration: none;
      box-shadow: 0 6px 18px -12px rgba(46,109,164,.5);
    }
    .hari-kartu.aktif { border-color: var(--navy-900); border-width: 1.5px; background: var(--blue-100); }
    .hari-tanggal { font-size: .875rem; font-weight: 700; color: var(--navy-900); }
    .hari-hari { font-size: .75rem; color: var(--gray-500); }
    .hari-jumlah { font-size: .75rem; font-weight: 600; color: var(--blue-600); margin-top: 4px; }

    /* Petunjuk gulir hanya muncul di layar sempit, saat tabel memang
       terpotong dan perlu digeser. */
    .geser-hint { display: none; }
    @media (max-width: 860px) {
      .geser-hint {
        display: block; margin: 0 0 8px;
        font-size: .75rem; color: var(--gray-500);
      }
    }
</style>
@endpush

@section('topbar')
@php $adminTrail = [['label' => 'Admin', 'route' => 'admin.ringkasan'], ['label' => 'Log Aktivitas', 'route' => null]]; @endphp
<x-admin-topbar :trail="$adminTrail" title="Log Aktivitas" />
@endsection

@section('content')
<main class="admin-content">
        <div class="admin-page-head">
          <div>
            <p class="sub">Catatan tindakan penting admin untuk keperluan audit.</p>
            <div class="count">{{ $logs->total() }} aktivitas tercatat</div>
          </div>
        </div>

        {{-- Ringkasan per hari: klik satu tanggal untuk menyaring tabel. --}}
        @if ($perHari->isNotEmpty())
          <div class="panel">
            <div class="panel-head">
              <div>
                <h2>Aktivitas per hari</h2>
                <p>Pilih tanggal untuk menampilkan log hari itu saja.</p>
              </div>
              @if ($tanggal)
                <a href="{{ route('admin.log', array_filter(['q' => $q])) }}" class="btn btn-outline btn-sm">
                  Tampilkan semua hari
                </a>
              @endif
            </div>
            <div class="hari-grid">
              @foreach ($perHari as $hari)
                @php $nilaiHari = (string) $hari->hari; @endphp
                <a href="{{ route('admin.log', array_filter(['tanggal' => $nilaiHari, 'q' => $q])) }}"
                   @class(['hari-kartu', 'aktif' => $tanggal === $nilaiHari])>
                  <span class="hari-tanggal">{{ \Illuminate\Support\Carbon::parse($nilaiHari)->locale('id')->translatedFormat('d M Y') }}</span>
                  <span class="hari-hari">{{ \Illuminate\Support\Carbon::parse($nilaiHari)->locale('id')->translatedFormat('l') }}</span>
                  <span class="hari-jumlah">{{ $hari->jumlah }} aktivitas</span>
                </a>
              @endforeach
            </div>
          </div>
        @endif

        <div class="panel">
          <form method="get" action="{{ route('admin.log') }}" class="search-box log-search" role="search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            <input type="search" name="q" value="{{ $q }}" placeholder="Cari pelaku, tindakan, atau subjek..." aria-label="Cari aktivitas">
            {{-- Filter tanggal ikut terbawa saat mencari, supaya hasil
                 pencarian tidak diam-diam berpindah ke semua hari. --}}
            @if ($tanggal)
              <input type="hidden" name="tanggal" value="{{ $tanggal }}">
            @endif
          </form>

          @if ($tanggal)
            <div class="notice notice-blue" role="note" style="margin-bottom:18px">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
              <div>
                Menampilkan log tanggal
                <b>{{ \Illuminate\Support\Carbon::parse($tanggal)->locale('id')->translatedFormat('l, j F Y') }}</b>.
                <a href="{{ route('admin.log', array_filter(['q' => $q])) }}">Tampilkan semua hari</a>
              </div>
            </div>
          @endif
          <p class="geser-hint">Geser tabel ke samping untuk melihat kolom lainnya →</p>
          <div class="table-wrap">
            <table class="data log-table">
              <thead>
                <tr>
                  <th>Waktu</th>
                  <th>Pelaku</th>
                  <th>Tindakan</th>
                  <th>Subjek</th>
                  <th>Tingkat</th>
                  <th>Keterangan</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($logs as $log)
                  <tr>
                    <td>{{ $log->created_at->locale('id')->translatedFormat('j M Y, H.i') }}</td>
                    <td>
                      <div class="cell-avatar">
                        <div class="avatar">{{ \App\Enums\RoleAdmin::initials($log->pelaku ?? 'Sistem') }}</div>
                        <div class="cell-main">{{ $log->pelaku ?? 'Sistem' }}</div>
                      </div>
                    </td>
                    <td>{{ $log->tindakan }}</td>
                    <td><span class="code">{{ $log->subjek ?: '—' }}</span></td>
                    <td><span class="badge {{ $log->badgeTingkat() }}">{{ $log->tingkat }}</span></td>
                    <td>{{ $log->keterangan ?: '—' }}</td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="muted">Tidak ada aktivitas yang cocok dengan pencarian.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          @if ($logs->hasPages())
            <div class="panel-foot">
              <span>Menampilkan {{ $logs->firstItem() }}–{{ $logs->lastItem() }} dari {{ $logs->total() }} aktivitas</span>
              <div class="flex gap-8">
                @if ($logs->onFirstPage())
                  <span class="btn btn-outline btn-sm" style="opacity:.5;pointer-events:none">Sebelumnya</span>
                @else
                  <a href="{{ $logs->previousPageUrl() }}" class="btn btn-outline btn-sm">Sebelumnya</a>
                @endif
                @if ($logs->hasMorePages())
                  <a href="{{ $logs->nextPageUrl() }}" class="btn btn-outline btn-sm">Berikutnya</a>
                @else
                  <span class="btn btn-outline btn-sm" style="opacity:.5;pointer-events:none">Berikutnya</span>
                @endif
              </div>
            </div>
          @endif
        </div>

        <div class="panel">
          <div class="panel-head">
            <div>
              <h2>Aktivitas yang dicatat</h2>
              <p>Jenis tindakan yang masuk ke log.</p>
            </div>
          </div>
          <div class="log-cols">
            <ul class="doc-bullets">
              <li>Persetujuan, penolakan, dan permintaan perbaikan.</li>
              <li>Login dan logout admin.</li>
              <li>Akses dokumen privat oleh admin.</li>
            </ul>
            <ul class="doc-bullets">
              <li>Perubahan status pengajuan dan dokumen.</li>
              <li>Publikasi dan perubahan konten website.</li>
              <li>Perubahan profil desa dan pengaturan akun.</li>
            </ul>
          </div>
        </div>
      </main>
@endsection
