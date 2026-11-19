/* ============================================================
   Dropdown kustom: mengganti <select> bawaan browser.
   ------------------------------------------------------------
   Cara pakai: cukup tulis <select> seperti biasa. Saat halaman siap,
   setiap <select> kecuali yang ditandai data-pilih-lewati akan
   diubah menjadi dropdown bertema. Nilai tetap dikirim lewat
   <input type="hidden"> dengan nama yang sama, sehingga controller
   tidak perlu diubah.

   Fitur:
   - Klik untuk membuka, klik di luar / Escape untuk menutup.
   - Tombol panah atas/bawah, Home, End, Enter, dan Escape.
   - Menutup otomatis bila yang dipilih adalah pemicu submit.
   - Menghormati `disabled` dan `required` pada select asli.
   ============================================================ */

const SVG_PANAH =
  '<svg class="pilih-panah" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
  'stroke-width="2.5" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';

let idPilih = 0;

/** Membaca atribut `class` dari select asli untuk menentukan varian. */
function varianDari(select) {
  return /pilih-sm/.test(select.className) ? ' pilih-sm' : '';
}

/**
 * Mengubah satu <select> menjadi dropdown kustom.
 * Select asli dibiarkan di DOM (disembunyikan) agar form tetap valid.
 */
function ubahSelect(select) {
  if (select.dataset.pilihSiap === '1') return;

  const opsi = Array.from(select.options);
  if (opsi.length === 0) return;

  select.dataset.pilihSiap = '1';
  select.classList.add('data-pilih-asli');

  const pembungkus = document.createElement('div');
  pembungkus.className = 'pilih' + varianDari(select);
  pembungkus.dataset.pilih = '';
  if (select.disabled) pembungkus.dataset.nonaktif = '';

  const idMenu = 'pilih-menu-' + ++idPilih;

  const tombol = document.createElement('button');
  tombol.type = 'button';
  tombol.className = 'pilih-tombol';
  tombol.setAttribute('role', 'combobox');
  tombol.setAttribute('aria-haspopup', 'listbox');
  tombol.setAttribute('aria-expanded', 'false');
  tombol.setAttribute('aria-controls', idMenu);
  if (select.disabled) tombol.disabled = true;
  if (select.id) {
    // Label <label for="..."> harus tetap menunjuk ke elemen yang terlihat.
    tombol.id = select.id + '-tombol';
  }
  tombol.innerHTML =
    '<span class="pilih-nilai"></span>' + SVG_PANAH;

  const menu = document.createElement('ul');
  menu.className = 'pilih-menu';
  menu.id = idMenu;
  menu.setAttribute('role', 'listbox');
  menu.hidden = true;
  if (select.id) menu.setAttribute('aria-labelledby', tombol.id);

  opsi.forEach((o, i) => {
    const li = document.createElement('li');
    li.className = 'pilih-opsi';
    li.setAttribute('role', 'option');
    li.dataset.nilai = o.value;
    li.dataset.index = String(i);
    li.textContent = o.textContent.trim();
    if (o.disabled) {
      li.setAttribute('aria-disabled', 'true');
      li.style.opacity = '.45';
      li.style.cursor = 'not-allowed';
    }
    menu.appendChild(li);
  });

  pembungkus.appendChild(tombol);
  pembungkus.appendChild(menu);
  select.parentNode.insertBefore(pembungkus, select);
  pembungkus.appendChild(select);
  select.style.display = 'none';

  const nilaiEl = tombol.querySelector('.pilih-nilai');

  /** Menampilkan nilai yang sedang terpilih pada tombol. */
  function gambarTombol() {
    const o = select.options[select.selectedIndex];
    const teks = o ? o.textContent.trim() : '';
    nilaiEl.textContent = teks;

    // Opsi kosong dianggap placeholder (mis. "Pilih kategori").
    const kosong = !o || o.value === '' || o.disabled;
    nilaiEl.classList.toggle('kosong', kosong);
    if (!o && select.dataset.placeholder) {
      nilaiEl.textContent = select.dataset.placeholder;
    }

    menu.querySelectorAll('.pilih-opsi').forEach((li) => {
      li.setAttribute(
        'aria-selected',
        li.dataset.index === String(select.selectedIndex) ? 'true' : 'false'
      );
    });
  }

  function posisiMenu() {
    // Buka ke atas bila ruang di bawah tidak cukup.
    const tinggiMenu = Math.min(264, menu.scrollHeight || 264);
    const ruangBawah = window.innerHeight - tombol.getBoundingClientRect().bottom;
    pembungkus.classList.toggle('atas', ruangBawah < tinggiMenu + 24);
  }

  function buka() {
    if (select.disabled) return;
    tutupSemua(pembungkus);
    posisiMenu();
    menu.hidden = false;
    tombol.setAttribute('aria-expanded', 'true');

    // Fokuskan opsi yang sedang terpilih.
    const terpilih = menu.querySelector('[aria-selected="true"]:not([aria-disabled])');
    const pertama = menu.querySelector('.pilih-opsi:not([aria-disabled])');
    (terpilih || pertama)?.classList.add('aktif');
    (terpilih || pertama)?.scrollIntoView({ block: 'nearest' });
  }

  function tutup() {
    menu.hidden = true;
    tombol.setAttribute('aria-expanded', 'false');
    menu.querySelectorAll('.aktif').forEach((el) => el.classList.remove('aktif'));
  }

  function pilih(li) {
    if (!li || li.getAttribute('aria-disabled') === 'true') return;
    select.selectedIndex = Number(li.dataset.index);
    // Beri tahu kode lain (mis. filter otomatis) bahwa nilai berubah.
    select.dispatchEvent(new Event('change', { bubbles: true }));
    gambarTombol();
    tutup();
    tombol.focus();
  }

  tombol.addEventListener('click', (e) => {
    e.stopPropagation();
    menu.hidden ? buka() : tutup();
  });

  // PENTING: cegah mousedown pada menu mencabut fokus dari tombol.
  //
  // Tanpa ini, menekan sebuah opsi (yang berupa <li>, bukan elemen yang bisa
  // difokus) membuat browser memindahkan fokus ke <body>. Akibatnya handler
  // "focusout" di bawah langsung menutup menu SEBELUM event "click" pada opsi
  // sempat diproses — inilah sebab dropdown terlihat "tidak bisa dipilih":
  // menu sempat terbuka, tetapi pilihan tidak pernah tersimpan.
  menu.addEventListener('mousedown', (e) => e.preventDefault());

  menu.addEventListener('click', (e) => {
    const li = e.target.closest('.pilih-opsi');
    if (li) pilih(li);
  });

  // Sorot opsi saat kursor berhenti di atasnya (memudahkan arah keyboard).
  menu.addEventListener('mouseover', (e) => {
    const li = e.target.closest('.pilih-opsi');
    if (!li || li.getAttribute('aria-disabled') === 'true') return;
    menu.querySelectorAll('.aktif').forEach((el) => el.classList.remove('aktif'));
    li.classList.add('aktif');
  });

  tombol.addEventListener('keydown', (e) => {
    const bisa = Array.from(menu.querySelectorAll('.pilih-opsi:not([aria-disabled])'));
    if (!bisa.length) return;
    let idx = bisa.indexOf(menu.querySelector('.pilih-opsi.aktif'));

    switch (e.key) {
      case 'ArrowDown':
      case 'ArrowUp': {
        e.preventDefault();
        if (menu.hidden) { buka(); return; }
        idx = e.key === 'ArrowDown'
          ? (idx + 1) % bisa.length
          : (idx <= 0 ? bisa.length - 1 : idx - 1);
        bisa.forEach((el) => el.classList.remove('aktif'));
        bisa[idx].classList.add('aktif');
        bisa[idx].scrollIntoView({ block: 'nearest' });
        break;
      }
      case 'Home':
      case 'End': {
        if (menu.hidden) return;
        e.preventDefault();
        bisa.forEach((el) => el.classList.remove('aktif'));
        const target = e.key === 'Home' ? bisa[0] : bisa[bisa.length - 1];
        target.classList.add('aktif');
        target.scrollIntoView({ block: 'nearest' });
        break;
      }
      case 'Enter':
      case ' ': {
        if (menu.hidden) return;
        e.preventDefault();
        pilih(menu.querySelector('.pilih-opsi.aktif'));
        break;
      }
      case 'Escape': {
        if (menu.hidden) return;
        e.preventDefault();
        tutup();
        break;
      }
      case 'Tab': {
        tutup();
        break;
      }
    }
  });

  // Menutup saat fokus keluar dari komponen.
  pembungkus.addEventListener('focusout', (e) => {
    if (!pembungkus.contains(e.relatedTarget)) tutup();
  });

  select.addEventListener('change', gambarTombol);

  gambarTombol();
}

/** Menutup semua dropdown kustom selain $kecuali. */
function tutupSemua(kecuali) {
  document.querySelectorAll('.pilih[data-pilih]').forEach((p) => {
    if (p === kecuali) return;
    if (p._tutup) p._tutup();
  });
}

/** Daftarkan penutup global sekali saja. */
function pasangPenutupGlobal() {
  if (window.__pilihGlobalTerpasang) return;
  window.__pilihGlobalTerpasang = true;

  document.addEventListener('click', () => tutupSemua(null));
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') tutupSemua(null);
  });
}

/** Mengubah semua <select> pada sebuah akar dokumen. */
export function siapkanPilih(akar = document) {
  akar
    .querySelectorAll('select:not([data-pilih-lewati])')
    .forEach((select) => {
      ubahSelect(select);
      // Simpan penutup pada pembungkus agar bisa ditutup secara global.
      const pembungkus = select.closest('.pilih');
      if (pembungkus) {
        const tombol = pembungkus.querySelector('.pilih-tombol');
        const menu = pembungkus.querySelector('.pilih-menu');
        pembungkus._tutup = () => {
          menu.hidden = true;
          tombol.setAttribute('aria-expanded', 'false');
        };
      }
    });

  pasangPenutupGlobal();
}

document.addEventListener('DOMContentLoaded', () => siapkanPilih());
