@extends('layouts.admin')

@php
  $baru = ! $statistik->exists;
@endphp

@section('title', ($baru ? 'Tambah Statistik' : 'Ubah Statistik').' · Dashboard Admin '.$profil->nama_desa)

@section('topbar')
@php $adminTrail = [
    ['label' => 'Admin', 'route' => 'admin.ringkasan'],
    ['label' => 'Statistik Beranda', 'route' => 'admin.statistik.index'],
    ['label' => $baru ? 'Baru' : 'Ubah', 'route' => null],
]; @endphp
<x-admin-topbar :trail="$adminTrail" title="{{ $baru ? 'Tambah Statistik' : 'Ubah Statistik' }}" />
@endsection

@section('content')
<main class="admin-content">
        <div class="admin-page-head">
          <div>
            <p class="sub">Contoh: label "Penduduk", nilai "4812", satuan "jiwa terdata".</p>
          </div>
          <a href="{{ route('admin.statistik.index') }}" class="btn btn-outline btn-sm">Kembali ke daftar</a>
        </div>

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

        <div class="panel">
          <form method="POST"
                action="{{ $baru ? route('admin.statistik.store') : route('admin.statistik.update', $statistik) }}">
            @csrf
            @if (! $baru)
              @method('PUT')
            @endif
            <div class="form-grid">
              <div class="field">
                <label for="label">Label</label>
                <input type="text" id="label" name="label" value="{{ old('label', $statistik->label) }}" required maxlength="80" placeholder="mis. Penduduk">
              </div>
              <div class="field">
                <label for="nilai">Nilai</label>
                <input type="text" id="nilai" name="nilai" value="{{ old('nilai', $statistik->nilai) }}" required maxlength="30" placeholder="mis. 4812">
                <p class="field-help">Boleh berupa angka atau teks pendek.</p>
              </div>
              <div class="field">
                <label for="satuan">Satuan / keterangan</label>
                <input type="text" id="satuan" name="satuan" value="{{ old('satuan', $statistik->satuan) }}" maxlength="50" placeholder="mis. jiwa terdata">
              </div>
              <div class="field">
                <label for="urutan">Urutan tampil</label>
                <input type="number" id="urutan" name="urutan" min="0" max="9999" value="{{ old('urutan', $statistik->urutan ?? 0) }}">
              </div>
            </div>
            <div class="field mb-16">
              <label for="is_aktif">Status</label>
              <select id="is_aktif" name="is_aktif">
                <option value="1" @selected(\App\Support\TeksTampil::skalar(old('is_aktif', $statistik->is_aktif ? '1' : '0')) === '1')>Aktif</option>
                <option value="0" @selected(\App\Support\TeksTampil::skalar(old('is_aktif', $statistik->is_aktif ? '1' : '0')) === '0')>Nonaktif</option>
              </select>
              <p class="field-help">Statistik nonaktif tidak ditampilkan di beranda.</p>
            </div>
            <div class="panel-foot">
              <span>Perubahan langsung berlaku di beranda.</span>
              <button type="submit" class="btn btn-primary">{{ $baru ? 'Simpan statistik' : 'Simpan perubahan' }}</button>
            </div>
          </form>
        </div>
      </main>
@endsection
