@extends('layouts.admin')

@section('title', 'Akun Admin · Dashboard Admin '.$profil->nama_desa)

@push('styles')
<style>
.akun-table { min-width: 760px; }
    .action-cell { white-space: nowrap; }

    .panel-foot {
      display: flex; justify-content: space-between; align-items: center; gap: 16px;
      flex-wrap: wrap; border-top: 1px solid var(--gray-200);
      margin-top: 20px; padding-top: 18px;
      font-size: .875rem; color: var(--gray-500);
    }

    .pw-rules { list-style: none; margin-top: 14px; }
    .pw-rules li {
      display: flex; gap: 8px; align-items: center;
      font-size: .875rem; color: var(--gray-500); padding: 3px 0;
    }
    .pw-rules li svg { width: 14px; height: 14px; flex: none; }
    .pw-rules li.ok { color: var(--green-600); }
</style>
@endpush

@section('topbar')
@php $adminTrail = [['label' => 'Admin', 'route' => 'admin.ringkasan'], ['label' => 'Akun Admin', 'route' => null]]; @endphp
<x-admin-topbar :trail="$adminTrail" title="Akun Admin" />
@endsection

@section('content')
<main class="admin-content">
        <div class="admin-page-head">
          <div>
            <p class="sub">Kelola akun petugas yang dapat masuk ke dashboard desa.</p>
          </div>
          @if (auth()->user()->role->bolehKelolaAkun())
            <button type="button" class="btn btn-primary" data-modal-open="modalTambah">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
              Tambah admin
            </button>
          @endif
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

        <div class="panel">
          <div class="panel-head">
            <div>
              <h2>Daftar akun admin</h2>
              <p>{{ $akuns->count() }} akun terdaftar dengan peran dan status masing-masing.</p>
            </div>
            <span class="badge badge-gray">{{ $akuns->count() }} akun</span>
          </div>
          <div class="table-wrap">
            <table class="data akun-table">
              <thead>
                <tr>
                  <th>Akun</th>
                  <th>Peran</th>
                  <th>Status</th>
                  <th>Login terakhir</th>
                  <th aria-label="Aksi"></th>
                </tr>
              </thead>
              <tbody>
                @foreach ($akuns as $akun)
                  <tr>
                    <td>
                      <div class="cell-avatar">
                        <div class="avatar">{{ $akun->initials }}</div>
                        <div>
                          <div class="cell-main">{{ $akun->name }}</div>
                          <div class="cell-sub">{{ $akun->username }}</div>
                        </div>
                      </div>
                    </td>
                    <td><span class="badge {{ $akun->role->badge() }}">{{ $akun->role->label() }}</span></td>
                    <td>
                      @if ($akun->is_active)
                        <span class="badge badge-green">Aktif</span>
                      @else
                        <span class="badge badge-gray">Nonaktif</span>
                      @endif
                    </td>
                    <td>
                      @if ($akun->last_login_at)
                        <div>{{ $akun->last_login_at->locale('id')->translatedFormat('j M Y') }}</div>
                        <div class="cell-sub">{{ $akun->last_login_at->format('H.i') }}</div>
                      @else
                        <div>Belum pernah masuk</div>
                        <div class="cell-sub">Akun baru</div>
                      @endif
                    </td>
                    <td class="right action-cell">
                      @if (auth()->user()->role->bolehKelolaAkun())
                        <div class="flex gap-8" style="justify-content:flex-end">
                          <button type="button" class="btn btn-outline btn-sm"
                                  data-modal-open="modalUbah"
                                  data-id="{{ $akun->id }}"
                                  data-nama="{{ $akun->name }}"
                                  data-username="{{ $akun->username }}"
                                  data-email="{{ $akun->email }}"
                                  data-role="{{ $akun->role->value }}"
                                  data-aktif="{{ $akun->is_active ? '1' : '0' }}">Ubah</button>
                          <button type="button" class="btn btn-outline btn-sm"
                                  data-modal-open="modalSandi"
                                  data-id="{{ $akun->id }}"
                                  data-nama="{{ $akun->name }}">Reset sandi</button>
                        </div>
                      @else
                        <span class="muted small">Tidak berwenang</span>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>

        <div class="panel">
          <div class="panel-head">
            <div>
              <h2>Keamanan akun</h2>
            </div>
          </div>
          <ul class="doc-bullets">
            <li class="green">Kata sandi disimpan sebagai hash dan minimal 12 karakter.</li>
            <li class="green">Percobaan login dibatasi 6 kali per menit untuk mencegah akses berulang.</li>
            <li class="green">Tindakan penting tercatat pada <a href="{{ route('admin.log') }}">log aktivitas</a>.</li>
          </ul>
          <div class="note-box mt-16">Jangan membagikan akses akun. Setiap petugas menggunakan akun masing-masing agar jejak audit tetap jelas.</div>
        </div>

        @if (auth()->user()->role->bolehKelolaAkun())
          {{-- Modal tambah akun --}}
          <div class="modal-overlay" id="modalTambah">
            <div class="modal" role="dialog" aria-modal="true" aria-labelledby="tambahTitle">
              <div class="modal-head">
                <h2 id="tambahTitle">Tambah admin</h2>
                <button type="button" class="modal-close" data-modal-close aria-label="Tutup">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
              </div>
              <form method="POST" action="{{ route('admin.akun.store') }}">
                @csrf
                <div class="field mb-16">
                  <label for="nama">Nama lengkap</label>
                  <input type="text" id="nama" name="name" required>
                </div>
                <div class="field mb-16">
                  <label for="username">Nama pengguna</label>
                  <input type="text" id="username" name="username" required autocomplete="off">
                </div>
                <div class="field mb-16">
                  <label for="email">Email (opsional)</label>
                  <input type="email" id="email" name="email">
                </div>
                <div class="field mb-16">
                  <label for="role">Peran</label>
                  <select id="role" name="role" required>
                    @foreach ($roles as $peran)
                      <option value="{{ $peran->value }}">{{ $peran->label() }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="field mb-16">
                  <label for="password">Kata sandi</label>
                  <input type="password" id="password" name="password" required minlength="12" autocomplete="new-password">
                  <p class="field-help">Minimal 12 karakter.</p>
                </div>
                <div class="field mb-16">
                  <label for="password_confirmation">Ulangi kata sandi</label>
                  <input type="password" id="password_confirmation" name="password_confirmation" required minlength="12" autocomplete="new-password">
                </div>
                <div class="modal-actions">
                  <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
                  <button type="submit" class="btn btn-primary">Simpan akun</button>
                </div>
              </form>
            </div>
          </div>

          {{-- Modal ubah akun --}}
          <div class="modal-overlay" id="modalUbah">
            <div class="modal" role="dialog" aria-modal="true" aria-labelledby="ubahTitle">
              <div class="modal-head">
                <h2 id="ubahTitle">Ubah akun</h2>
                <button type="button" class="modal-close" data-modal-close aria-label="Tutup">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
              </div>
              <form method="POST" id="formUbah" action="">
                @csrf
                @method('PUT')
                <div class="field mb-16">
                  <label for="ubahNama">Nama lengkap</label>
                  <input type="text" id="ubahNama" name="name" required>
                </div>
                <div class="field mb-16">
                  <label for="ubahUser">Nama pengguna</label>
                  <input type="text" id="ubahUser" name="username" required>
                </div>
                <div class="field mb-16">
                  <label for="ubahEmail">Email (opsional)</label>
                  <input type="email" id="ubahEmail" name="email">
                </div>
                <div class="field mb-16">
                  <label for="ubahPeran">Peran</label>
                  <select id="ubahPeran" name="role" required>
                    @foreach ($roles as $peran)
                      <option value="{{ $peran->value }}">{{ $peran->label() }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="field mb-16">
                  <label for="ubahAktif">Status akun</label>
                  <select id="ubahAktif" name="is_active">
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                  </select>
                </div>
                <div class="modal-actions">
                  <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
                  <button type="submit" class="btn btn-primary">Simpan perubahan</button>
                </div>
              </form>
            </div>
          </div>

          {{-- Modal reset kata sandi --}}
          <div class="modal-overlay" id="modalSandi">
            <div class="modal" role="dialog" aria-modal="true" aria-labelledby="sandiTitle">
              <div class="modal-head">
                <h2 id="sandiTitle">Reset kata sandi</h2>
                <button type="button" class="modal-close" data-modal-close aria-label="Tutup">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
              </div>
              <p class="muted" id="sandiUntuk"></p>
              <form method="POST" id="formSandi" action="">
                @csrf
                @method('PUT')
                <div class="field mb-16">
                  <label for="sandiBaru">Kata sandi baru</label>
                  <input type="password" id="sandiBaru" name="password" required minlength="12" autocomplete="new-password">
                </div>
                <div class="field mb-16">
                  <label for="sandiUlang">Ulangi kata sandi baru</label>
                  <input type="password" id="sandiUlang" name="password_confirmation" required minlength="12" autocomplete="new-password">
                </div>
                <ul class="pw-rules">
                  <li data-rule="len">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                    <span>Minimal 12 karakter</span>
                  </li>
                  <li data-rule="same">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                    <span>Ketikan ulang sama</span>
                  </li>
                </ul>
                <div class="modal-actions">
                  <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
                  <button type="submit" class="btn btn-primary">Perbarui kata sandi</button>
                </div>
              </form>
            </div>
          </div>
        @endif
      </main>
@endsection

@push('scripts')
<script>
(function () {
  // Isi modal ubah / reset sandi dari data baris tabel.
  document.querySelectorAll('[data-modal-open="modalUbah"]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.getElementById('ubahNama').value = btn.dataset.nama || '';
      document.getElementById('ubahUser').value = btn.dataset.username || '';
      document.getElementById('ubahEmail').value = btn.dataset.email || '';
      document.getElementById('ubahPeran').value = btn.dataset.role || '';
      document.getElementById('ubahAktif').value = btn.dataset.aktif || '1';
      document.getElementById('formUbah').action = '{{ url('admin/akun') }}/' + btn.dataset.id;
    });
  });

  document.querySelectorAll('[data-modal-open="modalSandi"]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.getElementById('sandiUntuk').textContent = 'Atur kata sandi baru untuk ' + (btn.dataset.nama || 'akun ini') + '.';
      document.getElementById('formSandi').action = '{{ url('admin/akun') }}/' + btn.dataset.id + '/sandi';
    });
  });

  var baru = document.getElementById('sandiBaru');
  var ulang = document.getElementById('sandiUlang');
  function evalRules() {
    if (!baru || !ulang) return;
    var rules = {
      len: baru.value.length >= 12,
      same: ulang.value.length > 0 && baru.value === ulang.value
    };
    document.querySelectorAll('#modalSandi .pw-rules li').forEach(function (li) {
      li.classList.toggle('ok', !!rules[li.dataset.rule]);
    });
  }
  baru && baru.addEventListener('input', evalRules);
  ulang && ulang.addEventListener('input', evalRules);
})();
</script>
@endpush
