/* SIPELFA FMIPA UNCEN - interaksi sisi browser */
(function () {
  'use strict';

  var body = document.body;

  /* ---------- Klik: sidebar, tutup notifikasi, tampilkan password, perbesar foto ---------- */
  var lightbox = document.getElementById('lightbox');

  function bukaLightbox(src) {
    if (!lightbox) { return; }
    lightbox.querySelector('img').src = src;
    lightbox.hidden = false;
  }
  function tutupLightbox() {
    if (!lightbox) { return; }
    lightbox.hidden = true;
    lightbox.querySelector('img').removeAttribute('src');
  }

  document.addEventListener('click', function (ev) {
    var t = ev.target;
    if (!t.closest) { return; }

    if (t.closest('[data-sidebar-toggle]')) {
      body.classList.toggle('sidebar-open');
      return;
    }
    if (t.closest('[data-sidebar-close]')) {
      body.classList.remove('sidebar-open');
      return;
    }

    var tutup = t.closest('.alert-close');
    if (tutup) {
      var alertEl = tutup.closest('.alert');
      if (alertEl) { alertEl.remove(); }
      return;
    }

    var pw = t.closest('[data-toggle-password]');
    if (pw) {
      var input = document.getElementById(pw.getAttribute('data-toggle-password'));
      if (input) {
        var tampil = input.type === 'password';
        input.type = tampil ? 'text' : 'password';
        pw.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
      }
      return;
    }

    var zoom = t.closest('[data-zoom]');
    if (zoom) {
      ev.preventDefault();
      bukaLightbox(zoom.getAttribute('data-zoom'));
      return;
    }

    if (lightbox && !lightbox.hidden && t.closest('#lightbox')) {
      tutupLightbox();
    }
  });

  /* ---------- Pemberitahuan sukses hilang sendiri ---------- */
  setTimeout(function () {
    var daftar = document.querySelectorAll('.alert-success');
    for (var i = 0; i < daftar.length; i++) { daftar[i].remove(); }
  }, 7000);

  /* ---------- Modal konfirmasi untuk <form data-confirm="..."> ---------- */
  var modal = document.getElementById('modal-konfirmasi');
  var formMenunggu = null;

  function tutupModal() {
    if (modal) { modal.hidden = true; }
    formMenunggu = null;
  }

  document.addEventListener('submit', function (ev) {
    var form = ev.target;
    if (!form || !form.getAttribute || !form.hasAttribute('data-confirm') || !modal) { return; }
    if (form.getAttribute('data-confirmed') === '1') { return; }

    ev.preventDefault();
    formMenunggu = form;
    modal.querySelector('[data-modal-title]').textContent = form.getAttribute('data-confirm-title') || 'Konfirmasi';
    modal.querySelector('[data-modal-text]').textContent = form.getAttribute('data-confirm');
    var lanjut = modal.querySelector('[data-modal-confirm]');
    lanjut.textContent = form.getAttribute('data-confirm-button') || 'Lanjutkan';
    lanjut.className = 'btn ' + (form.getAttribute('data-confirm-danger') === '1' ? 'btn-danger' : 'btn-primary');
    modal.hidden = false;
    lanjut.focus();
  });

  if (modal) {
    modal.addEventListener('click', function (ev) {
      var t = ev.target;
      if (t === modal || (t.closest && t.closest('[data-modal-cancel]'))) {
        tutupModal();
        return;
      }
      if (t.closest && t.closest('[data-modal-confirm]') && formMenunggu) {
        var form = formMenunggu;
        form.setAttribute('data-confirmed', '1');
        modal.hidden = true;
        HTMLFormElement.prototype.submit.call(form);
      }
    });
  }

  document.addEventListener('keydown', function (ev) {
    if (ev.key !== 'Escape') { return; }
    tutupModal();
    tutupLightbox();
    body.classList.remove('sidebar-open');
  });

  /* ---------- Pratinjau foto sebelum dikirim (bisa lebih dari satu input) ---------- */
  var jenisOk = ['image/jpeg', 'image/png', 'image/webp'];

  function pasangPratinjau(input) {
    var kotak = document.getElementById(input.getAttribute('data-preview'));
    var pesan = document.getElementById(input.getAttribute('data-preview-msg'));
    var maks = parseInt(input.getAttribute('data-max-bytes'), 10) || 5242880;

    input.addEventListener('change', function () {
      var berkas = input.files && input.files[0];
      if (kotak) { kotak.innerHTML = ''; }
      if (pesan) { pesan.textContent = ''; }
      if (!berkas) { return; }

      var galat = '';
      if (jenisOk.indexOf(berkas.type) === -1) {
        galat = 'Format foto harus JPG, PNG, atau WEBP.';
      } else if (berkas.size > maks) {
        galat = 'Ukuran foto ' + (berkas.size / 1048576).toFixed(1) + ' MB. Maksimal 5 MB.';
      }
      if (galat) {
        input.value = '';
        if (pesan) { pesan.textContent = galat; }
        return;
      }

      if (kotak) {
        var img = document.createElement('img');
        img.alt = 'Pratinjau foto';
        img.src = URL.createObjectURL(berkas);
        kotak.appendChild(img);
      }
    });
  }

  var daftarInputFoto = document.querySelectorAll('input[data-preview]');
  for (var n = 0; n < daftarInputFoto.length; n++) { pasangPratinjau(daftarInputFoto[n]); }

  /* ---------- Cegah kirim ganda pada formulir bertanda data-once ---------- */
  document.addEventListener('submit', function (ev) {
    var form = ev.target;
    if (!form || !form.hasAttribute || !form.hasAttribute('data-once') || ev.defaultPrevented) { return; }
    var tombol = form.querySelector('button[type="submit"]');
    if (tombol) {
      setTimeout(function () {
        tombol.disabled = true;
        tombol.textContent = form.getAttribute('data-once') || 'Mengirim...';
      }, 0);
    }
  });
})();
