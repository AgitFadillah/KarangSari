@extends('layouts.public')

@section('title', 'Lupa Kode Akses · '.$profil->nama_desa)
@section('description', 'Pulihkan kode akses pengajuan surat desa dengan nomor referensi dan tanggal lahir pemohon.')

@section('content')

  <section class="page-hero page-hero-soft">
    <div class="container">
      <nav class="breadcrumb">
        <a href="{{ route('beranda') }}">Beranda</a>
        <span class="sep">/</span>
        <a href="{{ route('cek-status.form') }}">Cek status</a>
        <span class="sep">/</span>
        <span>Lupa kode akses</span>
      </nav>
      <div class="page-hero-row">
        <div>
          <div class="eyebrow eyebrow-plain" style="color:var(--blue-600)">Pemulihan akses</div>
          <h1>Lupa kode akses?</h1>
          <p class="lead">Masukkan nomor referensi dan tanggal lahir pemohon. Kami tampilkan kembali nomor referensi dan kode akses Anda &mdash; tidak perlu membuat pengajuan baru.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="section form-page">
    <div class="container">
      <form method="POST" action="{{ route('cek-status.lupa') }}" class="form-layout" style="grid-template-columns:1fr">
        @csrf
        <div>
          @if ($errors->any())
            <div class="form-alert show" role="alert">
              <ul style="margin:0 0 0 18px">
                @foreach ($errors->all() as $pesan)
                  <li>{{ $pesan }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <div class="form-card">
            <div class="form-card-head">
              <span class="form-step-num sq">1</span>
              <div>
                <div style="font-weight:700;font-size:1.0625rem">Buktikan kepemilikan pengajuan</div>
                <div class="sub">Tanggal lahir yang Anda isi saat mengajukan.</div>
              </div>
            </div>
            <hr class="form-section-hr">
            <div class="form-grid">
              <div class="field span-2">
                <label for="nomor_referensi">Nomor referensi <span class="req">*</span></label>
                <input type="text" id="nomor_referensi" name="nomor_referensi"
                       value="{{ old('nomor_referensi', session('nomor_referensi_terakhir')) }}"
                       placeholder="{{ config('desa.prefix_referensi') }}-{{ now()->year }}-0001"
                       required autocomplete="off" spellcheck="false"
                       style="text-transform:uppercase"
                       @class(['invalid' => $errors->has('nomor_referensi')])>
              </div>
              <div class="field span-2">
                <label for="tanggal_lahir">Tanggal lahir pemohon <span class="req">*</span></label>
                <input type="date" id="tanggal_lahir" name="tanggal_lahir"
                       value="{{ old('tanggal_lahir') }}"
                       required autocomplete="off"
                       @class(['invalid' => $errors->has('tanggal_lahir')])>
                <div class="field-help">Demi keamanan, kode akses hanya ditampilkan bila nomor referensi dan tanggal lahir cocok. Data lain seperti NIK dan alamat tetap tidak ditampilkan.</div>
              </div>
            </div>
            <div class="form-actions" style="margin-top:20px">
              <a href="{{ route('cek-status.form') }}" class="back-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                Kembali ke cek status
              </a>
              <button type="submit" class="btn btn-primary">
                Tampilkan kode akses
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </section>

@endsection