@extends('layouts.admin')

@section('title', 'Jenis Surat · Dashboard Admin '.$profil->nama_desa)

@push('styles')
<style>
.jenis-table { min-width: 720px; }
    .action-cell { white-space: nowrap; }
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
            <p class="sub">Kelola daftar jenis surat yang dapat diajukan warga melalui portal.</p>
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
              <h2>Daftar jenis surat</h2>
              <p>Persyaratan tiap jenis surat menjadi dasar formulir pengajuan warga.</p>
            </div>
            <span class="badge badge-gray">{{ $jenisSurats->count() }} jenis</span>
          </div>
          <div class="table-wrap">
            <table class="data jenis-table">
              <thead>
                <tr>
                  <th>Jenis surat</th>
                  <th>Kode</th>
                  <th>Status</th>
                  <th>Persyaratan</th>
                  <th aria-label="Aksi"></th>
                </tr>
              </thead>
              <tbody>
                @forelse ($jenisSurats as $jenis)
                  <tr>
                    <td>
                      <div class="cell-main">{{ $jenis->nama }}</div>
                      <div class="cell-sub">{{ $jenis->estimasi_hari ?: 'Estimasi belum diatur' }}</div>
                    </td>
                    <td><span class="badge badge-gray">{{ $jenis->kode }}</span></td>
                    <td>
                      @if ($jenis->is_aktif)
                        <span class="badge badge-green">Aktif</span>
                      @else
                        <span class="badge badge-amber">Nonaktif</span>
                      @endif
                    </td>
                    <td>{{ $jenis->persyaratan_count }} berkas</td>
                    <td class="right action-cell">
                      <a href="{{ route('admin.jenis-surat.edit', $jenis) }}" class="btn btn-outline btn-sm">Konfigurasi</a>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="muted">Belum ada jenis surat. Jalankan seeder untuk mengisi data awal.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <p class="list-note">Jenis surat baru ditambahkan melalui seeder atau administrator sistem; halaman ini mengatur konfigurasi yang sudah ada.</p>
      </main>
@endsection
