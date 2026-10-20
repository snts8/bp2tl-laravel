/* =========================================================
   Pintasan keyboard: ketik "admin" di keyboard (di luar kolom
   input/textarea) untuk otomatis membuka halaman login admin.
   Didaftarkan di luar DOMContentLoaded supaya aktif sesegera
   mungkin begitu skrip dimuat.
   ========================================================= */
(function bp2tlAdminShortcut(){
  const TARGET = 'admin';
  let buffer = '';

  document.addEventListener('keydown', (e) => {
    const tag = (e.target && e.target.tagName || '').toLowerCase();
    const isTyping = tag === 'input' || tag === 'textarea' || tag === 'select' || (e.target && e.target.isContentEditable);

    if(isTyping){
      buffer = ''; // jangan aktif saat user sedang mengisi form
      return;
    }

    if(e.key && e.key.length === 1 && /[a-z]/i.test(e.key)){
      buffer = (buffer + e.key.toLowerCase()).slice(-TARGET.length);
      if(buffer === TARGET){
        buffer = '';
        window.location.href = (window.BP2TL_URLS && window.BP2TL_URLS.admin) || '/admin';
      }
    }else if(e.key !== 'Shift'){
      buffer = ''; // tombol lain (spasi, panah, dst.) mereset urutan ketikan
    }
  });
})();

document.addEventListener('DOMContentLoaded', () => {

    /* ---------- Muat teks Hero & section lain dari pengaturan admin ---------- */
  (async function bp2tlLoadSiteSettings(){
    try{
      const res = await bp2tlSettingsGet();
      if(!res.success || !res.data) return;
      Object.keys(res.data).forEach(key => {
        const value = res.data[key];
        if(value == null || value === '') return;

        // Kunci "hero_photo_*" dipasang sebagai background-image (foto hero)
        if(key.startsWith('hero_photo_')){
          document.querySelectorAll(`[data-setting-bg="${key}"]`).forEach(el => {
            el.style.backgroundImage = `url('${value}')`;
          });
          return;
        }

        // Kunci yang dipasangkan lewat atribut data-setting-href dipasang
        // sebagai atribut href tautan (dipakai untuk tautan di footer,
        // mis. footer_link1_href, footer_link2_href, dst.)
        document.querySelectorAll(`[data-setting-href="${key}"]`).forEach(el => {
          el.setAttribute('href', value);
        });

        // Kunci teks biasa dipasang sebagai textContent
        document.querySelectorAll(`[data-setting="${key}"]`).forEach(el => {
          el.textContent = value;
        });

        // Kalau kunci ini juga dipakai sebagai alamat peta (data-map-src /
        // data-map-link), bangun ulang URL Google Maps-nya dari teks alamat
        // terbaru, supaya peta & tombol "Buka di Google Maps" ikut berubah.
        const mapsUrl = 'https://www.google.com/maps?q=' + encodeURIComponent(value);
        document.querySelectorAll(`[data-map-src="${key}"]`).forEach(el => {
          el.src = mapsUrl + '&output=embed';
        });
        document.querySelectorAll(`[data-map-link="${key}"]`).forEach(el => {
          el.href = mapsUrl;
        });
      });
    }catch(err){
      console.error('Gagal memuat pengaturan teks halaman:', err);
    }
  })();

  /* ---------- Slideshow foto background hero (fade bergantian) ---------- */
  (function bp2tlHeroSlideshow(){
    const slides = document.querySelectorAll('.hero-bg-slide');
    if(slides.length < 2) return;
    let current = 0;
    setInterval(() => {
      slides[current].classList.remove('active');
      current = (current + 1) % slides.length;
      slides[current].classList.add('active');
    }, 5000);
  })();

  /* ---------- Mode gelap ---------- */
  (function bp2tlThemeToggle(){
    const btn = document.getElementById('themeToggle');
    if(!btn) return;
    const root = document.documentElement;

    function syncButton(theme){
      const isDark = theme === 'dark';
      btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
      btn.setAttribute('aria-label', isDark ? 'Ganti ke mode terang' : 'Ganti ke mode gelap');
    }

    // Tema awal sudah diset oleh skrip di <head>; sinkronkan tampilan tombol saja.
    syncButton(root.getAttribute('data-theme') || 'light');

    btn.addEventListener('click', () => {
      const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      try{ localStorage.setItem('bp2tl-theme', next); }catch(e){}
      syncButton(next);
    });
  })();

  /* ---------- Navigasi mobile ---------- */
  const navToggle = document.getElementById('navToggle');
  const mainNav = document.getElementById('mainNav');
  navToggle.addEventListener('click', () => {
    const open = mainNav.classList.toggle('open');
    navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  mainNav.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', () => mainNav.classList.remove('open'));
  });

  /* ---------- Tab: Ajukan Pertanyaan / Lacak Pertanyaan ---------- */
  const tabButtons = document.querySelectorAll('.tab-btn');
  const tabPanels = document.querySelectorAll('.tab-panel');
  function activateTab(name){
    tabButtons.forEach(b => b.classList.toggle('active', b.dataset.tab === name));
    tabPanels.forEach(p => p.classList.toggle('active', p.id === 'panel-' + name));
  }
  tabButtons.forEach(btn => {
    btn.addEventListener('click', () => activateTab(btn.dataset.tab));
  });

  document.querySelectorAll('[data-goto-tab]').forEach(el => {
    el.addEventListener('click', (e) => {
      e.preventDefault();
      const target = document.getElementById('tanya');
      activateTab(el.dataset.gotoTab);
      target.scrollIntoView({ behavior:'smooth', block:'start' });
      if(el.dataset.gotoTab === 'lacak' && el.dataset.prefillCode){
        document.getElementById('trackCode').value = el.dataset.prefillCode;
      }
    });
  });

  /* Dipakai chatbot.js untuk membuka tab Ajukan dan mengisi pertanyaan */
  window.bp2tlOpenAskWithText = function(text){
    activateTab('ajukan');
    document.getElementById('tanya').scrollIntoView({ behavior:'smooth', block:'start' });
    document.getElementById('askPertanyaan').value = text;
    document.getElementById('askNama').focus();
  };
  window.bp2tlOpenTrackWithCode = function(code){
    activateTab('lacak');
    document.getElementById('tanya').scrollIntoView({ behavior:'smooth', block:'start' });
    document.getElementById('trackCode').value = code;
  };

  /* ---------- Helper validasi field (tampilkan pesan "Mohon isi dahulu") ---------- */
  function showFieldError(inputId){
    const input = document.getElementById(inputId);
    const error = document.getElementById(inputId + 'Error');
    if(input) input.classList.add('invalid');
    if(error) error.classList.add('show');
  }
  function clearFieldError(inputId){
    const input = document.getElementById(inputId);
    const error = document.getElementById(inputId + 'Error');
    if(input) input.classList.remove('invalid');
    if(error) error.classList.remove('show');
  }
  // Begitu pengguna mulai mengisi ulang field yang tadi error, pesan errornya
  // langsung hilang (tidak perlu menunggu submit ulang untuk tahu sudah benar).
  function clearFieldErrorOnInput(inputId){
    const input = document.getElementById(inputId);
    if(!input) return;
    input.addEventListener('input', () => clearFieldError(inputId));
    input.addEventListener('change', () => clearFieldError(inputId));
  }

  /* ---------- Form: Ajukan Pertanyaan ---------- */
  const askForm = document.getElementById('askForm');
  const ticketResult = document.getElementById('ticketResult');
  const askSubmitBtn = document.getElementById('askSubmitBtn');

  ['askNama', 'askKontak', 'askKategori', 'askPertanyaan'].forEach(clearFieldErrorOnInput);

  askForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const nama = document.getElementById('askNama').value.trim();
    const kontak = document.getElementById('askKontak').value.trim();
    const kategori = document.getElementById('askKategori').value;
    const pertanyaan = document.getElementById('askPertanyaan').value.trim();

    ['askNama', 'askKontak', 'askKategori', 'askPertanyaan'].forEach(clearFieldError);

    let firstInvalid = null;
    if(!nama){ showFieldError('askNama'); firstInvalid = firstInvalid || 'askNama'; }
    if(!kontak){ showFieldError('askKontak'); firstInvalid = firstInvalid || 'askKontak'; }
    if(!kategori){ showFieldError('askKategori'); firstInvalid = firstInvalid || 'askKategori'; }
    if(!pertanyaan){ showFieldError('askPertanyaan'); firstInvalid = firstInvalid || 'askPertanyaan'; }

    if(firstInvalid){
      document.getElementById(firstInvalid).focus();
      return;
    }

    askSubmitBtn.disabled = true;
    askSubmitBtn.textContent = 'Mengirim...';
    const res = await bp2tlAjukan({ nama, kontak, kategori, pertanyaan });
    askSubmitBtn.disabled = false;
    askSubmitBtn.textContent = 'Kirim Pertanyaan';

    if(!res.success){
      ticketResult.innerHTML = `<div class="notice notice-error">${bp2tlEscapeHtml(res.message || 'Gagal mengirim pertanyaan. Coba lagi.')}</div>`;
      return;
    }

    // Sudah ada pertanyaan serupa yang terjawab: tampilkan jawabannya, tanpa tiket baru
    if(res.terjawab){
      renderAnswered(ticketResult, res, { nama, kontak, kategori, pertanyaan });
      ticketResult.scrollIntoView({ behavior:'smooth', block:'nearest' });
      return;
    }

    // Ada beberapa pertanyaan mirip: tampilkan beserta jawabannya, tiket hanya kalau pengguna memilih
    if(res.mirip && Array.isArray(res.saran) && res.saran.length){
      renderSimilar(ticketResult, res.saran, { nama, kontak, kategori, pertanyaan });
      ticketResult.scrollIntoView({ behavior:'smooth', block:'nearest' });
      return;
    }

    renderTicket(ticketResult, res.code, 'pertanyaan');
    askForm.reset();
    ticketResult.scrollIntoView({ behavior:'smooth', block:'nearest' });
  });

  function renderTicket(container, code, jenis){
    container.innerHTML = `
      <div class="ticket" role="status">
        <div class="ticket-top">
          <span class="label">Kode tiket ${jenis === 'pengaduan' ? 'pengaduan' : 'pertanyaan'} Anda</span>
          <div class="ticket-code-row">
            <span class="ticket-code" id="ticketCodeText-${code}">${code}</span>
            <button type="button" class="btn btn-ghost btn-sm" data-copy-code="${code}">Salin kode</button>
          </div>
        </div>
        <div class="ticket-perf"></div>
        <div class="ticket-bottom">
          <p>Simpan kode ini untuk melacak status jawaban dari admin BP2TL kapan saja.</p>
        </div>
      </div>
    `;
    container.querySelector('[data-copy-code]').addEventListener('click', (e) => copyCode(e.currentTarget.dataset.copyCode, e.currentTarget));
  }

  function renderSimilar(container, list, payload){
    const forceLabel = 'Tidak ada yang sesuai — buat tiket untuk pertanyaan saya';
    container.innerHTML = `
      <div class="answered" role="status">
        <span class="answered-badge">Ada pertanyaan yang mirip</span>
        <p class="answered-lead">Kami menemukan pertanyaan yang mungkin sama dengan yang Anda maksud. Klik untuk melihat jawabannya.</p>
        <div class="similar-list">
          ${list.map(it => `
            <details class="similar-item">
              <summary>${bp2tlEscapeHtml(it.pertanyaan || '')}</summary>
              <div class="answered-a">${bp2tlEscapeHtml(it.jawaban || '')}</div>
            </details>
          `).join('')}
        </div>
        <div class="answered-actions">
          <button type="button" class="btn btn-ghost btn-sm" data-force-ask>${forceLabel}</button>
        </div>
      </div>
    `;
    const forceBtn = container.querySelector('[data-force-ask]');
    forceBtn.addEventListener('click', async () => {
      forceBtn.disabled = true;
      forceBtn.textContent = 'Mengirim...';
      const r = await bp2tlAjukan({ ...payload, paksa:true });
      if(!r.success){
        forceBtn.disabled = false;
        forceBtn.textContent = forceLabel;
        container.insertAdjacentHTML('beforeend', `<div class="notice notice-error">${bp2tlEscapeHtml(r.message || 'Gagal mengirim pertanyaan. Coba lagi.')}</div>`);
        return;
      }
      renderTicket(container, r.code, 'pertanyaan');
      askForm.reset();
    });
  }

  function renderAnswered(container, res, payload){
    const forceLabel = 'Jawaban ini belum sesuai — tetap ajukan pertanyaan saya';
    container.innerHTML = `
      <div class="answered" role="status">
        <span class="answered-badge">Sudah ada jawabannya</span>
        <p class="answered-lead">Pertanyaan Anda mirip dengan pertanyaan yang sudah terjawab, jadi tidak perlu membuat tiket baru.</p>
        <div class="answered-q"><span class="label">Pertanyaan terkait</span>${bp2tlEscapeHtml(res.pertanyaan_terkait || '')}</div>
        <div class="answered-a">${bp2tlEscapeHtml(res.jawaban || '')}</div>
        <div class="answered-actions">
          <button type="button" class="btn btn-ghost btn-sm" data-force-ask>${forceLabel}</button>
        </div>
      </div>
    `;
    const forceBtn = container.querySelector('[data-force-ask]');
    forceBtn.addEventListener('click', async () => {
      forceBtn.disabled = true;
      forceBtn.textContent = 'Mengirim...';
      const r = await bp2tlAjukan({ ...payload, paksa:true });
      if(!r.success){
        forceBtn.disabled = false;
        forceBtn.textContent = forceLabel;
        container.insertAdjacentHTML('beforeend', `<div class="notice notice-error">${bp2tlEscapeHtml(r.message || 'Gagal mengirim pertanyaan. Coba lagi.')}</div>`);
        return;
      }
      renderTicket(container, r.code, 'pertanyaan');
      askForm.reset();
    });
  }

  function copyCode(code, btn){
    navigator.clipboard.writeText(code).then(() => {
      showToastLike(btn, 'Tersalin!');
    }).catch(() => {
      const ta = document.createElement('textarea');
      ta.value = code;
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      document.body.removeChild(ta);
      showToastLike(btn, 'Tersalin!');
    });
  }

  function showToastLike(btn, text){
    if(!btn) return;
    const original = btn.textContent;
    btn.textContent = text;
    setTimeout(() => { btn.textContent = original; }, 1500);
  }

  /* ---------- Form: Pengaduan ---------- */
  const complaintForm = document.getElementById('complaintForm');
  const complaintResult = document.getElementById('complaintResult');
  const complaintSubmitBtn = document.getElementById('complaintSubmitBtn');

  ['complaintNama', 'complaintKontak', 'complaintIsi'].forEach(clearFieldErrorOnInput);

  complaintForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const nama = document.getElementById('complaintNama').value.trim();
    const kontak = document.getElementById('complaintKontak').value.trim();
    const isi = document.getElementById('complaintIsi').value.trim();

    ['complaintNama', 'complaintKontak', 'complaintIsi'].forEach(clearFieldError);

    let firstInvalid = null;
    if(!nama){ showFieldError('complaintNama'); firstInvalid = firstInvalid || 'complaintNama'; }
    if(!kontak){ showFieldError('complaintKontak'); firstInvalid = firstInvalid || 'complaintKontak'; }
    if(!isi){ showFieldError('complaintIsi'); firstInvalid = firstInvalid || 'complaintIsi'; }

    if(firstInvalid){
      document.getElementById(firstInvalid).focus();
      return;
    }

    complaintSubmitBtn.disabled = true;
    complaintSubmitBtn.textContent = 'Mengirim...';
    const res = await bp2tlAjukan({ nama, kontak, kategori:'Pengaduan Layanan', pertanyaan: isi });
    complaintSubmitBtn.disabled = false;
    complaintSubmitBtn.textContent = 'Kirim Pengaduan';

    if(!res.success){
      complaintResult.innerHTML = `<div class="notice notice-error">${bp2tlEscapeHtml(res.message || 'Gagal mengirim pengaduan. Coba lagi.')}</div>`;
      return;
    }

    renderTicket(complaintResult, res.code, 'pengaduan');
    complaintForm.reset();
    complaintResult.scrollIntoView({ behavior:'smooth', block:'nearest' });
  });

  /* ---------- Form: Lacak Pertanyaan ---------- */
  const trackForm = document.getElementById('trackForm');
  const trackResult = document.getElementById('trackResult');
  const trackSubmitBtn = document.getElementById('trackSubmitBtn');

  trackForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const code = document.getElementById('trackCode').value.trim();
    if(!code){ return; }

    trackSubmitBtn.disabled = true;
    trackSubmitBtn.textContent = 'Memeriksa...';
    const res = await bp2tlLacak(code);
    trackSubmitBtn.disabled = false;
    trackSubmitBtn.textContent = 'Cek Status';

    if(!res.success){
      trackResult.innerHTML = `
        <div class="notice notice-error">
          ${bp2tlEscapeHtml(res.message || 'Kode tiket tidak ditemukan.')}
        </div>`;
      return;
    }

    const item = res.data;
    const statusBadge = item.status === 'dijawab'
      ? '<span class="status-badge status-dijawab">Sudah dijawab</span>'
      : '<span class="status-badge status-menunggu">Menunggu jawaban</span>';

    const answerHtml = item.status === 'dijawab'
      ? `<div class="answer-box">
          <div class="who">Jawaban admin BP2TL &middot; ${bp2tlFormatDate(item.tanggal_jawab)}</div>
          <p style="margin:0;">${bp2tlEscapeHtml(item.jawaban)}</p>
        </div>`
      : `<div class="notice notice-info">Pertanyaan Anda sedang diproses oleh admin. Silakan cek kembali secara berkala.</div>`;

    trackResult.innerHTML = `
      <div class="result-card">
        <div class="meta">
          <span class="code">${item.code}</span>
          ${statusBadge}
        </div>
        <p style="margin:0 0 4px; font-size:13.5px; color:var(--slate-500);">Diajukan pada ${bp2tlFormatDate(item.tanggal)} &middot; Kategori: ${bp2tlEscapeHtml(item.kategori)}</p>
        <p style="margin:0; font-weight:600;">${bp2tlEscapeHtml(item.pertanyaan)}</p>
        ${answerHtml}
      </div>
    `;
  });

  /* ---------- Pra-isi kode lacak dari URL (?kode=...) ---------- */
  const params = new URLSearchParams(window.location.search);
  if(params.get('kode')){
    window.bp2tlOpenTrackWithCode(params.get('kode'));
  }

  /* ---------- FAQ accordion ---------- */
  document.querySelectorAll('.faq-item').forEach(item => {
    const btn = item.querySelector('.faq-q');
    const answer = item.querySelector('.faq-a');
    btn.addEventListener('click', () => {
      const isOpen = item.classList.contains('open');
      document.querySelectorAll('.faq-item.open').forEach(other => {
        if(other !== item){
          other.classList.remove('open');
          other.querySelector('.faq-a').style.maxHeight = null;
          other.querySelector('.faq-q').setAttribute('aria-expanded','false');
        }
      });
      item.classList.toggle('open', !isOpen);
      btn.setAttribute('aria-expanded', (!isOpen).toString());
      answer.style.maxHeight = !isOpen ? answer.scrollHeight + 'px' : null;
    });
  });
});

/* =========================================================
   Animasi halaman utama: muncul saat di-scroll
   ========================================================= */
document.addEventListener('DOMContentLoaded', () => {
  /* ---------- Reveal on scroll ---------- */
  if(!('IntersectionObserver' in window)) return;

  const targets = [];
  const add = (selector, step) => {
    document.querySelectorAll(selector).forEach((el, i) => {
      el.classList.add('reveal');
      el.style.setProperty('--rd', (i * step) + 'ms');
      targets.push(el);
    });
  };
  add('main .section-head', 0);
  add('.tanya-card', 0);
  add('.faq-item', 80);
  add('.peta-map, .peta-info', 150);
  add('.footer-grid > div', 120);

  const done = (el) => {
    el.classList.remove('reveal', 'in');
    el.style.removeProperty('--rd');
  };

  const io = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if(!entry.isIntersecting) return;
      const el = entry.target;
      io.unobserve(el);
      el.classList.add('in');
      /* bersihkan kelas setelah selesai supaya transisi bawaan elemen (hover, dll) normal lagi */
      const delay = parseInt(el.style.getPropertyValue('--rd'), 10) || 0;
      setTimeout(() => done(el), delay + 900);
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

  targets.forEach(el => io.observe(el));
});