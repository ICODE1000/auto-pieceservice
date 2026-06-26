/* ================================================================
   PIÈCES DÉTACHÉES AUTO 69 — main.js
   Navigation multi-pages + Modal devis + Formulaires AJAX
================================================================ */

(function () {
  'use strict';

  /* ──────────────────────────────────────────────────────────────
     HEADER : shadow au scroll
  ────────────────────────────────────────────────────────────── */
  const header = document.getElementById('header');
  if (header) {
    window.addEventListener('scroll', () => {
      header.classList.toggle('scrolled', window.scrollY > 10);
    }, { passive: true });
  }

  /* ──────────────────────────────────────────────────────────────
     MENU BURGER (mobile)
  ────────────────────────────────────────────────────────────── */
  const burger = document.getElementById('burger');
  const nav    = document.getElementById('nav');

  if (burger && nav) {
    burger.addEventListener('click', () => {
      const isOpen = nav.classList.toggle('open');
      burger.classList.toggle('open', isOpen);
      burger.setAttribute('aria-expanded', String(isOpen));
      document.body.style.overflow = isOpen ? 'hidden' : '';
    });

    nav.querySelectorAll('a, button').forEach(link => {
      link.addEventListener('click', () => {
        nav.classList.remove('open');
        burger.classList.remove('open');
        burger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
      });
    });

    document.addEventListener('click', e => {
      if (nav.classList.contains('open') &&
          !nav.contains(e.target) &&
          !burger.contains(e.target)) {
        nav.classList.remove('open');
        burger.classList.remove('open');
        burger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
      }
    });
  }

  /* ──────────────────────────────────────────────────────────────
     MODAL DEVIS
  ────────────────────────────────────────────────────────────── */
  const modalOverlay   = document.getElementById('devisModal');
  const closeModalBtn  = document.getElementById('closeModal');

  function openModal() {
    if (!modalOverlay) return;
    // Reset form + messages
    const mForm = document.getElementById('modalDevisForm');
    const mMsg  = document.getElementById('modalFormMsg');
    if (mForm) {
      mForm.reset();
      mForm.querySelectorAll('.invalid').forEach(f => f.classList.remove('invalid'));
    }
    if (mMsg) { mMsg.className = 'form-message'; mMsg.innerHTML = ''; }
    modalOverlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    if (!modalOverlay) return;
    modalOverlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('[data-open-modal]').forEach(btn => {
    btn.addEventListener('click', openModal);
  });
  if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
  if (modalOverlay) {
    modalOverlay.addEventListener('click', e => {
      if (e.target === modalOverlay) closeModal();
    });
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && modalOverlay.classList.contains('open')) closeModal();
    });
  }

  /* ──────────────────────────────────────────────────────────────
     UTILITAIRES COMMUNS
  ────────────────────────────────────────────────────────────── */
  function validateField(field) {
    const val = field.value.trim();
    let ok = true;
    if (field.required && val === '')                  ok = false;
    if (field.type === 'email' && val !== '')          ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
    if (field.type === 'tel'   && val !== '')          ok = /^[+\d\s\-().]{6,20}$/.test(val);
    if (field.tagName === 'SELECT' && field.required)  ok = val !== '';
    const errorEl = document.getElementById('error-' + field.id);
    field.classList.toggle('invalid', !ok);
    if (errorEl) errorEl.classList.toggle('show', !ok);
    return ok;
  }

  function esc(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str || ''));
    return d.innerHTML;
  }

  async function submitForm(formEl, submitBtnEl, msgEl, isHeroForm) {
    const formData = new FormData(formEl);
    const btnText   = submitBtnEl.querySelector('.btn-text');
    const btnLoader = submitBtnEl.querySelector('.btn-loader');

    submitBtnEl.disabled = true;
    if (btnText)   btnText.style.display   = 'none';
    if (btnLoader) btnLoader.style.display = 'flex';
    msgEl.className = isHeroForm ? 'hero-form-msg' : 'form-message';
    msgEl.innerHTML = '';

    try {
      // Détection : ouverture locale via file:// (PHP impossible)
      if (location.protocol === 'file:') {
        throw Object.assign(new Error('file-protocol'), { isFileProtocol: true });
      }

      const resp = await fetch('api/devis.php', { method: 'POST', body: formData });
      let data;
      try { data = await resp.json(); } catch (_) { throw new Error('HTTP ' + resp.status); }
      if (!resp.ok) throw new Error(data.error || 'HTTP ' + resp.status);

      if (data.success) {
        msgEl.className = (isHeroForm ? 'hero-form-msg' : 'form-message') + ' success';
        const emailVal = formData.get('email');
        if (isHeroForm) {
          msgEl.innerHTML = '<strong>✓ Demande envoyée !</strong> Nous vous répondrons très prochainement.';
        } else {
          msgEl.innerHTML =
            '<strong>✓ Demande envoyée avec succès !</strong><br>' +
            'Un email de confirmation a été envoyé à <strong>' + esc(emailVal) + '</strong>. ' +
            'Nous vous répondrons dans les meilleurs délais.';
        }
        formEl.reset();
        formEl.querySelectorAll('.invalid').forEach(f => f.classList.remove('invalid'));
      } else {
        throw new Error(data.error || 'Erreur serveur');
      }
    } catch (err) {
      console.error('[Devis]', err);
      msgEl.className = (isHeroForm ? 'hero-form-msg' : 'form-message') + ' error';

      // Erreur de protocole file:// ou réseau (pas de serveur PHP)
      if (err.isFileProtocol || err instanceof TypeError) {
        msgEl.innerHTML =
          '<strong>✗ Formulaire non disponible en local.</strong> ' +
          'Pour tester, ouvrez le site via un serveur PHP (MAMP, WAMP, hébergement). ' +
          'En production, tout fonctionnera automatiquement.';
      } else {
        msgEl.innerHTML =
          '<strong>✗ Erreur d\'envoi.</strong> Appelez directement le ' +
          '<a href="tel:+33756811938">+33 7 56 81 19 38</a> ou ' +
          '<a href="mailto:infos@auto-pieceservice.fr">infos@auto-pieceservice.fr</a>.';
      }
    } finally {
      submitBtnEl.disabled = false;
      if (btnText)   btnText.style.display   = 'flex';
      if (btnLoader) btnLoader.style.display = 'none';
      msgEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }

  /* ──────────────────────────────────────────────────────────────
     FORMULAIRE HERO (mini form — index.html)
  ────────────────────────────────────────────────────────────── */
  const heroForm   = document.getElementById('heroForm');
  const heroSubmit = document.getElementById('heroSubmitBtn');
  const heroMsg    = document.getElementById('heroFormMsg');

  if (heroForm && heroSubmit && heroMsg) {
    heroForm.querySelectorAll('input, textarea').forEach(f => {
      f.addEventListener('blur',  () => validateField(f));
      f.addEventListener('input', () => { if (f.classList.contains('invalid')) validateField(f); });
    });
    heroForm.addEventListener('submit', async e => {
      e.preventDefault();
      let valid = true;
      heroForm.querySelectorAll('[required]').forEach(f => { if (!validateField(f)) valid = false; });
      if (!valid) return;
      await submitForm(heroForm, heroSubmit, heroMsg, true);
    });
  }

  /* ──────────────────────────────────────────────────────────────
     FORMULAIRE MODAL
  ────────────────────────────────────────────────────────────── */
  const modalForm   = document.getElementById('modalDevisForm');
  const modalSubmit = document.getElementById('modalSubmitBtn');
  const modalMsg    = document.getElementById('modalFormMsg');

  if (modalForm && modalSubmit && modalMsg) {
    modalForm.querySelectorAll('input, select, textarea').forEach(f => {
      f.addEventListener('blur',  () => validateField(f));
      f.addEventListener('input', () => { if (f.classList.contains('invalid')) validateField(f); });
    });
    modalForm.addEventListener('submit', async e => {
      e.preventDefault();
      let valid = true;
      modalForm.querySelectorAll('[required]').forEach(f => { if (!validateField(f)) valid = false; });
      if (!valid) {
        modalForm.querySelector('.invalid')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }
      await submitForm(modalForm, modalSubmit, modalMsg, false);
    });
  }

  /* ──────────────────────────────────────────────────────────────
     FORMULAIRE DEVIS COMPLET (contact.html)
  ────────────────────────────────────────────────────────────── */
  const form        = document.getElementById('devisForm');
  const submitBtn   = document.getElementById('submitBtn');
  const formMessage = document.getElementById('formMessage');

  if (form && submitBtn && formMessage) {
    form.querySelectorAll('input, select, textarea').forEach(field => {
      field.addEventListener('blur',  () => validateField(field));
      field.addEventListener('input', () => { if (field.classList.contains('invalid')) validateField(field); });
    });
    form.addEventListener('submit', async e => {
      e.preventDefault();
      let formValid = true;
      form.querySelectorAll('[required]').forEach(f => { if (!validateField(f)) formValid = false; });
      if (!formValid) {
        form.querySelector('.invalid')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }
      await submitForm(form, submitBtn, formMessage, false);
    });
  }

})();
