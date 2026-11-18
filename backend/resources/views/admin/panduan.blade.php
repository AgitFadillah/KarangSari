@extends('layouts.admin')

@section('title', 'Panduan Admin · Dashboard Admin '.$profil->nama_desa)

@section('topbar')
@php $adminTrail = [['label' => 'Admin', 'route' => 'admin.ringkasan'], ['label' => 'Panduan Admin', 'route' => null]]; @endphp
<x-admin-topbar :trail="$adminTrail" title="Panduan Admin" />
@endsection

@section('content')
@php
  use App\Enums\StatusPengajuan;

  $alur = [];
  foreach (StatusPengajuan::cases() as $status) {
      $alur[] = $status;
  }
@endphp

<main class="admin-content">
  <div class="admin-page-head">
    <div>
      <p class="sub">Panduan lengkap alur kerja panel admin: verifikasi pengajuan, pemeriksaan dokumen, perubahan status, sampai pengelolaan konten website desa.</p>
    </div>
    <a href="{{ route('admin.pengajuan.index') }}" class="btn btn-outline btn-sm">Buka daftar pengajuan</a>
  </div>

  <div class="notice notice-blue" role="status">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 16v-5M12 8h.01"/></svg>
    <span>Persetujuan admin <b>bukan</b> pengesahan surat. Surat baru sah setelah ditandatangani basah oleh pejabat berwenang.</span>
  </div>

  {{-- 1. Alur pengajuan --}}
  <div class="panel">
    <div class="panel-head">
      <div>
        <h2>1. Alur status pengajuan</h2>
        <p>Setiap perubahan status wajib mengikuti urutan berikut.</p>
      </div>
      <span class="badge badge-gray">{{ count($alur) }} status</span>
    </div>
    <div class="timeline">
      @foreach ($alur as $status)
        <div class="timeline-item">
          <span class="timeline-dot {{ $status->isSelesai() ? '' : 'green' }}" aria-hidden="true"></span>
          <div class="between">
            <b>{{ $status->label() }}</b>
            <span class="badge {{ $status->badge() }}">{{ $status->value }}</span>
          </div>
          <p class="small muted mt-8">
            @switch($status)
              @case(StatusPengajuan::MenungguVerifikasi)
                Pengajuan baru masuk dari warga (atau warga baru mengirim revisi). Belum ditangani.
                @break
              @case(StatusPengajuan::PerluPerbaikan)
                Data/isian kurang atau keliru. Wajib menuliskan catatan untuk pemohon. Warga dapat memperbaiki sendiri lewat halaman cek status tanpa membuat nomor baru. Bila ternyata sudah benar, pengajuan tetap boleh langsung <b>Disetujui</b>.
                @break
              @case(StatusPengajuan::Disetujui)
                Data dan isian surat sudah benar. Surat siap disiapkan.
                @break
              @case(StatusPengajuan::SedangDiproses)
                Surat sedang dicetak / ditandatangani.
                @break
              @case(StatusPengajuan::SiapDiambil)
                Surat sudah sah dan menunggu diambil warga di kantor desa. Hubungi warga lewat WhatsApp.
                @break
              @case(StatusPengajuan::Selesai)
                Surat sudah diambil. Pengajuan ditutup dan tidak dapat diubah lagi.
                @break
              @case(StatusPengajuan::Ditolak)
                Pengajuan ditolak (mis. bukan warga desa). Wajib menuliskan alasan. Tidak dapat diubah lagi.
                @break
            @endswitch
          </p>
        </div>
      @endforeach
    </div>
    <p class="note-box mt-16">Transisi tidak sah tidak akan ditampilkan pada form ubah status, sehingga admin tidak mungkin melompati tahapan.</p>
  </div>

  {{-- 2. Verifikasi pengajuan --}}
  <div class="panel">
    <div class="panel-head">
      <div>
        <h2>2. Memverifikasi pengajuan</h2>
        <p>Langkah pemeriksaan pada halaman detail pengajuan.</p>
      </div>
    </div>
    <div class="timeline">
      <div class="timeline-item">
        <span class="timeline-dot green" aria-hidden="true"></span>
        <b>Buka detail pengajuan</b>
        <p class="small muted mt-8">Menu <b>Pengajuan Surat</b> → klik nomor referensi. Nomor yang menunggu tindakan ditandai badge pada sidebar.</p>
      </div>
      <div class="timeline-item">
        <span class="timeline-dot green" aria-hidden="true"></span>
        <b>Periksa data pemohon</b>
        <p class="small muted mt-8">Nama, alamat, keperluan, dan WhatsApp. Data kependudukan (NIK, tempat/tanggal lahir) wajib karena tercetak pada surat resmi desa.</p>
      </div>
      <div class="timeline-item">
        <span class="timeline-dot green" aria-hidden="true"></span>
        <b>Periksa isian surat</b>
        <p class="small muted mt-8">Panel <b>Isian surat</b> menampilkan nilai yang diisi warga sesuai template. Pratinjau surat pada panel kanan sudah tersusun otomatis dari template resmi.</p>
      </div>
      <div class="timeline-item">
        <span class="timeline-dot green" aria-hidden="true"></span>
        <b>Periksa dokumen (bila ada)</b>
        <p class="small muted mt-8">Lihat bagian 3 di bawah. Tidak semua jenis surat punya dokumen unggahan.</p>
      </div>
      <div class="timeline-item">
        <span class="timeline-dot green" aria-hidden="true"></span>
        <b>Ubah status + catatan</b>
        <p class="small muted mt-8">Pilih status baru pada panel <b>Ubah status pengajuan</b>. Catatan <b>wajib</b> diisi saat meminta perbaikan atau menolak &mdash; catatan tampil pada riwayat warga.</p>
      </div>
    </div>
  </div>

  {{-- 3. Verifikasi dokumen --}}
  <div class="panel">
    <div class="panel-head">
      <div>
        <h2>3. Verifikasi dokumen</h2>
        <p>Hanya muncul bila warga mengunggah berkas persyaratan.</p>
      </div>
    </div>
    <div class="data-list">
      <div class="field-block">
        <div class="label-cap">LIHAT / UNDUH</div>
        <b>Tombol <b>Unduh</b> membuka dokumen. Setiap pembukaan tercatat pada Log Aktivitas.</b>
      </div>
      <div class="field-block">
        <div class="label-cap">SETUJUI</div>
        <b>Tandai berkas terbaca jelas dan sesuai. Badge berubah menjadi terverifikasi.</b>
      </div>
      <div class="field-block" style="border-bottom:none">
        <div class="label-cap">TOLAK</div>
        <b>Tandai berkas tidak terbaca / tidak sesuai, lalu minta perbaikan lewat catatan.</b>
      </div>
    </div>
    <p class="note-box mt-16">Dokumen disimpan pada penyimpanan privat (disk <code>local</code>), tidak dapat diakses pengunjung umum. Dokumen asli tetap perlu dibawa saat pengambilan surat.</p>
  </div>

  {{-- 4. Revisi dari warga --}}
  <div class="panel">
    <div class="panel-head">
      <div>
        <h2>4. Revisi dari warga</h2>
        <p>Bagaimana alur perbaikan bekerja tanpa pengajuan baru.</p>
      </div>
    </div>
    <div class="timeline">
      <div class="timeline-item">
        <span class="timeline-dot green" aria-hidden="true"></span>
        <b>Admin meminta perbaikan</b>
        <p class="small muted mt-8">Ubah status ke <b>Perlu Perbaikan</b> dan tulis catatan yang jelas (apa yang salah, apa yang harus diisi).</p>
      </div>
      <div class="timeline-item">
        <span class="timeline-dot green" aria-hidden="true"></span>
        <b>Warga memperbaiki sendiri</b>
        <p class="small muted mt-8">Warga membuka halaman <b>Cek Status</b>, memasukkan nomor referensi dan kode akses, lalu menekan <b>Perbaiki pengajuan</b>. Nomor referensi tetap sama.</p>
      </div>
      <div class="timeline-item">
        <span class="timeline-dot green" aria-hidden="true"></span>
        <b>Status otomatis kembali</b>
        <p class="small muted mt-8">Setelah warga mengirim, status kembali ke <b>Menunggu Verifikasi</b>, penghitung <b>revisi_ke</b> bertambah, dan riwayat mencatat "Warga mengirim perbaikan".</p>
      </div>
    </div>
    <p class="note-box mt-16">Pada detail pengajuan, badge <b>Revisi ke-N</b> menandakan pengajuan tersebut sudah berapa kali diperbaiki.</p>
  </div>

  {{-- 5. Kelola konten --}}
  <div class="panel">
    <div class="panel-head">
      <div>
        <h2>5. Mengelola konten website</h2>
        <p>Semua teks halaman publik diubah dari sini, bukan dari kode.</p>
      </div>
    </div>
    <div class="kv-row"><span class="k">Konten Website</span><span class="v">Teks statis tiap bagian (beranda, profil, footer, dsb).</span></div>
    <div class="kv-row"><span class="k">Berita &amp; Agenda</span><span class="v">Berita, pengumuman, dan agenda. Centang <b>Sorot berita ini</b> agar tampil sebagai banner besar di paling atas halaman Informasi (hanya satu sorotan).</span></div>
    <div class="kv-row"><span class="k">Potensi Desa</span><span class="v">Potensi unggulan yang tampil di beranda dan halaman Potensi.</span></div>
    <div class="kv-row"><span class="k">Statistik Beranda</span><span class="v">Angka ringkas (penduduk, KK, luas wilayah, dst).</span></div>
    <div class="kv-row"><span class="k">Perangkat Desa</span><span class="v">Struktur pemerintahan desa &mdash; tampil pada halaman Profil Desa.</span></div>
    <div class="kv-row"><span class="k">Profil &amp; Kontak</span><span class="v">Identitas desa, jam pelayanan, sejarah, visi &amp; misi, batas wilayah, dan penggunaan lahan (tampil pada halaman Profil &amp; Kontak).</span></div>
    <div class="kv-row"><span class="k">Jenis Surat</span><span class="v">Nama, deskripsi, estimasi, <b>template surat</b>, dan persyaratan tiap layanan.</span></div>
  </div>

  {{-- 6. Template surat --}}
  <div class="panel">
    <div class="panel-head">
      <div>
        <h2>6. Menulis template surat</h2>
        <p>Template menentukan kolom isian yang muncul pada formulir warga.</p>
      </div>
    </div>
    <p class="small muted">Tulis template pada menu <b>Jenis Surat</b> memakai penanda dua kurung kurawal, misalnya:</p>
    <div class="letter" style="margin-top:12px">
      <p style="white-space:pre-line">@verbatim Yang bertanda tangan di bawah ini, Kepala {{nama_desa}}, menerangkan bahwa {{nama}} berdomisili di {{alamat}} memiliki usaha {{nama_usaha}}. @endverbatim</p>
    </div>
    <div class="data-list" style="margin-top:16px">
      <div class="field-block">
        <div class="label-cap">PENANDA OTOMATIS</div>
        <b>{{ implode(', ', array_keys(\App\Services\TemplateSuratService::PENANDA_OTOMATIS)) }}</b>
        <p class="small muted">Terisi sendiri dari data pemohon / profil desa. Tidak menjadi kolom isian.</p>
      </div>
      <div class="field-block" style="border-bottom:none">
        <div class="label-cap">PENANDA LAIN</div>
        <b>@verbatim Contoh: {{nama_usaha}}, {{penghasilan_per_bulan}} @endverbatim</b>
        <p class="small muted">Otomatis menjadi kolom isian wajib pada formulir warga, lengkap dengan pratinjau surat live.</p>
      </div>
    </div>
  </div>

  {{-- 7. Hak akses --}}
  <div class="panel">
    <div class="panel-head">
      <div>
        <h2>7. Hak akses per peran</h2>
        <p>Kelola akun pada menu Akun Admin.</p>
      </div>
    </div>
    <div class="kv-row"><span class="k"><span class="badge badge-blue">Administrator</span></span><span class="v">Semua menu: verifikasi, jenis surat, konten, akun, log.</span></div>
    <div class="kv-row"><span class="k"><span class="badge badge-green">Verifikator</span></span><span class="v">Verifikasi pengajuan dan dokumen, ubah status, kelola jenis surat.</span></div>
    <div class="kv-row"><span class="k"><span class="badge badge-gray">Operator Konten</span></span><span class="v">Kelola berita, potensi, statistik, perangkat, konten, profil. <b>Tidak</b> dapat membuka data pengajuan warga (NIK/KK).</span></div>
  </div>

  <div class="panel panel-flat">
    <div style="font-weight:700;margin-bottom:8px">Panduan untuk warga</div>
    <p style="font-size:.9rem;color:var(--gray-600);margin-bottom:14px">Bagikan tautan panduan warga agar pemohon memahami alur, penomoran referensi, dan cara memperbaiki pengajuan.</p>
    <a href="{{ route('panduan') }}" class="panel-link" target="_blank" rel="noopener">Buka panduan warga →</a>
  </div>
</main>
@endsection
