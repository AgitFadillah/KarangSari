@extends('layouts.admin')

@section('title', 'Konfigurasi '.$jenisSurat->nama.' · Dashboard Admin '.$profil->nama_desa)

@push('styles')
<style>
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 720px) { .form-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('topbar')
@php $adminTrail = [
    ['label' => 'Admin', 'route' => 'admin.ringkasan'],
    ['label' => 'Jenis Surat', 'route' => 'admin.jenis-surat.index'],
    ['label' => $jenisSurat->kode, 'route' => null],
]; @endphp
<x-admin-topbar :trail="$adminTrail" title="Konfigurasi Jenis Surat" />
@endsection

@section('content')
<main class="admin-content">
        <div class="admin-page-head">
          <div>
            <p class="sub">Atur kode, persyaratan, dan template surat untuk jenis surat yang dipilih.</p>
          </div>
          <a href="{{ route('admin.jenis-surat.index') }}" class="btn btn-outline btn-sm">Kembali ke daftar</a>
        </div>

        @if (session('status'))
          <div class="notice notice-green" role="status" style="margin-bottom:22px">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
            <span>{{ session('status') }}</span>
          </div>
        @endif

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
          <form method="POST" action="{{ route('admin.jenis-surat.update', $jenisSurat) }}">
            @csrf
            @method('PUT')
            <div class="form-grid">
              <div class="field mb-16">
                <label for="kode">Kode jenis surat</label>
                <input type="text" id="kode" name="kode" value="{{ old('kode', $jenisSurat->kode) }}" required maxlength="20">
              </div>
              <div class="field mb-16">
                <label for="nama">Nama jenis surat</label>
                <input type="text" id="nama" name="nama" value="{{ old('nama', $jenisSurat->nama) }}" required>
              </div>
              <div class="field mb-16">
                <label for="kode_layanan">Kode layanan</label>
                <input type="text" id="kode_layanan" value="{{ $jenisSurat->kode_layanan ?: '—' }}" disabled>
                <p class="field-help">Slug layanan: <code>{{ $jenisSurat->slug }}</code></p>
              </div>
              <div class="field mb-16">
                <label for="estimasi_hari">Estimasi penyelesaian</label>
                <input type="text" id="estimasi_hari" name="estimasi_hari" value="{{ old('estimasi_hari', $jenisSurat->estimasi_hari) }}" placeholder="mis. 2 hari kerja">
              </div>
            </div>
            <div class="field mb-16">
              <label for="deskripsi">Deskripsi</label>
              <textarea id="deskripsi" name="deskripsi" rows="3">{{ old('deskripsi', $jenisSurat->deskripsi) }}</textarea>
              <p class="field-help">Tampil pada kartu katalog dan halaman detail layanan.</p>
            </div>
            <div class="field mb-16">
              <label for="persyaratan">Persyaratan (satu per baris)</label>
              <textarea id="persyaratan" name="persyaratan" rows="5">{{ old('persyaratan', $jenisSurat->persyaratan->pluck('label')->implode("\n")) }}</textarea>
              <p class="field-help">Setiap baris menjadi satu kolom unggah dokumen pada formulir pengajuan.</p>
            </div>
            <div class="field mb-16">
              <label for="is_aktif">Status</label>
              <select id="is_aktif" name="is_aktif">
                <option value="1" @selected((string) old('is_aktif', $jenisSurat->is_aktif ? '1' : '0') === '1')>Aktif</option>
                <option value="0" @selected((string) old('is_aktif', $jenisSurat->is_aktif ? '1' : '0') === '0')>Nonaktif</option>
              </select>
              <p class="field-help">Jenis surat nonaktif tidak muncul pada katalog maupun formulir publik.</p>
            </div>
            <div class="field mb-16">
              <label for="template_surat">Template surat</label>
              <textarea id="template_surat" name="template_surat" rows="8">{{ old('template_surat', $jenisSurat->template_surat) }}</textarea>
            </div>
            <div class="panel-foot">
              <span>Perubahan langsung berlaku untuk pengajuan berikutnya.</span>
              <button type="submit" class="btn btn-primary">Simpan konfigurasi</button>
            </div>
          </form>
        </div>
      </main>
@endsection
