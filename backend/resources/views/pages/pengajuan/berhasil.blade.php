@extends('layouts.public')

@section('title', 'Pengajuan Diterima · '.$profil->nama_desa)
@section('description', 'Konfirmasi pengajuan surat desa diterima, lengkap dengan nomor referensi dan langkah selanjutnya.')

@section('content')
{{-- topbar + header dari partial --}}

  <!-- Konfirmasi -->
  <main class="success-wrap">
    <div class="container">
      <div class="confirm-hero">
        <div class="confirm-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
        </div>
        <span class="success-eyebrow">Pengajuan diterima</span>
        <h1>Terima kasih, pengajuan Anda sudah masuk.</h1>
        <p class="lead">Admin desa akan memeriksa data dan dokumen Anda. Simpan nomor referensi berikut untuk keperluan komunikasi dengan petugas.</p>
      </div>

      <div class="confirm-card">
        <div class="ref-box">
          <div class="label">Nomor referensi</div>
          <div class="number" id="refNumber">{{ $pengajuan->nomor_referensi }}</div>
          <button type="button" class="btn btn-outline btn-sm" data-copy="#refNumber">Salin nomor referensi</button>
        </div>
        <div class="kv-row"><span class="k">Jenis layanan</span><span class="v">{{ $pengajuan->jenisSurat->nama }}</span></div>
        <div class="kv-row"><span class="k">Nama pemohon</span><span class="v">{{ $pengajuan->nama }}</span></div>
        <div class="kv-row"><span class="k">WhatsApp</span><span class="v">{{ $pengajuan->whatsapp }}</span></div>
        <div class="kv-row"><span class="k">Status awal</span><span class="v"><span class="badge {{ $pengajuan->status->badge() }}">{{ $pengajuan->status->label() }}</span></span></div>
        <div class="notice">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
          <span>Website ini tidak menyediakan pelacakan status untuk publik. Admin akan menghubungi Anda melalui WhatsApp ketika ada perbaikan yang diperlukan atau ketika surat sudah siap diambil.</span>
        </div>
      </div>

      <div class="next-block">
        <div class="next-label">Apa yang terjadi selanjutnya?</div>
        <div class="next-grid">
          <div class="next-card">
            <div class="next-num">1</div>
            <h3>Pemeriksaan admin</h3>
            <p>Data dan dokumen diperiksa, lalu preview surat disiapkan.</p>
          </div>
          <div class="next-card">
            <div class="next-num">2</div>
            <h3>Pencetakan surat</h3>
            <p>Surat dicetak dan ditandatangani basah oleh pejabat berwenang.</p>
          </div>
          <div class="next-card">
            <div class="next-num">3</div>
            <h3>Diambil di kantor desa</h3>
            <p>Anda dihubungi via WhatsApp, lalu datang membawa identitas.</p>
          </div>
        </div>
        <div class="success-actions">
          <a href="{{ route('pelayanan') }}#katalog" class="btn btn-primary">Ajukan surat lain</a>
          <a href="{{ route('beranda') }}" class="btn btn-outline">Kembali ke beranda</a>
        </div>
        <p class="success-fine">Simpan nomor referensi ini. Petugas dapat menggunakannya untuk mencari pengajuan Anda.</p>
      </div>
    </div>
  </main>

  {{-- footer dari partial --}}
@endsection
