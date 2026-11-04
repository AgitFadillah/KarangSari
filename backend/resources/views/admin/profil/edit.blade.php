@extends('layouts.admin')

@section('title', 'Profil & Kontak · Dashboard Admin '.$profil->nama_desa)

@push('styles')
<style>
.preview-box {
      display: flex; gap: 14px; align-items: center;
      border: 1px solid var(--gray-200); border-radius: var(--radius-md);
      padding: 16px;
    }
    .preview-box .brand-mark { width: 46px; height: 46px; flex: none; }
    .preview-box b { display: block; font-size: .9375rem; color: var(--navy-900); line-height: 1.35; }
    .preview-box span { font-size: .8125rem; color: var(--gray-500); }
    .preview-box img { width: 46px; height: 46px; object-fit: contain; flex: none; }

    .pub-link {
      display: flex; justify-content: space-between; align-items: center; gap: 12px;
      border: 1px solid var(--gray-200); border-radius: var(--radius-sm);
      padding: 13px 16px; margin-bottom: 10px;
      font-size: .9375rem; font-weight: 600; color: var(--navy-900); text-decoration: none;
      transition: border-color .15s ease, background .15s ease;
    }
    .pub-link:last-child { margin-bottom: 0; }
    .pub-link:hover { border-color: var(--navy-900); background: var(--gray-100); text-decoration: none; }
    .pub-link svg { width: 16px; height: 16px; color: var(--gray-500); flex: none; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 720px) { .form-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('topbar')
@php $adminTrail = [['label' => 'Admin', 'route' => 'admin.ringkasan'], ['label' => 'Profil & Kontak', 'route' => null]]; @endphp
<x-admin-topbar :trail="$adminTrail" title="Profil & Kontak" />
@endsection

@section('content')
<main class="admin-content">
        <form method="POST" action="{{ route('admin.profil.update') }}" enctype="multipart/form-data">
          @csrf
          @method('PUT')

          <div class="admin-page-head">
            <div>
              <p class="sub">Informasi ini tampil pada halaman profil, kontak, dan footer website publik.</p>
            </div>
            <button type="submit" class="btn btn-primary">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
              Simpan perubahan
            </button>
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

          <div class="admin-grid-2">
            <div>
              <div class="panel">
                <div class="panel-head">
                  <div>
                    <h2>Identitas desa</h2>
                    <p>Nama dan wilayah administratif sesuai data resmi.</p>
                  </div>
                </div>
                <div class="field mb-16">
                  <label for="nama_desa">Nama desa <span class="req">*</span></label>
                  <input type="text" id="nama_desa" name="nama_desa" value="{{ old('nama_desa', $profil->nama_desa) }}" required>
                </div>
                <div class="form-grid">
                  <div class="field">
                    <label for="kecamatan">Kecamatan</label>
                    <input type="text" id="kecamatan" name="kecamatan" value="{{ old('kecamatan', $profil->kecamatan) }}">
                  </div>
                  <div class="field">
                    <label for="kabupaten">Kabupaten</label>
                    <input type="text" id="kabupaten" name="kabupaten" value="{{ old('kabupaten', $profil->kabupaten) }}">
                  </div>
                  <div class="field">
                    <label for="provinsi">Provinsi</label>
                    <input type="text" id="provinsi" name="provinsi" value="{{ old('provinsi', $profil->provinsi) }}">
                  </div>
                  <div class="field">
                    <label for="kode_pos">Kode pos</label>
                    <input type="text" id="kode_pos" name="kode_pos" value="{{ old('kode_pos', $profil->kode_pos) }}" inputmode="numeric">
                  </div>
                </div>
                <div class="field mt-16">
                  <label for="alamat_kantor">Alamat kantor</label>
                  <textarea id="alamat_kantor" name="alamat_kantor" rows="3">{{ old('alamat_kantor', $profil->alamat_kantor) }}</textarea>
                </div>
              </div>

              <div class="panel">
                <div class="panel-head">
                  <div>
                    <h2>Kontak &amp; pelayanan</h2>
                    <p>Nomor yang dapat dihubungi warga dan jam pelayanan kantor.</p>
                  </div>
                </div>
                <div class="form-grid">
                  <div class="field">
                    <label for="telepon">Telepon kantor</label>
                    <input type="tel" id="telepon" name="telepon" value="{{ old('telepon', $profil->telepon) }}">
                  </div>
                  <div class="field">
                    <label for="whatsapp">WhatsApp desa</label>
                    <input type="tel" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $profil->whatsapp) }}">
                    <p class="field-help">Format lokal, contoh 081234567890.</p>
                  </div>
                  <div class="field span-2">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $profil->email) }}">
                  </div>
                  <div class="field span-2">
                    <label for="jam_layanan">Jam pelayanan</label>
                    <input type="text" id="jam_layanan" name="jam_layanan" value="{{ old('jam_layanan', $profil->jam_layanan) }}">
                  </div>
                  <div class="field span-2">
                    <label for="jam_kunjungan">Jam kunjungan kantor</label>
                    <input type="text" id="jam_kunjungan" name="jam_kunjungan" value="{{ old('jam_kunjungan', $profil->jam_kunjungan) }}">
                    <p class="field-help">Rincian harian yang tampil pada kartu jam di halaman kontak.</p>
                  </div>
                  <div class="field span-2">
                    <label for="kueri_peta">Kueri lokasi peta</label>
                    <input type="text" id="kueri_peta" name="kueri_peta" value="{{ old('kueri_peta', $profil->kueri_peta) }}">
                    <p class="field-help">Digunakan untuk tombol "Buka di Google Maps" pada halaman kontak.</p>
                  </div>
                </div>
              </div>

              <div class="panel">
                <div class="panel-head">
                  <div>
                    <h2>Narasi profil</h2>
                    <p>Sejarah, visi, dan misi desa untuk halaman profil publik.</p>
                  </div>
                </div>
                <div class="field mb-16">
                  <label for="sejarah">Sejarah singkat</label>
                  <textarea id="sejarah" name="sejarah" rows="4">{{ old('sejarah', $profil->sejarah) }}</textarea>
                </div>
                <div class="field mb-16">
                  <label for="visi">Visi</label>
                  <textarea id="visi" name="visi" rows="3">{{ old('visi', $profil->visi) }}</textarea>
                </div>
                <div class="field">
                  <label for="misi">Misi (satu per baris)</label>
                  <textarea id="misi" name="misi" rows="5">{{ old('misi', $profil->misi) }}</textarea>
                </div>
              </div>
            </div>

            <div>
              <div class="panel">
                <div class="label-cap" style="margin-top:0">Pratinjau identitas</div>
                <div class="preview-box mb-16">
                  @if ($profil->logo_path)
                    <img src="{{ Storage::disk('public')->url($profil->logo_path) }}" alt="Logo {{ $profil->nama_desa }}">
                  @else
                    <div class="brand-mark" aria-hidden="true">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M4 18h16M6 18v-8h3v8M11 18v-8h3v8M16 18v-8h3v8M12 3l9 5H3l9-5z"/></svg>
                    </div>
                  @endif
                  <div>
                    <b>{{ $profil->nama_desa }}</b>
                    <span>Kecamatan {{ $profil->kecamatan }}, {{ $profil->kabupaten }}</span>
                  </div>
                </div>
                <p class="field-help">Lambang bawaan hanya penanda sementara sampai logo resmi diunggah.</p>
              </div>

              <div class="panel">
                <div class="panel-head">
                  <div>
                    <h2>Logo desa</h2>
                    <p>Unggah logo resmi berformat PNG, JPG, atau WEBP.</p>
                  </div>
                </div>
                <div class="dropzone" data-dropzone data-size-hint="klik untuk mengganti">
                  <input type="file" id="logo" name="logo" accept=".png,.jpg,.jpeg,.webp" hidden>
                  <div class="dz-default">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>
                    <div style="font-weight:600">Pilih file logo</div>
                    <div class="small muted" style="margin-top:4px">PNG, JPG, WEBP · maks. 1 MB</div>
                  </div>
                  <div class="dropzone-file" style="display:none">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                    <div class="dz-meta">
                      <div class="dz-name"></div>
                      <div class="dz-size"></div>
                    </div>
                    <button type="button" class="dz-remove">Hapus</button>
                  </div>
                </div>

                <div class="panel-head mt-24">
                  <div>
                    <h2>Foto utama beranda</h2>
                    <p>Gunakan foto lanskap desa beresolusi tinggi.</p>
                  </div>
                </div>
                <div class="dropzone" data-dropzone data-size-hint="klik untuk mengganti">
                  <input type="file" id="foto_beranda" name="foto_beranda" accept=".jpg,.jpeg,.png,.webp" hidden>
                  <div class="dz-default">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                    <div style="font-weight:600">Pilih foto beranda</div>
                    <div class="small muted" style="margin-top:4px">JPG, PNG, WEBP · maks. 5 MB</div>
                  </div>
                  <div class="dropzone-file" style="display:none">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                    <div class="dz-meta">
                      <div class="dz-name"></div>
                      <div class="dz-size"></div>
                    </div>
                    <button type="button" class="dz-remove">Hapus</button>
                  </div>
                </div>
                <p class="field-help mt-16">File gambar diunggah saat tombol simpan ditekan.</p>
              </div>

              <div class="panel">
                <div class="panel-head">
                  <div>
                    <h2>Tautan publik</h2>
                    <p>Periksa tampilan setelah menyimpan perubahan.</p>
                  </div>
                </div>
                <a href="{{ route('profil') }}" target="_blank" rel="noopener" class="pub-link">
                  Halaman profil
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6M10 14L21 3"/></svg>
                </a>
                <a href="{{ route('kontak') }}" target="_blank" rel="noopener" class="pub-link">
                  Halaman kontak
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6M10 14L21 3"/></svg>
                </a>
              </div>
            </div>
          </div>
        </form>
      </main>
@endsection
