@extends('layouts.admin')

@section('title', 'Konten Website · Dashboard Admin '.$profil->nama_desa)

@push('styles')
<style>
.konten-table { min-width: 720px; }
    .action-cell { white-space: nowrap; }
</style>
@endpush

@section('topbar')
@php $adminTrail = [['label' => 'Admin', 'route' => 'admin.ringkasan'], ['label' => 'Konten Website', 'route' => null]]; @endphp
<x-admin-topbar :trail="$adminTrail" title="Konten Website" />
@endsection

@section('content')
<main class="admin-content">
        <div class="admin-page-head">
          <div>
            <p class="sub">Kelola teks dan informasi yang tampil pada halaman publik desa.</p>
          </div>
        </div>

        @if (session('status'))
          <div class="notice notice-green" role="status" style="margin-bottom:22px">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
            <span>{{ session('status') }}</span>
          </div>
        @endif

        <div class="panel">
          <div class="panel-head">
            <div>
              <h2>Daftar konten</h2>
              <p>Setiap bagian mewakili satu blok konten pada halaman publik.</p>
            </div>
            <span class="badge badge-gray">{{ $kontens->count() }} bagian</span>
          </div>
          <div class="table-wrap">
            <table class="data konten-table">
              <thead>
                <tr>
                  <th>Bagian</th>
                  <th>Judul</th>
                  <th>Status</th>
                  <th>Diperbarui</th>
                  <th aria-label="Aksi"></th>
                </tr>
              </thead>
              <tbody>
                @forelse ($kontens as $konten)
                  <tr>
                    <td>
                      <div class="cell-main">{{ Str::headline($konten->bagian) }}</div>
                      <div class="cell-sub">{{ $konten->diperbaruiOleh?->name ? 'oleh '.$konten->diperbaruiOleh->name : 'Belum pernah diubah' }}</div>
                    </td>
                    <td>{{ $konten->judul }}</td>
                    <td><span class="badge {{ $konten->badgeStatus() }}">{{ $konten->labelStatus() }}</span></td>
                    <td>
                      <div>{{ $konten->updated_at->locale('id')->translatedFormat('j M Y') }}</div>
                      <div class="cell-sub">{{ $konten->updated_at->format('H.i') }}</div>
                    </td>
                    <td class="right action-cell">
                      <a href="{{ route('admin.konten.edit', $konten) }}" class="btn btn-outline btn-sm">Edit</a>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="muted">Belum ada konten. Jalankan seeder untuk mengisi konten awal.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </main>
@endsection
