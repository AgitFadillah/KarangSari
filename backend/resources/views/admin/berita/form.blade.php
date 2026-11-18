@extends('layouts.admin')

@php
  $baru = ! $berita->exists;
@endphp

@section('title', ($baru ? 'Tulis Berita' : 'Ubah Berita').' · Dashboard Admin '.$profil->nama_desa)

@section('topbar')
@php $adminTrail = [
    ['label' => 'Admin', 'route' => 'admin.ringkasan'],
    ['label' => 'Berita & Agenda', 'route' => 'admin.berita.index'],
    ['label' => $baru ? 'Baru' : 'Ubah', 'route' => null],
]; @endphp
<x-admin-topbar :trail="$adminTrail" title="{{ $baru ? 'Tulis Berita' : 'Ubah Berita' }}" />
@endsection

@section('content')
<main class="admin-content">
        <div class="admin-page-head">
          <div>
            <p class="sub">Isi judul, ringkasan, dan tanggal terbit. Kosongkan tanggal terbit untuk menyimpan sebagai draf.</p>
          </div>
          <a href="{{ route('admin.berita.index') }}" class="btn btn-outline btn-sm">Kembali ke daftar</a>
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
                action="{{ $baru ? route('admin.berita.store') : route('admin.berita.update', $berita) }}">
            @csrf
            @if (! $baru)
              @method('PUT')
            @endif
            <div class="field mb-16">
              <label for="judul">Judul</label>
              <input type="text" id="judul" name="judul" value="{{ old('judul', $berita->judul) }}" required maxlength="255">
            </div>
            <div class="form-grid">
              <div class="field">
                <label for="kategori">Kategori</label>
                <select id="kategori" name="kategori">
                  @foreach ($kategori as $opsi)
                    <option value="{{ $opsi }}" @selected(old('kategori', $berita->kategori) === $opsi)>{{ ucfirst($opsi) }}</option>
                  @endforeach
                </select>
                <p class="field-help">
                  <b>Berita</b> tampil di sorotan &amp; arsip. <b>Pengumuman</b> tampil pada blok khusus.
                  <b>Agenda</b> tampil pada daftar kegiatan terdekat.
                </p>
              </div>
              <div class="field">
                <label for="terbit_at" id="terbit_at_label">Tanggal &amp; waktu</label>
                <input type="datetime-local" id="terbit_at" name="terbit_at"
                       value="{{ old('terbit_at', $berita->terbit_at?->format('Y-m-d\TH:i')) }}">
                {{-- Keterangan menyesuaikan kategori; diperbarui oleh skrip
                     di bawah agar tidak perlu menyimpan form lebih dulu. --}}
                <p class="field-help" id="terbit_at_help">
                  @if (old('kategori', $berita->kategori) === 'agenda')
                    Isi tanggal dan jam kegiatan. Entri tampil di daftar agenda terdekat setelah waktu ini terlewati.
                  @else
                    Kosongkan untuk menyimpan sebagai draf.
                  @endif
                </p>
              </div>
            </div>
            <div class="field mb-16">
              <label for="penulis">Penulis</label>
              <input type="text" id="penulis" name="penulis" value="{{ old('penulis', $berita->penulis) }}" maxlength="120" placeholder="mis. Pemerintah Desa">
            </div>

            {{-- Sorotan: banner besar paling atas pada halaman Informasi. --}}
            <div class="field mb-16">
              <div class="checkbox-row">
                <input type="checkbox" id="is_highlight" name="is_highlight" value="1" @checked(old('is_highlight', $berita->is_highlight))>
                <label for="is_highlight">Sorot berita ini (banner besar di paling atas halaman Informasi)</label>
              </div>
              <p class="field-help">Hanya satu berita yang dapat disorot. Menandai berita ini otomatis melepas sorotan berita lain.</p>
            </div>

            <div class="field mb-16">
              <label for="ringkasan">Ringkasan</label>
              <textarea id="ringkasan" name="ringkasan" rows="3" maxlength="1000">{{ old('ringkasan', $berita->ringkasan) }}</textarea>
              <p class="field-help">Tampil pada kartu berita di beranda dan halaman Informasi.</p>
            </div>
            <div class="field mb-16">
              <label for="isi">Isi lengkap</label>
              <textarea id="isi" name="isi" rows="10">{{ old('isi', $berita->isi) }}</textarea>
              <p class="field-help">Teks biasa. Baris baru dipertahankan saat ditampilkan.</p>
            </div>
            <div class="panel-foot">
              <span>{{ $baru ? 'Berita baru langsung muncul di daftar.' : 'Perubahan langsung berlaku di situs publik.' }}</span>
              <button type="submit" class="btn btn-primary">{{ $baru ? 'Simpan berita' : 'Simpan perubahan' }}</button>
            </div>
          </form>
        </div>
      </main>
@endsection

@push('scripts')
<script>
  // Keterangan tanggal mengikuti kategori yang dipilih. Dropdown kustom
  // milik kita tetap memicu event "change" pada <select> aslinya, jadi
  // pendengar ini bekerja untuk keduanya.
  (function () {
    var kategori = document.getElementById('kategori');
    var bantuan = document.getElementById('terbit_at_help');
    if (!kategori || !bantuan) return;

    var teks = {
      agenda: 'Isi tanggal dan jam kegiatan. Entri tampil di daftar agenda terdekat setelah waktu ini terlewati.',
      pengumuman: 'Kosongkan untuk menyimpan sebagai draf. Pengumuman tampil pada blok khusus di beranda.',
      berita: 'Kosongkan untuk menyimpan sebagai draf.'
    };

    function segarkan() {
      bantuan.textContent = teks[kategori.value] || teks.berita;
    }

    kategori.addEventListener('change', segarkan);
    segarkan();
  })();
</script>
@endpush
