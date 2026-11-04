// Desa Sukamaju - main.js
// Berisi helper UI untuk: mobile nav, copy reference number, wizard form upload, dsb.

document.addEventListener('DOMContentLoaded', () => {
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
    adminMenuBtn.addEventListener('click', () => {
      adminSidebar.classList.toggle('open');
    });
    // Close when clicking content on mobile
    document.querySelector('.admin-main')?.addEventListener('click', () => {
      if (window.innerWidth <= 1024) adminSidebar.classList.remove('open');
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
    dz.addEventListener('click', (e) => {
      if (e.target.closest('.dz-remove')) return;
      input.click();
    });
    input.addEventListener('change', () => {
      if (input.files.length > 0) {
        const file = input.files[0];
        const fileInfo = dz.querySelector('.dz-meta');
        if (fileInfo) {
          fileInfo.querySelector('.dz-name').textContent = file.name;
          const size = file.size / 1024; // KB
          const sizeText = size > 1024 ? `${(size/1024).toFixed(1)} MB` : `${Math.round(size)} KB`;
          const hint = dz.dataset.sizeHint ? ` · ${dz.dataset.sizeHint}` : '';
          fileInfo.querySelector('.dz-size').textContent = sizeText + hint;
        }
        dz.classList.add('has-file');
        const defaultContent = dz.querySelector('.dz-default');
        const fileContent = dz.querySelector('.dropzone-file');
        if (defaultContent) defaultContent.style.display = 'none';
        if (fileContent) fileContent.style.display = 'flex';
      }
    });
    dz.querySelector('.dz-remove')?.addEventListener('click', (e) => {
      e.stopPropagation();
      input.value = '';
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

  // Informasi page: filter konten berdasarkan tab
  const infoTabs = document.querySelector('[data-info-tabs]');
  if (infoTabs) {
    const triggers = infoTabs.querySelectorAll('[data-filter]');
    const items = document.querySelectorAll('[data-cat]');
    triggers.forEach(t => t.addEventListener('click', () => {
      triggers.forEach(x => x.classList.remove('active'));
      t.classList.add('active');
      const filter = t.dataset.filter;
      items.forEach(el => {
        const cats = (el.dataset.cat || '').split(/\s+/);
        const show = filter === 'semua' || cats.includes(filter);
        el.style.display = show ? '' : 'none';
      });
    }));
  }

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
