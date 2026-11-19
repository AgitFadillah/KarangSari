@extends('layouts.public')

@section('title', 'Potensi Desa · '.$profil->nama_desa)
@section('description', 'Pertanian, wisata alam, dan UMKM warga '.$profil->nama_desa.' yang dikelola warga dan kelompok desa.')

@push('styles')
<style>
/* Pills filter */
    .potential-pills {
      display: flex; gap: 10px; flex-wrap: wrap; margin: 40px 0 32px;
    }
    .potential-pill {
      padding: 10px 22px; border-radius: 999px; border: 1.5px solid var(--gray-300);
      background: var(--white); color: var(--navy-900); font-size: .875rem; font-weight: 600;
      font-family: inherit; cursor: pointer; transition: all .18s ease;
    }
    .potential-pill:hover { border-color: var(--navy-900); }
    .potential-pill.active {
      background: var(--navy-900); color: var(--white); border-color: var(--navy-900);
    }

    /* Grid kartu */
    .potential-grid {
      display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;
    }
    @media (max-width: 960px) { .potential-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 640px) { .potential-grid { grid-template-columns: 1fr; } }

    .potential-card {
      background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius-lg);
      overflow: hidden; transition: box-shadow .2s ease, transform .2s ease, opacity .3s ease;
    }
    .potential-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-3px); }
    .potential-card.hidden-card { display: none; }

    .potential-thumb {
      position: relative; height: 190px;
      background-image: repeating-linear-gradient(135deg, rgba(255,255,255,.045) 0 2px, transparent 2px 14px);
    }
    .potential-thumb::after {
      content: ''; position: absolute; right: -36px; bottom: -40px; width: 150px; height: 150px;
      border-radius: 50%; border: 26px solid rgba(255,255,255,.07);
    }
    .potential-thumb.thumb-dark { background-color: var(--navy-900); }
    .potential-thumb.thumb-light { background-color: var(--blue-100); }
    .potential-thumb.thumb-mint { background-color: #DDF0E4; }
    .potential-thumb.thumb-light::after { border-color: rgba(15,43,70,.06); }
    .potential-thumb.thumb-mint::after { border-color: rgba(23,138,75,.07); }

    .thumb-leaf {
      position: absolute; right: 22px; bottom: 18px; width: 42px; height: 42px; opacity: .18;
    }
    .thumb-light .thumb-leaf, .thumb-mint .thumb-leaf { opacity: .28; }
    .thumb-leaf svg { width: 100%; height: 100%; }
    .thumb-dark .thumb-leaf { color: var(--white); }
    .thumb-light .thumb-leaf { color: var(--navy-900); }
    .thumb-mint .thumb-leaf { color: var(--green-700); }

    .potential-badge {
      position: absolute; top: 14px; left: 14px; z-index: 2;
      padding: 6px 14px; border-radius: 999px; font-size: .75rem; font-weight: 700;
    }
    .badge-solid { background: rgba(15,43,70,.92); color: var(--white); }
    .badge-outline { background: var(--white); color: var(--navy-900); }

    .potential-body { padding: 22px 24px 24px; }
    .potential-body h3 { font-size: 1.0625rem; font-weight: 700; color: var(--navy-900); margin-bottom: 8px; }
    .potential-body p { font-size: .875rem; color: var(--gray-600); line-height: 1.6; margin-bottom: 18px; }
    .potential-manager-label {
      font-size: .625rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase;
      color: var(--gray-500); margin-bottom: 4px;
    }
    .potential-manager { font-size: .875rem; font-weight: 700; color: var(--navy-900); }

    .potential-empty {
      display: none; text-align: center; padding: 56px 20px; color: var(--gray-500);
      border: 1.5px dashed var(--gray-300); border-radius: var(--radius-lg);
    }
    .potential-empty.show { display: block; }

    /* CTA bawah */
    .cta-split {
      display: grid; grid-template-columns: 1.1fr 1fr; gap: 48px; align-items: center;
    }
    @media (max-width: 860px) { .cta-split { grid-template-columns: 1fr; gap: 28px; } }
    .cta-checklist { list-style: none; margin: 16px 0 24px; display: flex; flex-direction: column; gap: 12px; }
    .cta-checklist li { display: flex; gap: 12px; font-size: .9375rem; color: var(--gray-900); align-items: flex-start; }
    .cta-checklist li::before {
      content: ''; flex: none; width: 20px; height: 20px; margin-top: 1px; border-radius: 50%;
      background: var(--blue-100) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232563EB' stroke-width='3.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 13l4 4L19 7'/%3E%3C/svg%3E") center/11px no-repeat;
    }
    .cta-actions { display: flex; gap: 12px; flex-wrap: wrap; }
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
        <span>Potensi Desa</span>
      </nav>
      <div class="page-hero-row">
        <div>
          <div class="eyebrow">Potensi Desa</div>
          <h1>Yang tumbuh, dibuat, dan hidup di {{ $profil->nama_desa }}.</h1>
        </div>
        <p class="page-hero-note">Potensi di bawah dikelola warga dan kelompok desa. Data kontak dikelola oleh perangkat desa.</p>
      </div>
    </div>
  </section>

  <!-- Grid + Filter -->
  <section class="section" style="padding-top:8px">
    <div class="container">

      @php
        $pillKategori = $potensis->pluck('kategori')
            ->filter()
            ->unique()
            ->values();
      @endphp
      {{-- Filter hanya berguna bila ada kartu yang bisa disaring. Saat data
           potensi masih kosong, deretan pil tidak dirender agar tidak
           meninggalkan pil kosong di bawah judul halaman. --}}
      @if ($pillKategori->isNotEmpty())
        <div class="potential-pills" id="potentialPills" role="tablist" aria-label="Filter kategori potensi">
          <button class="potential-pill active" data-filter="semua">Semua</button>
          @foreach ($pillKategori as $kategori)
            <button class="potential-pill" data-filter="{{ \Illuminate\Support\Str::slug($kategori) }}">{{ $kategori }}</button>
          @endforeach
        </div>
      @endif

      <div class="potential-grid" id="potentialGrid">
        @foreach ($potensis as $potensi)
          @php
            $kategori = \Illuminate\Support\Str::slug((string) $potensi->kategori) ?: 'lainnya';
            $gaya = ['thumb-dark', 'thumb-light', 'thumb-mint'][$loop->index % 3];
          @endphp
          <article class="potential-card" data-category="{{ $kategori }}">
            <div class="potential-thumb {{ $gaya }}">
              <span class="potential-badge {{ $gaya === 'thumb-dark' ? 'badge-solid' : 'badge-outline' }}">{{ $potensi->kategori ?: 'Potensi' }}</span>
              <span class="thumb-leaf"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 22V10"/><path d="M12 10C12 5.5 8.5 2 3.5 2 3.5 6.5 7 10 12 10z"/><path d="M12 13c0-4 3-7 7.5-7C19.5 10 16.5 13 12 13z"/></svg></span>
            </div>
            <div class="potential-body">
              <h3>{{ $potensi->nama }}</h3>
              <p>{{ $potensi->deskripsi }}</p>
              @if ($potensi->kontak)
                <div class="potential-manager-label">Pengelola</div>
                <div class="potential-manager">{{ $potensi->kontak }}</div>
              @endif
            </div>
          </article>
        @endforeach
      </div>

      {{-- Dua keadaan kosong dibedakan: belum ada data sama sekali (langsung
           tampil) versus penyaringan kategori yang tidak menemukan kartu. --}}
      <p class="potential-empty @if ($potensis->isEmpty()) show @endif" id="potentialEmpty">
        @if ($potensis->isEmpty())
          Belum ada potensi desa yang terdata. Sampaikan usulan Anda ke kantor desa.
        @else
          Belum ada potensi pada kategori ini. Sampaikan usulan Anda ke kantor desa.
        @endif
      </p>

    </div>
  </section>

  <!-- CTA -->
  <section class="section section-tint">
    <div class="container">
      <div class="cta-split">
        <div>
          <div class="eyebrow">Dukung Usaha Warga</div>
          <h2>Punya usaha atau potensi yang belum tercatat?</h2>
          <p style="color:var(--gray-600);margin-top:16px;max-width:52ch">Sampaikan informasi usaha, kelompok tani, atau destinasi wisata Anda ke kantor desa agar dapat masuk ke pendataan potensi.</p>
        </div>
        <div class="card">
          <p style="font-weight:600;color:var(--navy-900)">Bawa informasi berikut saat menghubungi kantor desa:</p>
          <ul class="cta-checklist">
            <li>Nama usaha atau kegiatan</li>
            <li>Lokasi dan dusun</li>
            <li>Nama dan nomor WhatsApp pengelola</li>
            <li>Foto atau deskripsi singkat</li>
          </ul>
          <div class="cta-actions">
            <a href="{{ route('kontak') }}" class="btn btn-primary">Hubungi kantor desa</a>
            @if ($profil->nomorWhatsapp())
              <a href="https://wa.me/{{ $profil->nomorWhatsapp() }}" class="btn btn-outline" target="_blank" rel="noopener">WhatsApp</a>
            @endif
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- footer dari partial --}}
  
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
      const pills = document.querySelectorAll('.potential-pill');
      const cards = document.querySelectorAll('.potential-card');
      const empty = document.getElementById('potentialEmpty');

      pills.forEach(pill => {
        pill.addEventListener('click', () => {
          pills.forEach(p => p.classList.remove('active'));
          pill.classList.add('active');
          const filter = pill.dataset.filter;
          let shown = 0;
          cards.forEach(card => {
            const match = filter === 'semua' || card.dataset.category === filter;
            card.classList.toggle('hidden-card', !match);
            if (match) shown++;
          });
          empty.classList.toggle('show', shown === 0);
        });
      });
    });
</script>
@endpush
