@extends('layouts.admin')

@section('title', 'Potensi Desa · Dashboard Admin '.$profil->nama_desa)

@push('styles')
<style>
.potensi-table { min-width: 720px; }
</style>
@endpush

@section('topbar')
@php $adminTrail = [['label' => 'Admin', 'route' => 'admin.ringkasan'], ['label' => 'Potensi Desa', 'route' => null]]; @endphp
<x-admin-topbar :trail="$adminTrail" title="Potensi Desa" />
@endsection

@section('content')
<main class="admin-content">
        <div class="admin-page-head">
          <div>
            <p class="sub">UMKM, wisata, pertanian, dan kerajinan yang tampil di beranda serta halaman Potensi.</p>
          </div>
          <a href="{{ route('admin.potensi.create') }}" class="btn btn-primary btn-sm">Tambah potensi</a>
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
              <h2>Daftar potensi</h2>
              <p>Urutan kecil tampil lebih dahulu pada halaman publik.</p>
            </div>
            <span class="badge badge-gray">{{ $potensis->count() }} entri</span>
          </div>
          <div class="table-wrap">
            <table class="data potensi-table">
              <thead>
                <tr>
                  <th>Nama</th>
                  <th>Kategori</th>
                  <th>Urutan</th>
                  <th>Status</th>
                  <th aria-label="Aksi"></th>
                </tr>
              </thead>
              <tbody>
                @forelse ($potensis as $potensi)
                  <tr>
                    <td>
                      <div class="cell-main">{{ $potensi->nama }}</div>
                      <div class="cell-sub">{{ \Illuminate\Support\Str::limit($potensi->deskripsi ?: '—', 70) }}</div>
                    </td>
                    <td><span class="badge badge-gray">{{ $potensi->kategori ?: '—' }}</span></td>
                    <td>{{ $potensi->urutan }}</td>
                    <td>
                      @if ($potensi->is_aktif)
                        <span class="badge badge-green">Aktif</span>
                      @else
                        <span class="badge badge-amber">Nonaktif</span>
                      @endif
                    </td>
                    <td class="right action-cell">
                      <a href="{{ route('admin.potensi.edit', $potensi) }}" class="btn btn-outline btn-sm">Ubah</a>
                      <form method="POST" action="{{ route('admin.potensi.destroy', $potensi) }}"
                            onsubmit="return confirm('Hapus potensi ini? Tindakan tidak dapat dibatalkan.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline btn-sm">Hapus</button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="muted">Belum ada potensi. Klik "Tambah potensi" untuk mengisi.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </main>
@endsection
