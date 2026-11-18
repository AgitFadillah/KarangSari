@extends('layouts.admin')

@section('title', 'Statistik Beranda · Dashboard Admin '.$profil->nama_desa)

@push('styles')
<style>
.statistik-table { min-width: 640px; }
</style>
@endpush

@section('topbar')
@php $adminTrail = [['label' => 'Admin', 'route' => 'admin.ringkasan'], ['label' => 'Statistik Beranda', 'route' => null]]; @endphp
<x-admin-topbar :trail="$adminTrail" title="Statistik Beranda" />
@endsection

@section('content')
<main class="admin-content">
        <div class="admin-page-head">
          <div>
            <p class="sub">Angka ringkas pada blok "Sekilas Desa" di beranda, mis. jumlah penduduk dan kepala keluarga.</p>
          </div>
          <a href="{{ route('admin.statistik.create') }}" class="btn btn-primary btn-sm">Tambah statistik</a>
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
              <h2>Daftar statistik</h2>
              <p>Disarankan 4 angka agar tata letak beranda tetap seimbang.</p>
            </div>
            <span class="badge badge-gray">{{ $statistiks->count() }} entri</span>
          </div>
          <div class="table-wrap">
            <table class="data statistik-table">
              <thead>
                <tr>
                  <th>Label</th>
                  <th>Nilai</th>
                  <th>Satuan</th>
                  <th>Urutan</th>
                  <th>Status</th>
                  <th aria-label="Aksi"></th>
                </tr>
              </thead>
              <tbody>
                @forelse ($statistiks as $statistik)
                  <tr>
                    <td><div class="cell-main">{{ $statistik->label }}</div></td>
                    <td><strong>{{ $statistik->nilai }}</strong></td>
                    <td>{{ $statistik->satuan ?: '—' }}</td>
                    <td>{{ $statistik->urutan }}</td>
                    <td>
                      @if ($statistik->is_aktif)
                        <span class="badge badge-green">Aktif</span>
                      @else
                        <span class="badge badge-amber">Nonaktif</span>
                      @endif
                    </td>
                    <td class="right action-cell">
                      <a href="{{ route('admin.statistik.edit', $statistik) }}" class="btn btn-outline btn-sm">Ubah</a>
                      <form method="POST" action="{{ route('admin.statistik.destroy', $statistik) }}"
                            onsubmit="return confirm('Hapus statistik ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline btn-sm">Hapus</button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="muted">Belum ada statistik. Blok "Sekilas Desa" akan tampil tanpa angka.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </main>
@endsection
