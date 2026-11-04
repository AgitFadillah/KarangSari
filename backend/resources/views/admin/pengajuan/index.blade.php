@extends('layouts.admin')

@section('title', 'Pengajuan Surat · Dashboard Admin '.$profil->nama_desa)

@push('styles')
<style>
.filter-row .search-box { flex: 1 1 240px; min-width: 200px; max-width: 360px; }
    .filter-row .search-box input { width: 100%; max-width: none; }
    .filter-row input[type="date"] { flex: 0 1 auto; min-width: 150px; max-width: 180px; }
    .filter-row select { max-width: 240px; }
    .list-foot { border-top: 1px solid var(--gray-200); margin-top: 4px; padding-top: 16px; }
    .list-note { margin-top: 14px; font-size: .8125rem; color: var(--gray-500); }
    .status-pills a { text-decoration: none; }
</style>
@endpush

@section('topbar')
@php $adminTrail = [['label' => 'Admin', 'route' => 'admin.ringkasan'], ['label' => 'Pengajuan Surat', 'route' => null]]; @endphp
<x-admin-topbar :trail="$adminTrail" title="Pengajuan Surat" />
@endsection

@section('content')
<main class="admin-content">
        <div class="admin-page-head">
          <div>
            <p class="sub">Kelola permohonan surat yang masuk dari warga.</p>
            <p class="count" role="status">{{ $pengajuans->total() }} pengajuan ditemukan</p>
          </div>
          <div class="flex gap-12 wrap">
            <a href="{{ route('admin.pengajuan.index', ['status' => \App\Enums\StatusPengajuan::MenungguVerifikasi->value]) }}" class="btn btn-outline btn-sm">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
              Antrean verifikasi
            </a>
            <a href="{{ route('admin.pengajuan.index') }}" class="btn btn-outline btn-sm">Semua pengajuan</a>
          </div>
        </div>

        @if (session('status'))
          <div class="notice notice-green" role="status" style="margin-bottom:22px">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
            <span>{{ session('status') }}</span>
          </div>
        @endif

        <div class="panel">
          <div class="status-pills" role="group" aria-label="Filter status pengajuan">
            <a href="{{ route('admin.pengajuan.index', array_filter(['q' => $filter['q'], 'jenis' => $filter['jenis'], 'tanggal' => $filter['tanggal']])) }}"
               class="status-pill {{ $filter['status'] === null ? 'active' : '' }}"
               aria-pressed="{{ $filter['status'] === null ? 'true' : 'false' }}">Semua</a>
            @foreach ($statusList as $status)
              <a href="{{ route('admin.pengajuan.index', array_filter(['status' => $status->value, 'q' => $filter['q'], 'jenis' => $filter['jenis'], 'tanggal' => $filter['tanggal']])) }}"
                 class="status-pill {{ $filter['status'] === $status->value ? 'active' : '' }}"
                 aria-pressed="{{ $filter['status'] === $status->value ? 'true' : 'false' }}">{{ $status->label() }}</a>
            @endforeach
          </div>

          <form class="filter-row" method="get" action="{{ route('admin.pengajuan.index') }}">
            @if ($filter['status'])
              <input type="hidden" name="status" value="{{ $filter['status'] }}">
            @endif
            <div class="search-box">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
              <input type="search" name="q" value="{{ $filter['q'] }}" placeholder="Cari nama, kode, atau jenis surat..." aria-label="Cari nama, kode, atau jenis surat">
            </div>
            <select name="jenis" aria-label="Saring menurut jenis surat">
              <option value="">Semua jenis surat</option>
              @foreach ($jenisSurats as $jenis)
                <option value="{{ $jenis->slug }}" @selected($filter['jenis'] === $jenis->slug)>{{ $jenis->nama }}</option>
              @endforeach
            </select>
            <input type="date" name="tanggal" value="{{ $filter['tanggal'] }}" aria-label="Saring menurut tanggal masuk">
            <button type="submit" class="btn btn-primary btn-sm">Terapkan</button>
            <a href="{{ route('admin.pengajuan.index') }}" class="btn btn-outline btn-sm">Atur ulang</a>
          </form>

          <div class="table-wrap">
            <table class="data">
              <thead>
                <tr>
                  <th>Referensi &amp; pemohon</th>
                  <th>Jenis surat</th>
                  <th>Tanggal masuk</th>
                  <th>Status</th>
                  <th aria-label="Aksi"></th>
                </tr>
              </thead>
              <tbody>
                @forelse ($pengajuans as $item)
                  <tr>
                    <td>
                      <div class="cell-avatar">
                        <div class="avatar">{{ \App\Enums\RoleAdmin::initials($item->nama) }}</div>
                        <div>
                          <div class="cell-main">{{ $item->nama }}</div>
                          <div class="cell-sub">{{ $item->nomor_referensi }}</div>
                        </div>
                      </div>
                    </td>
                    <td>{{ $item->jenisSurat->nama }}</td>
                    <td><div>{{ $item->created_at->locale('id')->translatedFormat('j M Y') }}</div><div class="cell-sub">{{ $item->created_at->format('H.i') }}</div></td>
                    <td><span class="badge {{ $item->status->badge() }}">{{ $item->status->label() }}</span></td>
                    <td class="right">
                      <a href="{{ route('admin.pengajuan.show', $item) }}" class="row-arrow" aria-label="Lihat detail pengajuan {{ $item->nomor_referensi }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l6 6-6 6"/></svg>
                      </a>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5">
                      <div class="catalog-empty show" role="status">
                        <h3>Pengajuan tidak ditemukan</h3>
                        <p>Tidak ada pengajuan yang cocok dengan filter atau kata kunci Anda. Coba kata lain, misalnya “domisili” atau “SBM-{{ now()->year }}”.</p>
                        <a href="{{ route('admin.pengajuan.index') }}" class="btn btn-outline btn-sm">Tampilkan semua pengajuan</a>
                      </div>
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          @if ($pengajuans->hasPages())
            <div class="list-foot between">
              <span>Menampilkan {{ $pengajuans->firstItem() }}–{{ $pengajuans->lastItem() }} dari {{ $pengajuans->total() }} pengajuan</span>
              <div class="flex gap-8">
                @if ($pengajuans->onFirstPage())
                  <span class="btn btn-outline btn-sm" style="opacity:.5;pointer-events:none">Sebelumnya</span>
                @else
                  <a href="{{ $pengajuans->previousPageUrl() }}" class="btn btn-outline btn-sm">Sebelumnya</a>
                @endif
                @if ($pengajuans->hasMorePages())
                  <a href="{{ $pengajuans->nextPageUrl() }}" class="btn btn-outline btn-sm">Berikutnya</a>
                @else
                  <span class="btn btn-outline btn-sm" style="opacity:.5;pointer-events:none">Berikutnya</span>
                @endif
              </div>
            </div>
          @endif
        </div>
      </main>
@endsection
