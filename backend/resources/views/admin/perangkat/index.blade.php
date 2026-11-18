@extends('layouts.admin')

@section('title', 'Perangkat Desa · Dashboard Admin '.$profil->nama_desa)

@push('styles')
<style>
.perangkat-table { min-width: 660px; }
</style>
@endpush

@section('topbar')
@php $adminTrail = [['label' => 'Admin', 'route' => 'admin.ringkasan'], ['label' => 'Perangkat Desa', 'route' => null]]; @endphp
<x-admin-topbar :trail="$adminTrail" title="Perangkat Desa" />
@endsection

@section('content')
<main class="admin-content">
        <div class="admin-page-head">
          <div>
            <p class="sub">Nama dan jabatan perangkat desa yang tampil di halaman Profil. Urutan 1 tampil paling atas.</p>
          </div>
          <a href="{{ route('admin.perangkat.create') }}" class="btn btn-primary btn-sm">Tambah perangkat</a>
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
              <h2>Daftar perangkat</h2>
              <p>Jabatan "Kepala Desa" akan ditampilkan sebagai pejabat penanda tangan surat.</p>
            </div>
            <span class="badge badge-gray">{{ $perangkats->count() }} orang</span>
          </div>
          <div class="table-wrap">
            <table class="data perangkat-table">
              <thead>
                <tr>
                  <th>Nama</th>
                  <th>Jabatan</th>
                  <th>NIP</th>
                  <th>Urutan</th>
                  <th>Status</th>
                  <th aria-label="Aksi"></th>
                </tr>
              </thead>
              <tbody>
                @forelse ($perangkats as $perangkat)
                  @php
                    $fotoAdmin = \App\Support\BerkasPublik::ada($perangkat->foto_path)
                        ? \App\Support\BerkasPublik::url($perangkat->foto_path)
                        : null;
                  @endphp
                  <tr>
                    <td>
                      <div class="cell-avatar">
                        @if ($fotoAdmin)
                          <img class="avatar avatar-foto" src="{{ $fotoAdmin }}"
                               alt="Foto {{ $perangkat->nama }}" loading="lazy" width="40" height="40">
                        @else
                          <div class="avatar">{{ \App\Enums\RoleAdmin::initials($perangkat->nama) }}</div>
                        @endif
                        <div class="cell-main">{{ $perangkat->nama }}</div>
                      </div>
                    </td>
                    <td>{{ $perangkat->jabatan }}</td>
                    <td>{{ $perangkat->nip ?: '—' }}</td>
                    <td>{{ $perangkat->urutan }}</td>
                    <td>
                      @if ($perangkat->is_aktif)
                        <span class="badge badge-green">Aktif</span>
                      @else
                        <span class="badge badge-amber">Nonaktif</span>
                      @endif
                    </td>
                    <td class="right action-cell">
                      <a href="{{ route('admin.perangkat.edit', $perangkat) }}" class="btn btn-outline btn-sm">Ubah</a>
                      <form method="POST" action="{{ route('admin.perangkat.destroy', $perangkat) }}"
                            onsubmit="return confirm('Hapus data perangkat ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline btn-sm">Hapus</button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="muted">Belum ada data perangkat desa.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </main>
@endsection
