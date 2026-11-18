@extends('layouts.public')

@section('title', 'Panduan Pengajuan Surat · '.$profil->nama_desa)
@section('description', 'Panduan lengkap mengajukan surat desa: memilih layanan, mengisi surat sesuai template, memantau status dengan nomor referensi dan kode akses, sampai mengambil surat di kantor desa.')

@section('content')

  <section class="page-hero page-hero-soft">
    <div class="container">
      <nav class="breadcrumb">
        <a href="{{ route('beranda') }}">Beranda</a>
        <span class="sep">/</span>
        <span>Panduan</span>
      </nav>
      <div class="page-hero-row">
        <div>
          <div class="eyebrow eyebrow-plain" style="color:var(--blue-600)">Panduan Warga</div>
          <h1>Panduan pengajuan surat {{ $profil->nama_desa }}</h1>
          <p class="lead">Ikuti alur berikut agar pengajuan surat Anda cepat diproses. Tidak perlu membuat akun &mdash; cukup simpan nomor referensi dan kode akses yang Anda terima.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="section form-page">
    <div class="container">
      <div class="form-layout">
        <div>
          @php
            $langkah = [
              [
                'judul' => 'Pilih jenis surat',
                'isi' => 'Buka halaman Pelayanan, pilih surat yang Anda butuhkan, lalu baca deskripsi, estimasi waktu, dan persyaratannya.',
                'tautan' => ['label' => 'Lihat daftar layanan', 'route' => 'pelayanan'],
              ],
              [
                'judul' => 'Isi data pemohon',
                'isi' => 'Cukup lengkapi nama lengkap, alamat, keperluan surat, dan nomor WhatsApp aktif. Nomor WhatsApp dipakai admin untuk mengabari bila ada perbaikan atau surat sudah siap diambil.',
                'tautan' => ['label' => 'Mulai ajukan surat', 'route' => 'pengajuan.create'],
              ],
              [
                'judul' => 'Isi surat sesuai template desa',
                'isi' => 'Setiap jenis surat memiliki template resmi desa. Kolom isian yang muncul diambil dari penanda pada template, dan pratinjau surat diperbarui langsung saat Anda mengetik.',
                'tautan' => null,
              ],
              [
                'judul' => 'Simpan nomor referensi & kode akses',
                'isi' => 'Setelah mengirim, Anda menerima nomor referensi unik (contoh: '.config('desa.prefix_referensi').'-'.now()->year.'-0001) beserta kode akses rahasia. Keduanya diperlukan untuk memantau surat Anda. Jangan sampai hilang.',
                'tautan' => null,
              ],
              [
                'judul' => 'Pantau status surat',
                'isi' => 'Buka halaman Cek Status, masukkan nomor referensi dan kode akses, lalu lihat posisi surat Anda: menunggu verifikasi, diproses, perlu perbaikan, siap diambil, atau selesai.',
                'tautan' => ['label' => 'Cek status pengajuan', 'route' => 'cek-status.form'],
              ],
              [
                'judul' => 'Perbaiki bila diminta',
                'isi' => 'Jika admin menandai "Perlu Perbaikan", Anda dapat memperbaiki isian surat langsung dari halaman cek status. Nomor referensi tetap sama dan Anda tidak perlu mengajukan surat baru.',
                'tautan' => ['label' => 'Buka cek status', 'route' => 'cek-status.form'],
              ],
              [
                'judul' => 'Ambil surat di kantor desa',
                'isi' => 'Setelah status "Siap Diambil", datang ke kantor desa pada jam pelayanan '.(($profil->jam_layanan ?? '') ?: 'Senin–Kamis 08.00–15.00, Jumat 08.00–11.30').' dengan membawa identitas asli.',
                'tautan' => ['label' => 'Lihat kontak & alamat', 'route' => 'kontak'],
              ],
            ];
          @endphp

          <div class="form-card">
            <div class="form-card-head">
              <span class="form-step-num sq">1</span>
              <div>
                <div style="font-weight:700;font-size:1.0625rem">Alur pengajuan surat</div>
                <div class="sub">Tujuh langkah dari memilih layanan sampai mengambil surat.</div>
              </div>
            </div>
            <hr class="form-section-hr">
            <div class="timeline">
              @foreach ($langkah as $i => $l)
                <div class="timeline-item">
                  <span class="timeline-dot green" aria-hidden="true"></span>
                  <div class="between">
                    <b>Langkah {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }} · {{ $l['judul'] }}</b>
                  </div>
                  <p class="small muted mt-8">{{ $l['isi'] }}</p>
                  @if ($l['tautan'])
                    <a href="{{ route($l['tautan']['route']) }}" class="panel-link" style="margin-top:8px">{{ $l['tautan']['label'] }} →</a>
                  @endif
                </div>
              @endforeach
            </div>
          </div>

          <div class="form-card">
            <div class="form-card-head">
              <span class="form-step-num sq">2</span>
              <div>
                <div style="font-weight:700;font-size:1.0625rem">Arti status pengajuan</div>
                <div class="sub">Status ditampilkan pada halaman cek status.</div>
              </div>
            </div>
            <hr class="form-section-hr">
            <div class="kv-row"><span class="k"><span class="badge badge-amber">Menunggu Verifikasi</span></span><span class="v">Pengajuan masuk dan belum diperiksa admin.</span></div>
            <div class="kv-row"><span class="k"><span class="badge badge-blue">Diproses</span></span><span class="v">Admin sedang memeriksa dan menyiapkan surat.</span></div>
            <div class="kv-row"><span class="k"><span class="badge badge-red">Perlu Perbaikan</span></span><span class="v">Ada isian yang harus Anda perbaiki. Buka cek status untuk memperbaiki.</span></div>
            <div class="kv-row"><span class="k"><span class="badge badge-green">Siap Diambil</span></span><span class="v">Surat sudah ditandatangani dan dapat diambil di kantor desa.</span></div>
            <div class="kv-row"><span class="k"><span class="badge badge-gray">Selesai</span></span><span class="v">Surat telah diambil. Pengajuan ditutup.</span></div>
          </div>

          <div class="form-card">
            <div class="form-card-head">
              <span class="form-step-num sq">3</span>
              <div>
                <div style="font-weight:700;font-size:1.0625rem">Pertanyaan yang sering muncul</div>
                <div class="sub">Hal yang paling sering ditanyakan warga.</div>
              </div>
            </div>
            <hr class="form-section-hr">
            <details class="privacy-details">
              <summary>Apakah saya perlu NIK atau unggah KTP?<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg></summary>
              <div class="privacy-body"><p>Perlu. Surat resmi desa mencetak NIK serta tempat/tanggal lahir, jadi formulir meminta data tersebut. Berkas KTP/KK hanya diunggah bila jenis surat mensyaratkannya.</p></div>
            </details>
            <details class="privacy-details">
              <summary>Bagaimana kalau saya salah mengisi?<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg></summary>
              <div class="privacy-body"><p>Bila admin menandai pengajuan <b>Perlu Perbaikan</b>, buka halaman <a href="{{ route('cek-status.form') }}">cek status</a> dengan nomor referensi dan kode akses Anda, lalu klik <b>Perbaiki pengajuan</b>. Nomor referensi tetap sama.</p></div>
            </details>
            <details class="privacy-details">
              <summary>Apakah nomor referensi dan kode akses saya aman?<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg></summary>
              <div class="privacy-body"><p><b>Kode akses</b> bersifat rahasia seperti kata sandi dan hanya diketahui Anda serta petugas desa. Nomor referensi dipakai untuk komunikasi dengan petugas. Halaman cek status hanya menampilkan status dan catatan admin, bukan NIK, alamat, atau berkas Anda. Jangan bagikan kode akses kepada orang lain.</p></div>
            </details>
            <details class="privacy-details">
              <summary>Saya lupa atau kehilangan kode akses, bagaimana?<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg></summary>
              <div class="privacy-body"><p>Buka <a href="{{ route('cek-status.lupa.form') }}">lupa kode akses</a>, lalu masukkan nomor referensi dan tanggal lahir pemohon. Bila cocok, nomor referensi dan kode akses ditampilkan kembali. Tidak perlu membuat pengajuan baru.</p></div>
            </details>
            <details class="privacy-details">
              <summary>Apakah ada biaya?<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg></summary>
              <div class="privacy-body"><p>Semua layanan surat desa <b>tidak dipungut biaya</b>. Bila ada pihak yang meminta uang atas nama desa, laporkan ke kantor desa.</p></div>
            </details>
          </div>
        </div>

        <aside>
          <div class="panel">
            <div class="label-cap">Pintasan</div>
            <a href="{{ route('pelayanan') }}" class="panel-link">Daftar layanan →</a>
            <a href="{{ route('pengajuan.create') }}" class="panel-link">Ajukan surat →</a>
            <a href="{{ route('cek-status.form') }}" class="panel-link">Cek status surat →</a>
            <a href="{{ route('kontak') }}" class="panel-link">Kontak kantor desa →</a>
          </div>
          <div class="panel panel-flat">
            <div style="font-weight:700;margin-bottom:8px">Jam pelayanan</div>
            <p style="font-size:.9rem;color:var(--gray-600);margin-bottom:14px">{!! nl2br(\App\Support\TeksTampil::e($profil->jam_layanan ?: 'Senin–Kamis 08.00–15.00, Jumat 08.00–11.30')) !!}</p>
            <a href="{{ route('kontak') }}" class="panel-link">Alamat kantor desa →</a>
          </div>
        </aside>
      </div>
    </div>
  </section>

@endsection
