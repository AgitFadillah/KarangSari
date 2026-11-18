@extends('layouts.admin')

@section('title', 'Ringkasan · Dashboard Admin '.$profil->nama_desa)

@push('styles')
<style>
    /* Tabel "Pengajuan terbaru" berada di kolom kiri dashboard yang sempit.
       Tanpa aturan ini, nama layanan dan tanggal terlipat menjadi tiga baris
       sehingga tinggi tiap baris tabel tidak lagi seragam. */
    /* Tabel "Pengajuan terbaru" berada di kolom kiri dashboard yang sempit.
       Padding bawaan tabel daftar (16px per sisi) terlalu lebar di sini
       sehingga empat kolom tidak muat dan tabel melebar melewati tepi panel
       (kolom status dan panah ikut terpotong). */
    .terbaru-table { width: 100%; table-layout: auto; }
    .terbaru-table th, .terbaru-table td { padding: 12px 10px; }
    .terbaru-table th:first-child, .terbaru-table td:first-child { padding-left: 0; }
    .terbaru-table th:last-child, .terbaru-table td:last-child { padding-right: 0; }
    /* .cell-avatar .cell-sub diatur "nowrap" secara global untuk tabel daftar
       yang lebar. Di dashboard kolomnya sempit, jadi nowrap membuat kolom
       pemohon melebar dan mendorong kolom status serta panah keluar panel. */
    .terbaru-table .cell-sub { white-space: normal; }
    /* Avatar jangan ikut menyusut saat kolom pemohon sempit. */
    .terbaru-table .cell-avatar { gap: 10px; }
    .terbaru-table .cell-avatar .avatar { flex: none; width: 34px; height: 34px; }
    .terbaru-table .badge { white-space: nowrap; }
    /* Kolom aksi paling kanan cukup selebar ikon panah saja. */
    .terbaru-table th:last-child, .terbaru-table td:last-child { width: 1%; white-space: nowrap; }
    /* Nama layanan dibiarkan menyesuaikan lebar kolom agar tidak memaksa
       tabel melebar; kolom kiri (tabel pengajuan) tetap diberi porsi lebih
       besar daripada kolom kanan (grafik sebaran). */
    .terbaru-layanan { min-width: 110px; }
    .ringkasan-grid { grid-template-columns: minmax(0, 1.55fr) minmax(0, 1fr); }
    @media (max-width: 1280px) { .ringkasan-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('topbar')
@php $adminTrail = [['label' => 'Admin', 'route' => null], ['label' => 'Ringkasan', 'route' => null]]; @endphp
<x-admin-topbar :trail="$adminTrail" title="Ringkasan" />
@endsection

@section('content')
<main class="admin-content">
        <div class="admin-page-head greeting">
          <div>
            <div class="date">{{ now()->locale('id')->translatedFormat('l, j F Y') }} · {{ now()->format('H.i') }} WIB</div>
            <h2>{{ $sapaan }}, {{ Str::of(auth()->user()->name)->before(' ') }}.</h2>
            <p class="sub">
              @if ($bolehVerifikasi)
                @if ($perluTindakan > 0)
                  Ada <b>{{ $perluTindakan }}</b> pengajuan yang menunggu tindakan Anda.
                @else
                  Tidak ada pengajuan yang menunggu tindakan saat ini.
                @endif
              @else
                Kelola berita, potensi, dan informasi desa lewat menu di samping.
              @endif
            </p>
          </div>
          <div class="flex gap-12 wrap">
            @if ($bolehVerifikasi)
              <a href="{{ route('admin.pengajuan.index') }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/></svg>
                Periksa pengajuan
              </a>
            @endif
            @if ($bolehKelolaKonten)
              <a href="{{ route('admin.konten.index') }}" class="btn btn-outline">Kelola konten</a>
            @endif
          </div>
        </div>

        @if ($bolehVerifikasi)
        <div class="stat-cards">
          <div class="stat-card">
            <div class="stat-card-icon blue" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
            </div>
            <div class="stat-card-label">Total pengajuan</div>
            <div class="stat-card-value">{{ $total }}</div>
            <div class="stat-card-note">Seluruh pengajuan tercatat</div>
          </div>
          <div class="stat-card">
            <div class="stat-card-icon amber" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </div>
            <div class="stat-card-label">Menunggu tindakan</div>
            <div class="stat-card-value amber">{{ $perluTindakan }}</div>
            <div class="stat-card-note">{{ $jumlahMenunggu }} baru · {{ $jumlahPerbaikan }} perlu perbaikan</div>
          </div>
          <div class="stat-card">
            <div class="stat-card-icon blue" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
            </div>
            <div class="stat-card-label">Dalam proses</div>
            <div class="stat-card-value">{{ $dalamProses }}</div>
            <div class="stat-card-note">Disetujui, dicetak, dan siap diambil</div>
          </div>
          <div class="stat-card">
            <div class="stat-card-icon green" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
            </div>
            <div class="stat-card-label">Selesai</div>
            <div class="stat-card-value green">{{ $selesai }}</div>
            <div class="stat-card-note">Surat telah diserahkan ke warga</div>
          </div>
        </div>
        @endif

        <div class="admin-grid-2 ringkasan-grid" @unless ($bolehVerifikasi) style="grid-template-columns:1fr" @endunless>
          @if ($bolehVerifikasi)
          <div class="panel">
            <div class="panel-head">
              <div>
                <h2>Pengajuan terbaru</h2>
                <p>Lima pengajuan terakhir yang masuk</p>
              </div>
              <a href="{{ route('admin.pengajuan.index') }}" class="panel-link">Semua →</a>
            </div>
            <div class="table-wrap">
              <table class="data terbaru-table">
                <thead>
                  <tr>
                    <th>Pemohon</th>
                    <th>Layanan</th>
                    <th>Status</th>
                    <th aria-label="Aksi"></th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($terbaru as $item)
                    <tr>
                      <td>
                        <div class="cell-avatar">
                          <div class="avatar">{{ \App\Enums\RoleAdmin::initials($item->nama) }}</div>
                          <div>
                            <div class="cell-main">{{ $item->nama }}</div>
                            {{-- Tanggal masuk digabung di sini agar tabel cukup
                                 empat kolom dan tidak perlu menggulir mendatar. --}}
                            <div class="cell-sub">{{ $item->nomor_referensi }} · {{ $item->created_at->locale('id')->translatedFormat('j M Y, H.i') }}</div>
                          </div>
                        </div>
                      </td>
                      <td class="terbaru-layanan">{{ $item->jenisSurat->nama }}</td>
                      <td><span class="badge {{ $item->status->badge() }}">{{ $item->status->label() }}</span></td>
                      <td class="right">
                        <a href="{{ route('admin.pengajuan.show', $item) }}" class="row-arrow" aria-label="Lihat detail pengajuan {{ $item->nomor_referensi }}">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l6 6-6 6"/></svg>
                        </a>
                      </td>
                    </tr>
                  @empty
                    <tr><td colspan="4" class="muted">Belum ada pengajuan yang masuk.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
          @endif

          <div>
            @if ($bolehVerifikasi)
            <div class="panel">
              <div class="panel-head">
                <h2>Sebaran status</h2>
                <span class="badge badge-gray">{{ $total }} total</span>
              </div>
              <div class="horizontal-bars">
                @foreach ($sebaran as $baris)
                  <div class="hbar-row">
                    <div class="hbar-top"><b>{{ $baris['status']->label() }}</b><span>{{ $baris['jumlah'] }}</span></div>
                    <div class="hbar-track">
                      <div class="hbar-fill {{ match ($baris['status']) {
                        \App\Enums\StatusPengajuan::MenungguVerifikasi, \App\Enums\StatusPengajuan::PerluPerbaikan => 'amber',
                        \App\Enums\StatusPengajuan::Ditolak => 'red',
                        \App\Enums\StatusPengajuan::Disetujui, \App\Enums\StatusPengajuan::SiapDiambil => 'green',
                        \App\Enums\StatusPengajuan::SedangDiproses => 'blue',
                        \App\Enums\StatusPengajuan::Selesai => 'navy',
                      } }}" style="width:{{ $baris['persen'] }}%"></div>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
            @endif

            <div class="panel">
              <div class="panel-head">
                <h2>Ringkasan konten &amp; layanan</h2>
                @if ($bolehKelolaKonten)
                  <a href="{{ route('admin.konten.index') }}" class="panel-link">Kelola</a>
                @endif
              </div>
              <div class="kv-row"><span class="k">Jenis surat aktif</span><span class="v">{{ $jenisSuratAktif }} dari {{ $jenisSuratTotal }}</span></div>
              <div class="kv-row"><span class="k">Konten terbit</span><span class="v">{{ $kontenTerbit }} dari {{ $kontenTotal }}</span></div>
              <div class="kv-row"><span class="k">Login terakhir</span><span class="v">{{ auth()->user()->last_login_at?->locale('id')->translatedFormat('j M Y, H.i') ?? 'Sesi ini' }}</span></div>
            </div>
          </div>
        </div>

        <div class="quick-grid">
          @if (auth()->user()->role->bolehKelolaJenisSurat())
            <a href="{{ route('admin.jenis-surat.index') }}" class="quick-card">
              <div class="icon-tile" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
              </div>
              <div>
                <b>Kelola jenis surat</b>
                <span>{{ $jenisSuratAktif }} aktif dari {{ $jenisSuratTotal }} layanan</span>
              </div>
              <span class="quick-arrow" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l6 6-6 6"/></svg></span>
            </a>
          @endif
          @if ($bolehKelolaKonten)
            <a href="{{ route('admin.konten.index') }}" class="quick-card">
              <div class="icon-tile blue" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10M7 12h10M7 16h6"/></svg>
              </div>
              <div>
                <b>Konten website</b>
                <span>{{ $kontenTerbit }} konten terbit</span>
              </div>
              <span class="quick-arrow" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l6 6-6 6"/></svg></span>
            </a>
          @endif
          <a href="{{ route('beranda') }}" target="_blank" rel="noopener" class="quick-card">
            <div class="icon-tile green" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14L21 3"/></svg>
            </div>
            <div>
              <b>Lihat website publik</b>
              <span>Buka di tab baru</span>
            </div>
            <span class="quick-arrow" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l6 6-6 6"/></svg></span>
          </a>
        </div>
      </main>
@endsection
