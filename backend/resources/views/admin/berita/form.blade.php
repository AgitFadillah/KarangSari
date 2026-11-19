@extends('layouts.admin')

@php
  $baru = ! $berita->exists;
@endphp

@section('title', ($baru ? 'Tulis Berita' : 'Ubah Berita').' · Dashboard Admin '.$profil->nama_desa)

@push('styles')
<style>
/* Kolom foto berita: pratinjau di kiri, pemilih berkas di kanan.
   Aturan dasar .foto-* sengaja disalin dari form perangkat karena berkas
   itu hanya memuat gayanya di dalam halaman form perangkat sendiri. */
.foto-row { display: flex; gap: 18px; align-items: flex-start; flex-wrap: wrap; }
.foto-preview {
  flex: 0 0 auto; background: var(--gray-100); border: 1px solid var(--gray-200);
  display: grid; place-items: center; overflow: hidden;
  font-weight: 600; color: var(--gray-500); letter-spacing: .01em;
}
.foto-preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
/* Atribut [hidden] kalah oleh display:grid, jadi ditegaskan di sini. */
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

/* Kotak pratinjau foto berita: bentuknya persegi lebar (bukan bulat seperti
   foto perangkat) karena kartu berita memang memakai gambar mendatar. */
.foto-preview-lebar {
  width: 208px; height: 130px; border-radius: var(--radius-md);
  font-size: .8125rem; text-align: center; padding: 0 12px;
}
@media (max-width: 480px) {
  .foto-preview-lebar { width: 100%; height: 160px; }
}
</style>
@endpush

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
                enctype="multipart/form-data"
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

            {{-- Foto dokumentasi: tampil pada kartu berita di beranda, arsip
                 Informasi, dan halaman detail berita. --}}
            <div class="field mb-16">
              <label for="foto">Foto berita / pengumuman / agenda</label>
              @php $fotoSekarang = $berita->fotoUrl(); @endphp
              <div class="foto-row">
                <div class="foto-preview foto-preview-lebar" id="foto-preview">
                  <img id="foto-gambar"
                       src="{{ $fotoSekarang ?? '' }}"
                       alt="Foto {{ $berita->judul ?: 'berita' }}"
                       @if (! $fotoSekarang) hidden @endif>
                  <span id="foto-kosong" @if ($fotoSekarang) hidden @endif>Belum ada foto</span>
                </div>
                <div class="foto-aksi">
                  <input type="file" id="foto" name="foto"
                         accept="image/png,image/jpeg,image/webp">
                  <p class="field-help">
                    Format JPG, PNG, atau WEBP. Maksimal 5 MB. Foto mendatar (lanskap)
                    paling pas untuk kartu berita.
                  </p>
                  @if ($fotoSekarang)
                    <label class="foto-hapus">
                      <input type="checkbox" name="foto_hapus" value="1" @checked(old('foto_hapus'))>
                      Hapus foto saat ini
                    </label>
                  @endif
                </div>
              </div>
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
  // Pratinjau foto sebelum disimpan, tanpa perlu mengunggah lebih dulu.
  (function () {
    var input = document.getElementById('foto');
    var gambar = document.getElementById('foto-gambar');
    var kosong = document.getElementById('foto-kosong');
    if (! input || ! gambar) return;

    input.addEventListener('change', function () {
      var berkas = input.files && input.files[0];
      if (! berkas) return;

      if (berkas.size > 5 * 1024 * 1024) {
        input.value = '';
        window.alert('Ukuran foto melebihi 5 MB. Pilih berkas yang lebih kecil.');
        return;
      }

      gambar.src = URL.createObjectURL(berkas);
      gambar.hidden = false;
      if (kosong) kosong.hidden = true;
    });
  })();
</script>
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
