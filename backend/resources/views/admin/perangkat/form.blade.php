@extends('layouts.admin')

@php
  $baru = ! $perangkat->exists;
@endphp

@section('title', ($baru ? 'Tambah Perangkat' : 'Ubah Perangkat').' · Dashboard Admin '.$profil->nama_desa)

@push('styles')
<style>
/* Kolom foto: pratinjau bulat di kiri, tombol pilih berkas di kanan. */
.foto-row { display: flex; gap: 18px; align-items: flex-start; flex-wrap: wrap; }.foto-preview {
  width: 104px; height: 104px; border-radius: 50%; flex: 0 0 auto;
  background: var(--gray-100); border: 1px solid var(--gray-200);
  display: grid; place-items: center; overflow: hidden;
  font-weight: 700; color: var(--gray-500); font-size: 1.5rem; letter-spacing: .02em;
}
.foto-preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
/* Atribut [hidden] kalah oleh display:grid/flex, jadi ditegaskan di sini. */
.foto-preview img[hidden], .foto-preview span[hidden] { display: none !important; }
.foto-aksi { flex: 1 1 260px; min-width: 220px; max-width: 420px; }
.foto-aksi input[type="file"] {
  display: block; width: 100%; max-width: 100%; padding: 10px 12px; cursor: pointer;
  border: 1px dashed var(--gray-200); border-radius: var(--radius-sm);
  background: var(--gray-100); font: inherit; font-size: .875rem; color: var(--gray-600);
}
.foto-aksi input[type="file"]::file-selector-button {
  margin-right: 12px; padding: 7px 14px; cursor: pointer; font: inherit;
  font-size: .8125rem; font-weight: 600; color: var(--navy-900);
  background: var(--white); border: 1px solid var(--gray-200);
  border-radius: var(--radius-full);
}
.foto-aksi input[type="file"]::file-selector-button:hover { border-color: var(--blue-500); color: var(--blue-600); }
.foto-hapus { display: flex; gap: 8px; align-items: center; margin-top: 10px; font-size: .8125rem; color: var(--gray-600); }
</style>
@endpush

@section('topbar')
@php $adminTrail = [
    ['label' => 'Admin', 'route' => 'admin.ringkasan'],
    ['label' => 'Perangkat Desa', 'route' => 'admin.perangkat.index'],
    ['label' => $baru ? 'Baru' : 'Ubah', 'route' => null],
]; @endphp
<x-admin-topbar :trail="$adminTrail" title="{{ $baru ? 'Tambah Perangkat' : 'Ubah Perangkat' }}" />
@endsection

@section('content')
<main class="admin-content">
        <div class="admin-page-head">
          <div>
            <p class="sub">Isi nama sesuai dokumen resmi. NIP boleh dikosongkan.</p>
          </div>
          <a href="{{ route('admin.perangkat.index') }}" class="btn btn-outline btn-sm">Kembali ke daftar</a>
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
                enctype="multipart/form-data"
                action="{{ $baru ? route('admin.perangkat.store') : route('admin.perangkat.update', $perangkat) }}">
            @csrf
            @if (! $baru)
              @method('PUT')
            @endif
            @php
              $fotoSekarang = \App\Support\BerkasPublik::ada($perangkat->foto_path)
                  ? \App\Support\BerkasPublik::url($perangkat->foto_path)
                  : null;
              $inisialFoto = \App\Enums\RoleAdmin::initials($perangkat->nama ?: 'Perangkat Desa');
            @endphp

            <div class="field mb-16">
              <label for="foto">Foto perangkat</label>
              <div class="foto-row">
                <div class="foto-preview" id="foto-preview">
                  <img id="foto-gambar"
                       src="{{ $fotoSekarang ?? '' }}"
                       alt="Foto {{ $perangkat->nama ?: 'perangkat desa' }}"
                       @if (! $fotoSekarang) hidden @endif>
                  <span id="foto-inisial" @if ($fotoSekarang) hidden @endif>{{ $inisialFoto }}</span>
                </div>
                <div class="foto-aksi">
                  <input type="file" id="foto" name="foto"
                         accept="image/png,image/jpeg,image/webp">
                  <p class="field-help">
                    Format JPG, PNG, atau WEBP. Maksimal 2 MB. Sebaiknya foto persegi
                    agar tampil rapi pada kartu struktur pemerintahan.
                  </p>
                  @if ($fotoSekarang)
                    <label class="foto-hapus">
                      <input type="checkbox" name="hapus_foto" value="1" @checked(old('hapus_foto'))>
                      Hapus foto saat ini
                    </label>
                  @endif
                </div>
              </div>
            </div>
            <div class="form-grid">
              <div class="field">
                <label for="nama">Nama lengkap</label>
                <input type="text" id="nama" name="nama" value="{{ old('nama', $perangkat->nama) }}" required maxlength="120">
              </div>
              <div class="field">
                <label for="jabatan">Jabatan</label>
                <input type="text" id="jabatan" name="jabatan" value="{{ old('jabatan', $perangkat->jabatan) }}" required maxlength="120" placeholder="mis. Kepala Desa">
              </div>
              <div class="field">
                <label for="nip">NIP</label>
                <input type="text" id="nip" name="nip" value="{{ old('nip', $perangkat->nip) }}" maxlength="40">
              </div>
              <div class="field">
                <label for="urutan">Urutan tampil</label>
                <input type="number" id="urutan" name="urutan" min="0" max="9999" value="{{ old('urutan', $perangkat->urutan ?? 0) }}">
                <p class="field-help">Angka kecil tampil lebih dahulu.</p>
              </div>
            </div>
            <div class="field mb-16">
              <label for="is_aktif">Status</label>
              <select id="is_aktif" name="is_aktif">
                <option value="1" @selected(\App\Support\TeksTampil::skalar(old('is_aktif', $perangkat->is_aktif ? '1' : '0')) === '1')>Aktif</option>
                <option value="0" @selected(\App\Support\TeksTampil::skalar(old('is_aktif', $perangkat->is_aktif ? '1' : '0')) === '0')>Nonaktif</option>
              </select>
              <p class="field-help">Perangkat nonaktif tidak tampil di halaman Profil.</p>
            </div>
            <div class="panel-foot">
              <span>Perubahan langsung berlaku di halaman Profil.</span>
              <button type="submit" class="btn btn-primary">{{ $baru ? 'Simpan perangkat' : 'Simpan perubahan' }}</button>
            </div>
          </form>
        </div>
      </main>
@endsection

@push('scripts')
<script>
  // Pratinjau foto sebelum diunggah, tanpa perlu menekan "Simpan" dulu.
  (function () {
    var input = document.getElementById('foto');
    var gambar = document.getElementById('foto-gambar');
    var inisial = document.getElementById('foto-inisial');
    if (! input || ! gambar) return;

    input.addEventListener('change', function () {
      var berkas = input.files && input.files[0];
      if (! berkas) return;

      if (berkas.size > 2 * 1024 * 1024) {
        input.value = '';
        window.alert('Ukuran foto melebihi 2 MB. Pilih berkas yang lebih kecil.');
        return;
      }

      gambar.src = URL.createObjectURL(berkas);
      gambar.hidden = false;
      if (inisial) inisial.hidden = true;
    });
  })();
</script>
@endpush
