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

        <div class="panel">
          <form method="get" action="{{ route('admin.log') }}" class="search-box log-search" role="search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            <input type="search" name="q" value="{{ $q }}" placeholder="Cari pelaku, tindakan, atau subjek..." aria-label="Cari aktivitas">
          </form>
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
