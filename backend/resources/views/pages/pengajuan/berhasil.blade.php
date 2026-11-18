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
        <p class="lead">Admin desa akan memeriksa data dan dokumen Anda. Cukup <b>salin kode akses</b> berikut &mdash; nomor referensi terisi otomatis saat Anda memantau status.</p>
      </div>

      <div class="confirm-card">
        <div class="ref-box">
          <div class="label">Nomor referensi</div>
          <div class="number" id="refNumber">{{ $pengajuan->nomor_referensi }}</div>
          <div class="field-help">Tidak perlu disalin &mdash; terisi otomatis di halaman cek status.</div>
        </div>
        <div class="ref-box" style="margin-top:12px">
          <div class="label">Kode akses (rahasia) &mdash; yang perlu disalin</div>
          <div class="number" id="kodeAkses">{{ $kodeAkses }}</div>
          <button type="button" class="btn btn-primary btn-sm" data-copy="#kodeAkses">Salin kode akses</button>
        </div>

        @php
          // Tombol ini membuka percakapan WhatsApp dengan nomor warga sendiri,
          // sehingga catatan pengajuan tersimpan di riwayat chat - tidak
          // bergantung pada kertas atau tangkapan layar yang mudah hilang.
          $pesanSimpan = "Catatan pengajuan surat saya\n"
            .'Nomor referensi: '.$pengajuan->nomor_referensi."\n"
            .'Kode akses: '.$kodeAkses."\n"
            .'Jenis layanan: '.$pengajuan->jenisSurat->nama."\n"
            .'Cek status: '.route('cek-status.form');
          $tautanSimpan = $pengajuan->tautanWhatsapp($pesanSimpan);
        @endphp

        @if ($tautanSimpan)
          <div class="form-actions" style="margin-top:16px;justify-content:flex-start">
            <a href="{{ $tautanSimpan }}" class="btn btn-primary" target="_blank" rel="noopener">
              <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.5 15.2L2 22l4.9-1.4A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-2.9.8.8-2.8-.2-.3A8 8 0 1 1 12 20zm4.4-5.7c-.2-.1-1.4-.7-1.6-.8-.2-.1-.4-.1-.5.1l-.7.9c-.1.2-.3.2-.5.1a6.5 6.5 0 0 1-3.2-2.8c-.1-.2 0-.4.1-.5l.5-.6c.1-.2.1-.3 0-.5l-.7-1.7c-.1-.3-.2-.3-.4-.3h-.4c-.2 0-.5.1-.7.4-.3.3-.9.9-.9 2.1 0 1.2.9 2.4 1 2.6.1.2 1.8 2.8 4.4 3.8 1.6.6 2.2.7 3 .6.5-.1 1.4-.6 1.6-1.2.2-.6.2-1.1.1-1.2-.1-.1-.2-.2-.4-.3z"/></svg>
              Simpan ke WhatsApp saya
            </a>
          </div>
          <p class="field-help" style="margin-top:8px">Membuka percakapan WhatsApp dengan nomor Anda sendiri. Tekan kirim di sana agar catatan tersimpan di riwayat chat.</p>
        @endif
        <div class="kv-row"><span class="k">Jenis layanan</span><span class="v">{{ $pengajuan->jenisSurat->nama }}</span></div>
        <div class="kv-row"><span class="k">Nama pemohon</span><span class="v">{{ $pengajuan->nama }}</span></div>
        <div class="kv-row"><span class="k">WhatsApp</span><span class="v">{{ $pengajuan->whatsapp }}</span></div>
        <div class="kv-row"><span class="k">Status awal</span><span class="v"><span class="badge {{ $pengajuan->status->badge() }}">{{ $pengajuan->status->label() }}</span></span></div>
        <div class="notice">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
          <span>Simpan <b>kode akses</b> ini di tempat yang aman (catatan WhatsApp, buku, atau tangkapan layar). Nomor referensi tidak perlu dihafal &mdash; ia terisi otomatis saat Anda membuka <a href="{{ route('cek-status.form') }}">cek status pengajuan</a>. Bila kode hilang, Anda dapat <a href="{{ route('cek-status.lupa.form') }}">memulihkannya dengan tanggal lahir</a>.</span>
        </div>
      </div>

      <div class="next-block">
        <div class="next-label">Apa yang terjadi selanjutnya?</div>
        <div class="next-grid">
          <div class="next-card">
            <div class="next-num">1</div>
            <h3>Pemeriksaan admin</h3>
            <p>Admin memeriksa data dan isian surat Anda. Bila ada yang kurang, status berubah menjadi &ldquo;Perlu Perbaikan&rdquo;.</p>
          </div>
          <div class="next-card">
            <div class="next-num">2</div>
            <h3>Pencetakan surat</h3>
            <p>Setelah disetujui, surat dicetak dan ditandatangani basah oleh pejabat berwenang.</p>
          </div>
          <div class="next-card">
            <div class="next-num">3</div>
            <h3>Diambil di kantor desa</h3>
            <p>Anda dihubungi via WhatsApp, lalu datang membawa identitas asli.</p>
          </div>
        </div>
        <div class="success-actions">
          <a href="{{ route('cek-status.saya', $pengajuan->nomor_referensi) }}" class="btn btn-primary">Pantau status surat</a>
          <a href="{{ route('pelayanan') }}#katalog" class="btn btn-outline">Ajukan surat lain</a>
        </div>
        <p class="success-fine">Cukup simpan kode aksesnya. Nomor referensi terisi otomatis saat Anda membuka halaman cek status dari perangkat ini.</p>
      </div>
    </div>
  </main>

  {{-- footer dari partial --}}
@endsection
