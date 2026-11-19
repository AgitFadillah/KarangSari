@extends('layouts.admin')

@section('title', 'Edit Konten · Dashboard Admin '.$profil->nama_desa)

@section('topbar')
@php $adminTrail = [
    ['label' => 'Admin', 'route' => 'admin.ringkasan'],
    ['label' => 'Konten Website', 'route' => 'admin.konten.index'],
    ['label' => Str::headline($konten->bagian), 'route' => null],
]; @endphp
<x-admin-topbar :trail="$adminTrail" title="Edit Konten Website" />
@endsection

@section('content')
<main class="admin-content">
        <div class="admin-page-head">
          <div>
            <p class="sub">Ubah judul, isi, dan status publikasi konten halaman publik.</p>
          </div>
          <a href="{{ route('admin.konten.index') }}" class="btn btn-outline btn-sm">Kembali ke daftar</a>
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
          <form method="POST" action="{{ route('admin.konten.update', $konten) }}">
            @csrf
            @method('PUT')
            <div class="field mb-16">
              <label for="judul">Judul konten</label>
              <input type="text" id="judul" name="judul" value="{{ old('judul', $konten->judul) }}" required>
            </div>
            <div class="field mb-16">
              <label for="bagian">Bagian</label>
              <input type="text" id="bagian" value="{{ $konten->bagian }}" disabled>
              <p class="field-help">Bagian menentukan di mana konten ini ditampilkan.</p>
            </div>
            <div class="field mb-16">
              <label for="isi">Isi konten</label>
              <textarea id="isi" name="isi" rows="8">{{ old('isi', $konten->isi) }}</textarea>
            </div>
            <div class="field mb-16">
              <label for="status">Status publikasi</label>
              <select id="status" name="status">
                <option value="{{ \App\Models\KontenWebsite::TERBIT }}" @selected(old('status', $konten->status) === \App\Models\KontenWebsite::TERBIT)>Terbit</option>
                <option value="{{ \App\Models\KontenWebsite::DRAF }}" @selected(old('status', $konten->status) === \App\Models\KontenWebsite::DRAF)>Draf</option>
              </select>
              <p class="field-help">
                Status saat ini:
                <b>{{ $konten->labelStatus() }}</b>.
                @if ($konten->isTerbit())
                  Konten ini sedang tampil di halaman publik.
                @else
                  Konten draf tidak tampil di halaman publik.
                @endif
              </p>
            </div>
            <div class="panel-foot">
              <span>Pilih salah satu tombol di kanan untuk menyimpan sekaligus menentukan status.</span>
              <div class="flex gap-8" style="flex-wrap:wrap">
                {{-- Tombol eksplisit: perangkat desa tidak perlu menyentuh
                     dropdown sama sekali untuk menerbitkan konten. --}}
                <button type="submit" name="drafkan" value="1" class="btn btn-outline">
                  Simpan sebagai draf
                </button>
                <button type="submit" name="terbitkan" value="1" class="btn btn-primary">
                  Terbitkan sekarang
                </button>
              </div>
            </div>
          </form>
        </div>
      </main>
@endsection
