// Desa Sukamaju - main.js
// Berisi helper UI untuk: mobile nav, copy reference number, wizard form upload, dsb.

document.addEventListener('DOMContentLoaded', () => {
  // Dropdown "Ajukan Surat" di header publik: buka/tutup menu layanan.
  // Ditutup saat klik di luar, tekan Escape, atau memilih salah satu tautan.
  const aksiToggle = document.getElementById('navAksiToggle');
  const aksiMenu = document.getElementById('navAksiMenu');
  if (aksiToggle && aksiMenu) {
    const setAksiOpen = (open) => {
      aksiMenu.hidden = !open;
      aksiToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    aksiToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      setAksiOpen(aksiMenu.hidden);
    });

    document.addEventListener('click', (e) => {
      if (!aksiMenu.hidden && !aksiMenu.contains(e.target) && !aksiToggle.contains(e.target)) {
        setAksiOpen(false);
      }
    });

    document.addEventListener('keydown', (e) => {
      if (aksiMenu.hidden) return;

      if (e.key === 'Escape') {
        setAksiOpen(false);
        aksiToggle.focus();
        return;
      }

      // Navigasi papan ketik antar tautan menu.
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        const links = Array.from(aksiMenu.querySelectorAll('a'));
        if (!links.length) return;
        e.preventDefault();
        const idx = links.indexOf(document.activeElement);
        const next = e.key === 'ArrowDown'
          ? (idx + 1) % links.length
          : (idx <= 0 ? links.length - 1 : idx - 1);
        links[next].focus();
      }
    });
  }

  // Mobile nav toggle (public pages)
  const navToggle = document.getElementById('navToggle');
  const mainNav = document.getElementById('mainNav');
  if (navToggle && mainNav) {
    navToggle.addEventListener('click', () => {
      mainNav.classList.toggle('open');
      navToggle.setAttribute('aria-expanded', mainNav.classList.contains('open'));
    });
  }

  // Admin sidebar toggle (mobile)
  const adminMenuBtn = document.getElementById('adminMenuBtn');
  const adminSidebar = document.getElementById('adminSidebar');
  if (adminMenuBtn && adminSidebar) {
    const setSidebarOpen = (open) => {
      adminSidebar.classList.toggle('open', open);
      adminMenuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    adminMenuBtn.addEventListener('click', (e) => {
      // Tombol ini berada DI DALAM .admin-main, jadi tanpa stopPropagation
      // kliknya akan ikut memicu listener "klik konten menutup sidebar" di
      // bawah dan menu langsung tertutup lagi — gejalanya sidebar seolah
      // tidak bisa dibuka sama sekali.
      e.stopPropagation();
      setSidebarOpen(!adminSidebar.classList.contains('open'));
    });

    // Tutup saat area konten diketuk (di ponsel), tetapi jangan menutup
    // ketika yang diketuk justru tombol pembukanya.
    document.querySelector('.admin-main')?.addEventListener('click', (e) => {
      if (window.innerWidth <= 1024 && !adminMenuBtn.contains(e.target)) {
        setSidebarOpen(false);
      }
    });

    // Tutup dengan Escape agar bisa dipakai tanpa mouse.
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && adminSidebar.classList.contains('open')) {
        setSidebarOpen(false);
        adminMenuBtn.focus();
      }
    });
  }

  // Menu pengguna di topbar admin: buka/tutup dropdown.
  // Ditutup saat klik di luar, tekan Escape, atau pilih salah satu menu.
  const userBtn = document.getElementById('adminUserBtn');
  const userPanel = document.getElementById('adminUserPanel');
  if (userBtn && userPanel) {
    const setOpen = (open) => {
      userPanel.hidden = !open;
      userBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    userBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      setOpen(userPanel.hidden);
    });

    document.addEventListener('click', (e) => {
      if (!userPanel.hidden && !userPanel.contains(e.target) && e.target !== userBtn) {
        setOpen(false);
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !userPanel.hidden) {
        setOpen(false);
        userBtn.focus();
      }
    });
  }

  // Menu pengguna di sidebar admin: buka/tutup panel.
  // Baris ini dulu hanya tampilan statis (ikon panah tidak melakukan apa
  // pun), sehingga perangkat desa mengira menunya rusak.
  const sideUserBtn = document.getElementById('adminSidebarUserBtn');
  const sideUserPanel = document.getElementById('adminSidebarUserPanel');
  if (sideUserBtn && sideUserPanel) {
    const setSideOpen = (open) => {
      sideUserPanel.hidden = !open;
      sideUserBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    sideUserBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      setSideOpen(sideUserPanel.hidden);
    });

    document.addEventListener('click', (e) => {
      if (!sideUserPanel.hidden && !sideUserPanel.contains(e.target) && !sideUserBtn.contains(e.target)) {
        setSideOpen(false);
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !sideUserPanel.hidden) {
        setSideOpen(false);
        sideUserBtn.focus();
      }
    });
  }

  // Copy reference number
  document.querySelectorAll('[data-copy]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const target = document.querySelector(btn.dataset.copy);
      if (!target) return;
      const text = target.textContent.trim();
      try {
        await navigator.clipboard.writeText(text);
        const original = btn.innerHTML;
        btn.textContent = 'Nomor tersalin ✓';
        btn.disabled = true;
        setTimeout(() => {
          btn.innerHTML = original;
          btn.disabled = false;
        }, 2000);
      } catch {
        // fallback
        const range = document.createRange();
        range.selectNodeContents(target);
        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
      }
    });
  });

  // Tabs (public + admin): data-tabs="group" on container,
  // buttons with data-tab="id", panes with data-tab-pane="id"
  document.querySelectorAll('[data-tabs]').forEach(tabs => {
    const group = tabs.dataset.tabs;
    const triggers = tabs.querySelectorAll('[data-tab]');
    const panes = document.querySelectorAll(`[data-tab-pane][data-tabs="${group}"], [data-tab-pane].group-${group}`);
    triggers.forEach(t => {
      t.addEventListener('click', () => {
        triggers.forEach(x => x.classList.remove('active'));
        t.classList.add('active');
        const targetId = t.dataset.tab;
        panes.forEach(p => {
          p.style.display = p.dataset.tabPane === targetId ? '' : 'none';
        });
      });
    });
  });

  // Doc upload dropzone
  document.querySelectorAll('[data-dropzone]').forEach(dz => {
    const input = dz.querySelector('input[type=file]');
    if (!input) return;

    const defaultContent = dz.querySelector('.dz-default');
    const fileContent = dz.querySelector('.dropzone-file');

    /** Menampilkan kotak "berkas terpilih" alih-alih ajakan memilih. */
    const tampilkanTerpilih = (nama, ukuran) => {
      const meta = dz.querySelector('.dz-meta');
      if (meta) {
        const nameEl = meta.querySelector('.dz-name');
        const sizeEl = meta.querySelector('.dz-size');
        if (nameEl) nameEl.textContent = nama;
        if (sizeEl) sizeEl.textContent = ukuran;
      }
      dz.classList.add('has-file');
      if (defaultContent) defaultContent.style.display = 'none';
      if (fileContent) fileContent.style.display = 'flex';
    };

    /** Kembali ke keadaan "belum ada berkas". */
    const tampilkanKosong = () => {
      dz.classList.remove('has-file');
      if (defaultContent) defaultContent.style.display = '';
      if (fileContent) fileContent.style.display = 'none';
    };

    // Berkas yang sudah tersimpan sebelumnya (mis. logo atau foto beranda
    // yang baru diunggah). Tanpa ini kotak kembali kosong setelah halaman
    // dimuat ulang, sehingga perangkat desa mengira unggahannya gagal.
    if (dz.dataset.berkasAda) {
      tampilkanTerpilih(
        dz.dataset.berkasNama || 'Berkas tersimpan',
        dz.dataset.berkasUkuran || 'Sudah diunggah'
      );
    }

    dz.addEventListener('click', (e) => {
      if (e.target.closest('.dz-remove')) return;
      input.click();
    });

    input.addEventListener('change', () => {
      if (input.files.length === 0) return;

      const file = input.files[0];
      const kb = file.size / 1024;
      const ukuran = kb > 1024 ? `${(kb / 1024).toFixed(1)} MB` : `${Math.round(kb)} KB`;
      const petunjuk = dz.dataset.sizeHint ? ` · ${dz.dataset.sizeHint}` : '';

      // Batalkan permintaan hapus bila pengguna memilih berkas baru.
      const hapus = dz.querySelector('.dz-hapus');
      if (hapus) hapus.value = '0';

      tampilkanTerpilih(file.name, ukuran + petunjuk);
    });

    dz.querySelector('.dz-remove')?.addEventListener('click', (e) => {
      e.stopPropagation();
      input.value = '';

      // Batalkan permintaan hapus: tombol ini hanya untuk memilih berkas
      // pengganti, bukan menghapus foto desa.
      const hapus = dz.querySelector('.dz-hapus');
      if (hapus) hapus.value = '0';

      tampilkanKosong();
    });
  });

  // Tautan "Hapus foto desa" di bawah kotak unggah. Dipisah dari tombol
  // Ganti supaya foto desa tidak terhapus karena salah tekan.
  document.querySelectorAll('[data-hapus-foto]').forEach(tautan => {
    tautan.addEventListener('click', (e) => {
      e.preventDefault();

      const form = tautan.closest('form');
      const dz = form?.querySelector('input[name="foto_beranda"]')?.closest('[data-dropzone]');
      if (!dz) return;

      const input = dz.querySelector('input[type=file]');
      if (input) input.value = '';

      const hapus = dz.querySelector('.dz-hapus');
      if (hapus) hapus.value = '1';

      // Tampilkan keadaan kosong agar jelas fotonya akan terhapus.
      dz.classList.remove('has-file');
      const defaultContent = dz.querySelector('.dz-default');
      const fileContent = dz.querySelector('.dropzone-file');
      if (defaultContent) defaultContent.style.display = '';
      if (fileContent) fileContent.style.display = 'none';
    });
  });

  // Password toggle (provides eye icon)
  document.querySelectorAll('[data-pw-toggle]').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = btn.parentElement.querySelector('input');
      if (!input) return;
      const isPw = input.type === 'password';
      input.type = isPw ? 'text' : 'password';
      btn.setAttribute('aria-label', isPw ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
      btn.querySelector('svg')?.style.setProperty('opacity', isPw ? '0.6' : '1');
    });
  });

  // Wizard (multi-step form) - form pengajuan
  const wizard = document.getElementById('wizard');
  if (wizard) {
    const steps = wizard.querySelectorAll('.wizard-step');
    const nextBtns = wizard.querySelectorAll('[data-wizard-next]');
    const prevBtns = wizard.querySelectorAll('[data-wizard-prev]');
    const stepDots = document.querySelectorAll('.step-dot');
    let currentStep = 0;

    function goToStep(n) {
      steps.forEach((s, i) => s.style.display = i === n ? '' : 'none');
      stepDots.forEach((d, i) => {
        d.classList.toggle('active', i === n);
        d.classList.toggle('completed', i < n);
      });
      currentStep = n;
    }
    nextBtns.forEach(btn => btn.addEventListener('click', () => {
      // Basic validation: required fields in current step
      const step = steps[currentStep];
      const required = step.querySelectorAll('[required]');
      let valid = true;
      required.forEach(field => {
        if (!field.value.trim()) {
          field.classList.add('invalid');
          valid = false;
        } else {
          field.classList.remove('invalid');
        }
      });
      if (!valid) return;
      if (currentStep < steps.length - 1) goToStep(currentStep + 1);
      else {
        // final step → submit
        wizard.submit?.() || wizard.closest('form')?.submit();
      }
    }));
    prevBtns.forEach(btn => btn.addEventListener('click', () => {
      if (currentStep > 0) goToStep(currentStep - 1);
    }));
  }

  // Animated counters (stats)
  document.querySelectorAll('[data-counter]').forEach(el => {
    const target = parseInt(el.dataset.counter, 10);
    if (isNaN(target)) return;
    const duration = 1500;
    const start = performance.now();
    function update(now) {
      const progress = Math.min((now - start) / duration, 1);
      const ease = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.round(target * ease).toLocaleString('id-ID');
      if (progress < 1) requestAnimationFrame(update);
    }
    requestAnimationFrame(update);
  });

  // Pelayanan: pencarian katalog jenis surat (filter langsung + status kosong)
  const catalogSearch = document.getElementById('catalogSearch');
  if (catalogSearch) {
    const cards = Array.from(document.querySelectorAll('.catalog-card'));
    const countEl = document.getElementById('catalogCount');
    const emptyEl = document.getElementById('catalogEmpty');
    const total = cards.length;

    const apply = () => {
      const q = catalogSearch.value.trim().toLowerCase();
      let visible = 0;
      cards.forEach(card => {
        const match = !q || card.textContent.toLowerCase().includes(q);
        card.style.display = match ? '' : 'none';
        if (match) visible++;
      });
      if (countEl) {
        countEl.textContent = q
          ? `${visible} dari ${total} jenis surat cocok dengan pencarian`
          : `${total} dari ${total} jenis surat ditampilkan`;
      }
      emptyEl?.classList.toggle('show', visible === 0);
    };

    catalogSearch.addEventListener('input', apply);
    document.getElementById('catalogReset')?.addEventListener('click', () => {
      catalogSearch.value = '';
      apply();
      catalogSearch.focus();
    });
  }

  // Modal
  document.querySelectorAll('[data-modal-open]').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById(btn.dataset.modalOpen)?.classList.add('open');
    });
  });
  document.querySelectorAll('[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', () => {
      btn.closest('.modal-overlay')?.classList.remove('open');
    });
  });
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) overlay.classList.remove('open');
    });
  });

  // Informasi page: pil kategori kini berupa tautan ?kategori=… yang disaring
  // di server, sehingga bagian kosong tidak dirender sama sekali. Skrip lama
  // menyembunyikan kartu di sisi klien tetapi membiarkan judul seksi dan banner
  // sorotan tetap tampil — itulah sebabnya pil terasa "tidak bekerja".
  // Di sini hanya ditambahkan penanda aktif agar tetap responsif saat diklik.
  const infoTabs = document.querySelector('[data-info-tabs]');
  if (infoTabs) {
    infoTabs.querySelectorAll('[data-filter]').forEach((tautan) => {
      tautan.addEventListener('click', () => {
        infoTabs.querySelectorAll('[data-filter]').forEach((x) => {
          x.classList.remove('active');
          x.removeAttribute('aria-current');
        });
        tautan.classList.add('active');
        tautan.setAttribute('aria-current', 'true');
      });
    });
  }

  // Tabel data desa di panel admin: kolom "Jumlah" pada tabel golongan umur
  // dihitung langsung dari L + P saat diketik, agar perangkat desa melihat
  // angka akhirnya tanpa harus menyimpan lebih dulu.
  document.querySelectorAll('[data-jumlah]').forEach((sel) => {
    const baris = sel.closest('tr');
    if (!baris) return;
    const input = baris.querySelectorAll('input');
    if (input.length < 2) return;

    const hitung = () => {
      const angka = (el) => parseInt((el.value || '').replace(/[^0-9]/g, ''), 10) || 0;
      const total = angka(input[0]) + angka(input[1]);
      sel.textContent = total > 0 ? total.toLocaleString('id-ID') : '—';
    };

    input.forEach((el) => el.addEventListener('input', hitung));
  });

  // Scroll reveal animations
  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('visible');
        revealObserver.unobserve(e.target);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

  document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

  // Animated progress bars (initial width set inline by CSS, JS handles reveal)
  document.querySelectorAll('.bar-fill[data-width]').forEach(el => {
    el.style.width = el.dataset.width;
  });
});
