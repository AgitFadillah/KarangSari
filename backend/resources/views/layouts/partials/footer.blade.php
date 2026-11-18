<!-- Footer -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <div class="brand">
            <div class="brand-mark @if ($profil->logoUrl()) brand-mark-logo @endif">
              @if ($profil->logoUrl())
                <img src="{{ $profil->logoUrl() }}" alt="Logo {{ $profil->nama_desa }}" width="44" height="44" loading="lazy">
              @else
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M4 18h16M6 18v-8h3v8M11 18v-8h3v8M16 18v-8h3v8M12 3l9 5H3l9-5z"/></svg>
              @endif
            </div>
            <div>
              <div class="brand-name">{{ $profil->nama_desa }}</div>
              <div class="brand-sub">Kecamatan {{ $profil->kecamatan }} · {{ $profil->kabupaten }}</div>
            </div>
          </div>
          <p>Layanan digital resmi Pemerintah {{ $profil->nama_desa }}. Ajukan surat keterangan tanpa akun dan pantau prosesnya secara daring.</p>
          <div class="footer-badges">
            <span class="badge badge-dark">Layanan Digital Desa</span>
            <span class="badge badge-dark">Data Terupdate {{ date('Y') }}</span>
          </div>
        </div>
        <div class="footer-col">
          <h4>Layanan</h4>
          <ul>
            @foreach ($layananFooter as $layanan)
              <li><a href="{{ route('layanan.detail', $layanan->slug) }}">{{ $layanan->nama }}</a></li>
            @endforeach
            <li><a href="{{ route('pelayanan') }}#katalog">Katalog Semua Surat</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Informasi</h4>
          <ul>
            <li><a href="{{ route('informasi') }}">Berita &amp; Agenda</a></li>
            <li><a href="{{ route('informasi') }}#pengumuman">Pengumuman</a></li>
            <li><a href="{{ route('potensi') }}">Potensi Desa</a></li>
            <li><a href="{{ route('profil') }}#struktur">Perangkat Desa</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Kontak</h4>
          {{-- Butir kontak yang kosong tidak dirender, agar footer tidak
               menampilkan ikon dengan teks kosong di sebelahnya. Bila keempat
               kontak belum diisi, seluruh daftar disembunyikan. --}}
          @if (filled($profil->alamat_kantor) || filled($profil->telepon) || filled($profil->email) || filled($profil->jam_layanan))
          <ul class="footer-contact">
            @if (filled($profil->alamat_kantor))
              <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.1-7-11a7 7 0 0 1 14 0c0 4.9-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                <span>{{ $profil->alamat_kantor }}</span>
              </li>
            @endif
            @if (filled($profil->telepon))
              <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.9.6 2.9.7A2 2 0 0 1 22 16.9z"/></svg>
                <span>{{ $profil->telepon }}</span>
              </li>
            @endif
            @if (filled($profil->email))
              <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><path d="M22 6l-10 7L2 6"/></svg>
                <span>{{ $profil->email }}</span>
              </li>
            @endif
            @if (filled($profil->jam_layanan))
              <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                <span>{{ $profil->jam_layanan }}</span>
              </li>
            @endif
          </ul>
          @else
            <p class="muted small">Kontak desa belum diisi.</p>
          @endif
        </div>
      </div>
      <div class="footer-bottom">
        <span>© {{ date('Y') }} Pemerintah {{ $profil->nama_desa }}. Hak cipta dilindungi.</span>
        <div class="footer-bottom-links">
          <a href="{{ route('informasi') }}">Arsip</a>
          <a href="{{ route('panduan') }}">Panduan</a>
          <a href="{{ route('kontak') }}">Kontak</a>
          <a href="{{ route('admin.login') }}">Admin</a>
        </div>
      </div>
    </div>
  </footer>