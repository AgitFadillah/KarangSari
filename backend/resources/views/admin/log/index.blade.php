@extends('layouts.admin')

@section('title', 'Log Aktivitas · Dashboard Admin '.$profil->nama_desa)

@push('styles')
<style>
.log-search { max-width: 430px; margin-bottom: 20px; }
    .log-table { min-width: 880px; }
    .log-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 36px; }
    @media (max-width: 860px) { .log-cols { grid-template-columns: 1fr; } }
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
