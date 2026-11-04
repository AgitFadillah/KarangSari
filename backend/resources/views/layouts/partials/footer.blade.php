<!-- Footer -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <div class="brand">
            <div class="brand-mark">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M4 18h16M6 18v-8h3v8M11 18v-8h3v8M16 18v-8h3v8M12 3l9 5H3l9-5z"/></svg>
            </div>
            <div>
              <div class="brand-name">{{ $profil->nama_desa }}</div>
              <div class="brand-sub">Kecamatan {{ $profil->kecamatan }} · {{ $profil->kabupaten }}</div>
            </div>
          </div>
          <p>Layanan digital resmi Pemerintah {{ $profil->nama_desa }}. Buktikan cinta pada tanah sendiri dengan menghadiri musyawarah desa secara rutin.</p>
          <div class="footer-badges">
            <span class="badge badge-dark">Terhubung · Versi 2.1</span>
            <span class="badge badge-dark">Data Terupdate {{ date('Y') }}</span>
          </div>
        </div>
        <div class="footer-col">
          <h4>Layanan</h4>
          <ul>
            <li><a href="{{ route('layanan.detail', 'domisili') }}">Keterangan Domisili</a></li>
            <li><a href="{{ route('layanan.detail', 'usaha') }}">Keterangan Usaha</a></li>
            <li><a href="{{ route('layanan.detail', 'tidak-mampu') }}">Keterangan Tidak Mampu</a></li>
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
          <ul class="footer-contact">
            <li>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.1-7-11a7 7 0 0 1 14 0c0 4.9-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
              <span>{{ $profil->alamat_kantor }}</span>
            </li>
            <li>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.9.6 2.9.7A2 2 0 0 1 22 16.9z"/></svg>
              <span>{{ $profil->telepon }}</span>
            </li>
            <li>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><path d="M22 6l-10 7L2 6"/></svg>
              <span>{{ $profil->email }}</span>
            </li>
            <li>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
              <span>{{ $profil->jam_layanan }}</span>
            </li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <span>© {{ date('Y') }} Pemerintah {{ $profil->nama_desa }}. Hak cipta dilindungi.</span>
        <div class="footer-bottom-links">
          <a href="{{ route('informasi') }}">Arsip</a>
          <a href="{{ route('kontak') }}">Kontak</a>
          <a href="{{ route('admin.login') }}">Admin</a>
        </div>
      </div>
    </div>
  </footer>