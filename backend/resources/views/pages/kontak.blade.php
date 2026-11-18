@extends('layouts.public')

@section('title', 'Kontak · '.$profil->nama_desa)
@section('description', 'Alamat kantor, WhatsApp, telepon, jam pelayanan, dan peta lokasi Kantor '.$profil->nama_desa.'.')

@push('styles')
<style>
/* Kartu info kontak */
    .contact-info-grid {
      display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;
    }
    @media (max-width: 900px) { .contact-info-grid { grid-template-columns: 1fr; } }

    .contact-info-card {
      background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius-lg);
      padding: 26px 26px 28px;
    }
    .contact-icon-box {
      width: 46px; height: 46px; border-radius: var(--radius-md);
      display: inline-flex; align-items: center; justify-content: center; margin-bottom: 18px;
    }
    .contact-icon-box svg { width: 22px; height: 22px; }
    .icon-blue { background: var(--blue-100); color: var(--blue-600); }
    .icon-green { background: #DDF0E4; color: var(--green-700, #178A4B); }

    .contact-info-card h3 {
      font-size: .75rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
      color: var(--gray-500); margin-bottom: 12px;
    }
    .contact-value { font-size: 1rem; font-weight: 700; color: var(--navy-900); line-height: 1.5; }
    .contact-value + .contact-value { font-size: .9375rem; font-weight: 600; color: var(--gray-700); }

    .contact-wa-link {
      display: inline-flex; align-items: center; gap: 6px; margin-top: 14px;
      font-size: .875rem; font-weight: 700; color: #178A4B; text-decoration: none;
    }
    .contact-wa-link:hover { text-decoration: underline; }
    .contact-wa-link svg { width: 14px; height: 14px; }

    .contact-hours { display: flex; flex-direction: column; gap: 8px; }
    .contact-hours-row {
      display: flex; justify-content: space-between; font-size: .9375rem;
      padding-bottom: 8px; border-bottom: 1px dashed var(--gray-200);
    }
    .contact-hours-row:last-child { border-bottom: none; padding-bottom: 0; }
    .contact-hours-row .day { color: var(--gray-600); font-weight: 500; }
    .contact-hours-row .time { font-weight: 700; color: var(--navy-900); }
    .contact-hours-row .closed { color: #DC2626; }

    /* Peta + FAQ */
    .map-faq-grid {
      display: grid; grid-template-columns: 1.15fr 1fr; gap: 32px; align-items: start;
    }
    @media (max-width: 900px) { .map-faq-grid { grid-template-columns: 1fr; } }

    .map-card {
      background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius-lg);
      overflow: hidden; box-shadow: var(--shadow-sm);
    }
    .map-canvas {
      position: relative; height: 400px; background: #DCE9F5;
    }
    .map-canvas iframe {
      display: block; width: 100%; height: 100%; border: 0;
    }
    .map-bar {
      display: flex; align-items: center; justify-content: space-between; gap: 16px;
      padding: 16px 20px; border-top: 1px solid var(--gray-200); flex-wrap: wrap;
    }
    .map-bar-text { min-width: 0; }
    .map-bar-text b { display: block; font-size: .9375rem; color: var(--navy-900); }
    .map-bar-text span { display: block; font-size: .8125rem; color: var(--gray-500); margin-top: 2px; }
    .map-open-btn {
      display: inline-flex; align-items: center; gap: 8px; flex: none;
      background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius-sm);
      padding: 9px 16px; font-size: .8125rem; font-weight: 700; color: var(--navy-900);
      text-decoration: none; box-shadow: var(--shadow-sm); transition: box-shadow .18s ease, border-color .18s ease;
    }
    .map-open-btn:hover { box-shadow: var(--shadow-md); border-color: var(--blue-300, #B9D3EA); text-decoration: none; }
    .map-open-btn svg { width: 14px; height: 14px; }
    @media (max-width: 560px) { .map-canvas { height: 300px; } }

    /* FAQ */
    .faq-list { display: flex; flex-direction: column; gap: 12px; margin-top: 28px; }
    .faq-item {
      background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius-md);
      overflow: hidden;
    }
    .faq-q {
      width: 100%; display: flex; justify-content: space-between; align-items: center; gap: 16px;
      padding: 18px 22px; background: none; border: none; cursor: pointer; text-align: left;
      font-family: inherit; font-size: .9375rem; font-weight: 700; color: var(--navy-900);
    }
    .faq-q svg { width: 18px; height: 18px; flex: none; color: var(--gray-500); transition: transform .2s ease; }
    .faq-item.open .faq-q svg { transform: rotate(180deg); }
    .faq-a {
      max-height: 0; overflow: hidden; transition: max-height .25s ease;
    }
    .faq-a-inner {
      padding: 0 22px 18px; font-size: .875rem; color: var(--gray-600); line-height: 1.7;
    }

    /* CTA strip */
    .cta-strip-card {
      background: var(--gray-100); border-radius: var(--radius-lg);
      padding: 40px 48px; display: flex; align-items: center; justify-content: space-between; gap: 32px;
      flex-wrap: wrap;
    }
    .cta-strip-card h2 { font-size: 1.5rem; margin-bottom: 6px; }
    .cta-strip-card p { color: var(--gray-600); font-size: .9375rem; }
</style>
@endpush

@section('content')
{{-- topbar + header dari partial --}}

  <!-- Page Hero -->
  <section class="page-hero">
    <div class="container">
      <nav class="breadcrumb">
        <a href="{{ route('beranda') }}">Beranda</a>
        <span class="sep">/</span>
        <span>Kontak</span>
      </nav>
      <div class="page-hero-row">
        <div>
          @php $kontenKontak = \App\Support\KontenHalaman::ambil($kontens, 'kontak'); @endphp
          <div class="eyebrow">Kontak &amp; Lokasi</div>
          <h1>{{ \App\Support\KontenHalaman::judul($kontenKontak) ?? 'Datang ke kantor atau hubungi.' }}</h1>
        </div>
        <p class="page-hero-note">{{ \App\Support\KontenHalaman::pengantar($kontenKontak, 300) ?? 'Untuk pertanyaan singkat, WhatsApp menjadi jalur tercepat. Untuk urusan dokumen, datang langsung pada jam pelayanan.' }}</p>
      </div>
    </div>
  </section>

  <!-- Kartu Info -->
  <section class="section" style="padding-top:8px;padding-bottom:48px">
    <div class="container">
      <div class="contact-info-grid">

        <div class="contact-info-card">
          <div class="contact-icon-box icon-blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.1-7-11a7 7 0 0 1 14 0c0 4.9-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
          </div>
          <h3>Alamat kantor</h3>
          <div class="contact-value">{{ $profil->alamat_kantor }}</div>
        </div>

        <div class="contact-info-card">
          <div class="contact-icon-box icon-green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.4 8.4 0 0 1-8.5 8.4 8.6 8.6 0 0 1-3.8-.9L3 21l2-5.4a8.3 8.3 0 0 1-1-4A8.4 8.4 0 0 1 12.5 3.2 8.4 8.4 0 0 1 21 11.5z"/></svg>
          </div>
          <h3>WhatsApp &amp; telepon</h3>
          @if (filled($profil->whatsapp))
            <div class="contact-value">{{ $profil->whatsapp }}</div>
          @endif
          @if (filled($profil->telepon))
            <div class="contact-value">{{ $profil->telepon }}</div>
          @endif
          @if ($profil->nomorWhatsapp())
            <a href="https://wa.me/{{ $profil->nomorWhatsapp() }}" target="_blank" rel="noopener" class="contact-wa-link">
              Buka WhatsApp
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
          @elseif (! filled($profil->telepon))
            <p class="muted small">Nomor kontak belum diisi oleh perangkat desa.</p>
          @endif
        </div>

        <div class="contact-info-card">
          <div class="contact-icon-box icon-blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
          </div>
          <h3>Jam pelayanan</h3>
          @if ($jadwalLayanan !== [])
            <div class="contact-hours">
              @foreach ($jadwalLayanan as $jadwal)
                <div class="contact-hours-row">
                  <span class="day">{{ $jadwal['hari'] }}</span>
                  <span @class(['time', 'closed' => $jadwal['tutup']])>{{ $jadwal['jam'] ?: '—' }}</span>
                </div>
              @endforeach
            </div>
          @else
            <p class="muted small">Jam pelayanan belum diisi.</p>
          @endif
        </div>

      </div>
    </div>
  </section>

  <!-- Peta + FAQ -->
  <section class="section section-tint">
    <div class="container">
      <div class="map-faq-grid">

        <div class="map-card">
          <div class="map-canvas">
            <iframe
              src="{{ $profil->embedPeta() }}"
              title="Peta lokasi Kantor {{ $profil->nama_desa }}"
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
              allowfullscreen></iframe>
          </div>
          <div class="map-bar">
            <div class="map-bar-text">
              <b>Kantor {{ $profil->nama_desa }}</b>
              <span>{{ $profil->alamat_kantor }}</span>
            </div>
            <a class="map-open-btn" href="{{ $profil->tautanPeta() }}" target="_blank" rel="noopener">
              Buka di Google Maps
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6M10 14L21 3"/></svg>
            </a>
          </div>
        </div>

        <div>
          <h2 style="font-size:1.5rem">Sebelum menghubungi</h2>
          <p style="color:var(--gray-600);margin-top:8px">Beberapa pertanyaan yang paling sering ditanyakan warga.</p>

          <div class="faq-list" id="faqList">
            <div class="faq-item">
              <button class="faq-q" aria-expanded="false">
                Apakah pengajuan surat dikenakan biaya?
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
              </button>
              <div class="faq-a">
                <div class="faq-a-inner">Tidak. Seluruh pelayanan administrasi surat di {{ $profil->nama_desa }} gratis dan tidak dipungut biaya apa pun. Laporkan ke kantor desa apabila ada pihak yang meminta biaya.</div>
              </div>
            </div>
            <div class="faq-item">
              <button class="faq-q" aria-expanded="false">
                Berapa lama surat selesai diproses?
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
              </button>
              <div class="faq-a">
                <div class="faq-a-inner">Rata-rata 1–3 hari kerja setelah berkas lengkap diterima. Surat keterangan sederhana biasanya selesai pada hari yang sama apabila diajukan sebelum pukul 12.00.</div>
              </div>
            </div>
            <div class="faq-item">
              <button class="faq-q" aria-expanded="false">
                Bisakah saya memantau status pengajuan?
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
              </button>
              <div class="faq-a">
                <div class="faq-a-inner">Bisa. Setiap pengajuan mendapatkan nomor registrasi. Petugas akan mengabari melalui WhatsApp saat status berubah atau saat surat siap diambil.</div>
              </div>
            </div>
            <div class="faq-item">
              <button class="faq-q" aria-expanded="false">
                Dokumen apa yang perlu dibawa saat mengambil surat?
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
              </button>
              <div class="faq-a">
                <div class="faq-a-inner">Bawa KTP asli dan sebutkan nomor registrasi pengajuan Anda. Apabila diwakilkan, sertakan surat kuasa bermaterai dan fotokopi KTP pemberi serta penerima kuasa.</div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="section">
    <div class="container">
      <div class="cta-strip-card">
        <div>
          <h2>Ingin mengajukan surat sekarang?</h2>
          <p>Pilih jenis surat dan isi formulir tanpa membuat akun.</p>
        </div>
        <a href="{{ route('pelayanan') }}" class="btn btn-primary">Buka layanan surat</a>
      </div>
    </div>
  </section>

  {{-- footer dari partial --}}
  
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('.faq-item').forEach(item => {
        const btn = item.querySelector('.faq-q');
        const ans = item.querySelector('.faq-a');
        btn.addEventListener('click', () => {
          const wasOpen = item.classList.contains('open');
          document.querySelectorAll('.faq-item.open').forEach(o => {
            o.classList.remove('open');
            o.querySelector('.faq-a').style.maxHeight = null;
            o.querySelector('.faq-q').setAttribute('aria-expanded', 'false');
          });
          if (!wasOpen) {
            item.classList.add('open');
            ans.style.maxHeight = ans.scrollHeight + 'px';
            btn.setAttribute('aria-expanded', 'true');
          }
        });
      });
    });
</script>
@endpush
