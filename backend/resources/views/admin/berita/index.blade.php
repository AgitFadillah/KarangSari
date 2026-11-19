@extends('layouts.admin')

@section('title', 'Berita & Agenda · Dashboard Admin '.$profil->nama_desa)

@push('styles')
<style>
.berita-table { min-width: 820px; }

/* Gambar kecil di kolom judul: penanda cepat bahwa entri sudah punya foto. */
.cell-foto {
  width: 44px; height: 34px; object-fit: cover; border-radius: 5px;
  vertical-align: middle; margin-right: 9px; background: var(--gray-100);
}

    /* Ringkasan jumlah per kategori di atas tabel. */
    .sum-kartu {
      display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 14px; margin-bottom: 22px;
    }
    @media (max-width: 780px) { .sum-kartu { grid-template-columns: 1fr 1fr; } }
    .sum-item {
      background: var(--white); border: 1px solid #D8E2EC;
      border-radius: var(--radius-md); padding: 18px 20px;
      text-decoration: none; color: inherit;
      transition: border-color .16s, box-shadow .16s;
      display: block;
    }
    .sum-item:hover {
      border-color: var(--blue-500);
      box-shadow: 0 6px 20px -10px rgba(46,109,164,.4);
      text-decoration: none;
    }
    .sum-item.aktif { border-color: var(--navy-900); border-width: 1.5px; }
    .sum-label {
      font-size: .6875rem; font-weight: 700; letter-spacing: .1em;
      text-transform: uppercase; color: var(--gray-500);
    }
    .sum-angka {
      font-size: 1.6rem; font-weight: 800; color: var(--navy-900);
      line-height: 1.15; margin-top: 4px;
    }
    .cell-preview { max-width: 520px; }
</style>
@endpush

@section('topbar')
@php $adminTrail = [['label' => 'Admin', 'route' => 'admin.ringkasan'], ['label' => 'Berita & Agenda', 'route' => null]]; @endphp
<x-admin-topbar :trail="$adminTrail" title="Berita & Agenda" />
@endsection

@section('content')
<main class="admin-content">
        <div class="admin-page-head">
          <div>
            <p class="sub">Berita, pengumuman, dan agenda yang tampil di beranda serta halaman Informasi publik.</p>
          </div>
          <a href="{{ route('admin.berita.create') }}" class="btn btn-primary btn-sm">+ Tulis berita</a>
        </div>

        @if (session('status'))
          <div class="notice notice-green" role="status" style="margin-bottom:22px">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
            <span>{{ session('status') }}</span>
          </div>
        @endif

        {{--
          Ringkasan + filter kategori. Halaman publik Informasi punya blok
          "Arsip berita & agenda" dan "Pengumuman"; kartu di bawah membuat
          perangkat desa dapat melihat dan menyaring ketiganya di satu tempat.
        --}}
        <div class="sum-kartu">
          <a href="{{ route('admin.berita.index') }}"
             @class(['sum-item', 'aktif' => ! $kategoriAktif])>
            <div class="sum-label">Semua</div>
            <div class="sum-angka">{{ $jumlah['semua'] }}</div>
          </a>
          @foreach (['berita', 'pengumuman', 'agenda'] as $kat)
            <a href="{{ route('admin.berita.index', ['kategori' => $kat]) }}"
               @class(['sum-item', 'aktif' => $kategoriAktif === $kat])>
              <div class="sum-label">{{ ucfirst($kat) }}</div>
              <div class="sum-angka">{{ $jumlah[$kat] }}</div>
            </a>
          @endforeach
        </div>

        <div class="panel">
          <div class="panel-head">
            <div>
              <h2>{{ $kategoriAktif ? ucfirst($kategoriAktif) : 'Semua entri' }}</h2>
              <p>Entri berstatus <b>draf</b> tidak tampil di situs publik.</p>
            </div>
            <span class="badge badge-gray">{{ $beritas->count() }} entri</span>
          </div>

          @if ($kategoriAktif)
            <p class="field-help" style="margin-bottom:14px">
              Menyaring kategori <b>{{ ucfirst($kategoriAktif) }}</b>.
              <a href="{{ route('admin.berita.index') }}">Tampilkan semua</a>
            </p>
          @endif

          <div class="table-wrap">
            <table class="data berita-table">
              <thead>
                <tr>
                  <th>Judul</th>
                  <th>Kategori</th>
                  <th>Terbit</th>
                  <th>Status</th>
                  <th aria-label="Aksi"></th>
                </tr>
              </thead>
              <tbody>
                @forelse ($beritas as $berita)
                  <tr>
                    <td class="cell-preview">
                      @php $fotoAdmin = $berita->fotoUrl(); @endphp
                      <div class="cell-main">
                        @if ($fotoAdmin)
                          <img class="cell-foto" src="{{ $fotoAdmin }}" alt="" width="44" height="34" loading="lazy">
                        @endif
                        {{ $berita->judul }}
                        @if ($berita->is_highlight)
                          <span class="badge badge-amber" title="Tampil sebagai banner di halaman Informasi">Sorotan</span>
                        @endif
                      </div>
                      <div class="cell-sub">{{ \Illuminate\Support\Str::limit($berita->ringkasan ?: $berita->slug, 80) }}</div>
                    </td>
                    <td><span class="badge badge-gray">{{ $berita->labelKategori() }}</span></td>
                    <td>{{ $berita->terbit_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                    <td><span class="badge {{ $berita->badgeStatus() }}">{{ $berita->labelStatus() }}</span></td>
                    <td class="right action-cell">
                      @if ($berita->isTerbit())
                        <a href="{{ route('informasi.detail', $berita->slug) }}" target="_blank" rel="noopener"
                           class="btn btn-outline btn-sm" title="Buka halaman publik">Lihat</a>
                      @endif
                      <a href="{{ route('admin.berita.edit', $berita) }}" class="btn btn-outline btn-sm">Ubah</a>
                      <form method="POST" action="{{ route('admin.berita.destroy', $berita) }}"
                            onsubmit="return confirm('Hapus entri ini? Tindakan tidak dapat dibatalkan.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline btn-sm">Hapus</button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="muted">
                      @if ($kategoriAktif)
                        Belum ada entri kategori {{ strtolower($kategoriAktif) }}.
                        <a href="{{ route('admin.berita.create') }}">Tulis sekarang</a>.
                      @else
                        Belum ada berita. Klik "Tulis berita" untuk menambah.
                      @endif
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </main>
@endsection
