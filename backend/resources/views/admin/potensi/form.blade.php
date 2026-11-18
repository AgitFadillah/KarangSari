@extends('layouts.admin')

@php
  $baru = ! $potensi->exists;
@endphp

@section('title', ($baru ? 'Tambah Potensi' : 'Ubah Potensi').' · Dashboard Admin '.$profil->nama_desa)

@section('topbar')
@php $adminTrail = [
    ['label' => 'Admin', 'route' => 'admin.ringkasan'],
    ['label' => 'Potensi Desa', 'route' => 'admin.potensi.index'],
    ['label' => $baru ? 'Baru' : 'Ubah', 'route' => null],
]; @endphp
<x-admin-topbar :trail="$adminTrail" title="{{ $baru ? 'Tambah Potensi' : 'Ubah Potensi' }}" />
@endsection

@section('content')
<main class="admin-content">
        <div class="admin-page-head">
          <div>
            <p class="sub">Deskripsikan potensi secara singkat agar mudah dibaca warga dan pengunjung.</p>
          </div>
          <a href="{{ route('admin.potensi.index') }}" class="btn btn-outline btn-sm">Kembali ke daftar</a>
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
                action="{{ $baru ? route('admin.potensi.store') : route('admin.potensi.update', $potensi) }}">
            @csrf
            @if (! $baru)
              @method('PUT')
            @endif
            <div class="form-grid">
              <div class="field">
                <label for="nama">Nama potensi</label>
                <input type="text" id="nama" name="nama" value="{{ old('nama', $potensi->nama) }}" required maxlength="255">
              </div>
              <div class="field">
                <label for="kategori">Kategori</label>
                <input type="text" id="kategori" name="kategori" value="{{ old('kategori', $potensi->kategori) }}" maxlength="60" placeholder="mis. UMKM, Wisata, Pertanian">
              </div>
              <div class="field">
                <label for="kontak">Kontak / pengelola</label>
                <input type="text" id="kontak" name="kontak" value="{{ old('kontak', $potensi->kontak) }}" maxlength="120" placeholder="mis. Kelompok Tani Lereng Puncak">
              </div>
              <div class="field">
                <label for="urutan">Urutan tampil</label>
                <input type="number" id="urutan" name="urutan" min="0" max="9999" value="{{ old('urutan', $potensi->urutan ?? 0) }}">
                <p class="field-help">Angka kecil tampil lebih dahulu.</p>
              </div>
            </div>
            <div class="field mb-16">
              <label for="deskripsi">Deskripsi</label>
              <textarea id="deskripsi" name="deskripsi" rows="5" maxlength="2000">{{ old('deskripsi', $potensi->deskripsi) }}</textarea>
            </div>
            <div class="field mb-16">
              <label for="is_aktif">Status</label>
              <select id="is_aktif" name="is_aktif">
                <option value="1" @selected(\App\Support\TeksTampil::skalar(old('is_aktif', $potensi->is_aktif ? '1' : '0')) === '1')>Aktif</option>
                <option value="0" @selected(\App\Support\TeksTampil::skalar(old('is_aktif', $potensi->is_aktif ? '1' : '0')) === '0')>Nonaktif</option>
              </select>
              <p class="field-help">Potensi nonaktif tidak tampil di situs publik.</p>
            </div>
            <div class="panel-foot">
              <span>Perubahan langsung berlaku di situs publik.</span>
              <button type="submit" class="btn btn-primary">{{ $baru ? 'Simpan potensi' : 'Simpan perubahan' }}</button>
            </div>
          </form>
        </div>
      </main>
@endsection
