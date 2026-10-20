/* =========================================================
   BP2TL Jakarta — fx.js
   Lapisan tampilan interaktif untuk halaman Admin & Super Admin.
   Dimuat SETELAH api.js dan SEBELUM admin.js / superadmin.js.

   File ini menambah (tanpa mengubah logika admin.js/superadmin.js):
   - Latar laut animasi di layar login (kapal, mercusuar, ombak)
   - Banner sapaan di dashboard (salam sesuai jam, tanggal, ringkasan)
   - Kartu statistik: ikon + animasi hitung naik (+ klik untuk memfilter, khusus Admin)
   - Bar loading bergambar kapal saat server lambat merespons
   - Admin: badge jumlah tiket menunggu, judul tab "(3) ...", bunyi lonceng kapal
   - Admin: "Balasan cepat" (template jawaban) di jendela jawab

   Semua animasi mati otomatis bila perangkat memakai "kurangi gerakan".
   ========================================================= */
(function(){
  'use strict';

  const REALM = window.BP2TL_REALM || 'admin';
  const REDUCED = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

  const $  = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.prototype.slice.call(r.querySelectorAll(s));
  const store = {
    get(k){ try{ return window.localStorage.getItem(k); }catch(e){ return null; } },
    set(k, v){ try{ window.localStorage.setItem(k, v); }catch(e){ /* abaikan */ } },
  };

  /* =========================================================
     1) ILUSTRASI: kapal & ombak (SVG)
     ========================================================= */

  /** Jalur gelombang sinus yang bisa diulang mulus (lebar = kelipatan period). */
  function wavePath(y, amp, period, width, bottom){
    let d = 'M0 ' + y;
    for(let x = 0; x < width; x += period){
      d += ' Q' + (x + period / 4) + ' ' + (y - amp) + ' ' + (x + period / 2) + ' ' + y +
           ' T' + (x + period) + ' ' + y;
    }
    return d + ' V' + bottom + ' H0 Z';
  }

  const PAL_NIGHT = {
    hull: '#03111D', bridge: '#061A2C', cont: ['#061A2C', '#0A2438', '#08203A'],
    win: '#F5A623', winOpacity: .9,
  };
  const PAL_LIGHT = {
    hull: 'rgba(255,255,255,.20)', bridge: 'rgba(255,255,255,.24)',
    cont: ['rgba(255,255,255,.10)', 'rgba(255,255,255,.16)', 'rgba(255,255,255,.13)'],
    win: '#F5A623', winOpacity: 1,
  };

  /** Kapal kontainer menghadap kanan. Garis air di y=0, lebar ±240. */
  function shipBody(p){
    let c = '';
    for(let r = 0; r < 3; r++){
      const n = 7 - r;
      for(let i = 0; i < n; i++){
        c += '<rect x="' + (14 + i * 22) + '" y="' + (-26 - r * 12) + '" width="20" height="10" fill="' + p.cont[(i + r) % 3] + '"/>';
      }
    }
    let w = '';
    for(let i = 0; i < 4; i++){
      w += '<rect x="' + (182 + i * 6.5) + '" y="-62" width="4" height="3" fill="' + p.win + '" opacity="' + p.winOpacity + '"/>';
    }
    return '<path d="M0 -16 H240 L222 8 H22 Z" fill="' + p.hull + '"/>' + c +
      '<rect x="178" y="-70" width="30" height="54" fill="' + p.bridge + '"/>' +
      '<rect x="174" y="-78" width="38" height="10" fill="' + p.bridge + '"/>' + w +
      '<circle cx="193" cy="-83" r="2" fill="#fff" opacity=".9"/>' +
      '<circle cx="237" cy="-19" r="2.2" fill="#E04B3C"/>';
  }

  /** Pemandangan malam: langit berbintang, bulan, kapal, mercusuar, ombak. */
  function buildScene(){
    const stars = [
      [120,70],[260,140],[380,60],[520,110],[640,40],[760,150],[880,80],[1000,45],[1120,120],[1180,60],
      [1420,90],[1500,200],[60,210],[330,230],[700,250],[960,210],[1240,240],[1540,60],[430,300],[820,310]
    ].map(function(s, i){
      return '<circle class="fx-star" cx="' + s[0] + '" cy="' + s[1] + '" r="' + (i % 3 ? 1.1 : 1.7) +
             '" fill="#fff" style="animation-delay:' + (i * 0.37).toFixed(2) + 's"/>';
    }).join('');

    const ripple = [[1200,578,100],[1215,586,70],[1190,596,120],[1225,606,60]].map(function(r, i){
      return '<rect class="fx-shimmer" x="' + r[0] + '" y="' + r[1] + '" width="' + r[2] + '" height="2.5" rx="1.2" fill="#F5A623" style="animation-delay:' + (i * 0.6) + 's"/>';
    }).join('');

    return '' +
    '<svg viewBox="0 0 1600 900" preserveAspectRatio="xMidYMax slice" xmlns="http://www.w3.org/2000/svg" focusable="false">' +
      '<defs>' +
        '<linearGradient id="fxSky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#04182A"/><stop offset=".62" stop-color="#0E3552"/><stop offset="1" stop-color="#1F5A80"/></linearGradient>' +
        '<linearGradient id="fxSea" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#123A5C"/><stop offset="1" stop-color="#051B2D"/></linearGradient>' +
        '<radialGradient id="fxMoonGlow"><stop offset="0" stop-color="#F5A623" stop-opacity=".34"/><stop offset="1" stop-color="#F5A623" stop-opacity="0"/></radialGradient>' +
        '<radialGradient id="fxLamp"><stop offset="0" stop-color="#FFE9A8" stop-opacity=".95"/><stop offset="1" stop-color="#FFE9A8" stop-opacity="0"/></radialGradient>' +
        '<linearGradient id="fxBeam" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#FFE9A8" stop-opacity=".55"/><stop offset="1" stop-color="#FFE9A8" stop-opacity="0"/></linearGradient>' +
      '</defs>' +
      '<rect width="1600" height="900" fill="url(#fxSky)"/>' +
      stars +
      '<circle cx="1250" cy="170" r="170" fill="url(#fxMoonGlow)"/>' +
      '<circle cx="1250" cy="170" r="44" fill="#FFF4D6" opacity=".95"/>' +
      '<ellipse cx="800" cy="560" rx="900" ry="80" fill="#F5A623" opacity=".07"/>' +
      '<rect y="560" width="1600" height="340" fill="url(#fxSea)"/>' +
      ripple +
      /* kapal jauh di cakrawala (ke kanan) */
      '<g class="fx-ship fx-ship-far"><g transform="translate(0 566) scale(.5)"><g class="fx-bob">' + shipBody(PAL_NIGHT) + '</g></g></g>' +
      /* kapal dekat (ke kiri) */
      '<g class="fx-ship fx-ship-near"><g transform="translate(300 650) scale(-1 1)"><g class="fx-bob fx-bob-2">' + shipBody(PAL_NIGHT) + '</g></g></g>' +
      /* ombak belakang & tengah */
      '<g class="fx-wave fx-wave-1"><path d="' + wavePath(655, 10, 400, 3200, 900) + '" fill="#123A5C" opacity=".5"/></g>' +
      '<g class="fx-wave fx-wave-2"><path d="' + wavePath(725, 14, 400, 3200, 900) + '" fill="#0A2A43" opacity=".78"/></g>' +
      /* mercusuar berdiri di atas batu karang (di depan ombak tengah, di belakang ombak depan) */
      '<g transform="translate(210 0)">' +
        '<polygon class="fx-beam" points="0,501 1100,425 1100,577" fill="url(#fxBeam)"/>' +
        '<path d="M-460 900 L-460 790 L-190 772 Q-120 722 -30 730 Q40 737 100 792 L172 900 Z" fill="#031220"/>' +
        '<polygon points="-11,520 11,520 18.5,764 -18.5,764" fill="#B9C7D2"/>' +
        '<polygon points="-12.8,580 12.8,580 14,620 -14,620" fill="#B3402F" opacity=".85"/>' +
        '<polygon points="-14.9,650 14.9,650 16.1,690 -16.1,690" fill="#B3402F" opacity=".85"/>' +
        '<rect x="-15" y="512" width="30" height="8" fill="#0A2438"/>' +
        '<rect x="-9" y="490" width="18" height="22" fill="#FFE9A8"/>' +
        '<polygon points="-12,490 0,470 12,490" fill="#0A2438"/>' +
        '<circle class="fx-lamp" cx="0" cy="501" r="34" fill="url(#fxLamp)"/>' +
      '</g>' +
      /* ombak depan */
      '<g class="fx-wave fx-wave-3"><path d="' + wavePath(808, 18, 400, 3200, 900) + '" fill="#051B2D" opacity=".96"/></g>' +
    '</svg>';
  }

  function injectLoginScene(){
    const ls = $('#loginScreen');
    if(!ls || $('.fx-scene', ls)) return;
    ls.insertAdjacentHTML('afterbegin', '<div class="fx-scene" aria-hidden="true">' + buildScene() + '</div>');
  }

  /* =========================================================
     1b) LATAR LOGIN: FOTO BERGANTI-GANTI
         (menggantikan ilustrasi laut; ubah mode ke 'scene' untuk kembali)
     ========================================================= */
  const LOGIN_BG = {
    mode: 'photo',     // 'photo' = hanya foto bergantian  |  'scene' = ilustrasi laut animasi
    photos: [  'img/login/1.jpg',
                'img/login/2.jpg',
                'img/login/3.jpg',
    ],                 // dibiarkan kosong -> otomatis memakai foto hero beranda (Admin > Pengaturan)
    interval: 7000,    // jeda antar pergantian foto (milidetik)
  };

  /** Alamat relatif (img/login/1.jpg) diberi awalan folder aplikasi; URL penuh dipakai apa adanya. */
  function photoUrl(p){
    if(/^(https?:)?\/\//i.test(p) || p.charAt(0) === '/') return p;
    const base = String(window.BP2TL_API_BASE || '/api').replace(/\/api\/?$/, '');
    return base + '/' + p;
  }

    function injectLoginBackground(){
    const ls = $('#loginScreen');
    if(!ls || $('.fx-photos', ls)) return;
    if(LOGIN_BG.mode !== 'photo'){ if(!$('.fx-scene', ls)) injectLoginScene(); return; }

    /* Bila ilustrasi laut sudah terlanjur terpasang (mis. pemanggil lama masih ada), buang dulu
       supaya tidak menutupi foto. */
    $$('.fx-scene', ls).forEach(function(el){ el.remove(); });

    const wrap = document.createElement('div');
    wrap.className = 'fx-photos';
    wrap.setAttribute('aria-hidden', 'true');
    ls.insertBefore(wrap, ls.firstChild);

    /* Tidak ada foto yang bisa dimuat -> kembali ke ilustrasi laut agar layar tidak polos. */
    const fallback = function(alasan){
      console.warn('[fx] Latar foto tidak dipakai, kembali ke ilustrasi laut. Penyebab: ' + alasan);
      wrap.remove();
      injectLoginScene();
    };

    function run(list){
      list.forEach(function(u, i){
        const s = document.createElement('div');
        s.className = 'fx-photo' + (i === 0 ? ' is-on' : '');
        s.style.backgroundImage = 'url("' + u.replace(/"/g, '%22') + '")';
        wrap.appendChild(s);
      });
      if(list.length < 2 || REDUCED) return;       // satu foto / "kurangi gerakan": diam saja
      let cur = 0;
      setInterval(function(){
        if(document.hidden || window.getComputedStyle(ls).display === 'none') return;   // layar login tak terlihat
        const slides = wrap.children;
        slides[cur].classList.remove('is-on');
        cur = (cur + 1) % slides.length;
        slides[cur].classList.add('is-on');
      }, LOGIN_BG.interval);
    }

    /* Muat dulu semua foto; yang gagal dimuat (link mati / diblokir) dibuang. */
    function start(urls){
      let pending = urls.length;
      const ok = [];
      const done = function(){
        if(--pending > 0) return;
        const list = ok.filter(Boolean);
        if(list.length) run(list); else fallback('semua foto gagal dimuat (cek nama file, ekstensi, dan folder public/img/login)');
      };
      urls.forEach(function(u, i){
        const img = new Image();
        img.onload  = function(){ ok[i] = u; done(); };
        img.onerror = function(){ console.warn('[fx] Foto gagal dimuat: ' + u); done(); };
        img.src = u;
      });
    }

    if(LOGIN_BG.photos.length){ start(LOGIN_BG.photos.map(photoUrl)); return; }

    /* Tanpa daftar sendiri: pakai foto hero beranda (hero_photo_1..4 dari Pengaturan). */
    if(typeof window.bp2tlSettingsGet !== 'function'){ fallback('api.js belum dimuat sebelum fx.js'); return; }
    window.bp2tlSettingsGet().then(function(res){
      const d = (res && res.data) || {};
      const urls = [1, 2, 3, 4].map(function(i){ return d['hero_photo_' + i]; })
        .filter(function(u){ return typeof u === 'string' && u.trim() !== ''; });
      if(urls.length) start(urls); else fallback('daftar photos kosong dan Pengaturan > Hero belum berisi foto');
    }).catch(function(e){ fallback('gagal mengambil Pengaturan (' + (e && e.message ? e.message : e) + ')'); });
  }

  /* =========================================================
     2) BAR LOADING BERGAMBAR KAPAL
        Hanya muncul bila permintaan ke /api/ lebih lama dari 350 ms,
        sehingga polling cepat setiap 5 detik tidak membuatnya berkedip.
     ========================================================= */
  function setupShipBar(){
    const bar = document.createElement('div');
    bar.className = 'fx-shipbar';
    bar.setAttribute('aria-hidden', 'true');
    bar.innerHTML =
      '<span class="fx-shipbar-fill"></span>' +
      '<svg class="fx-shipbar-ship" viewBox="-6 -92 264 104" width="30" height="12">' + shipBody({
        hull: '#F5A623', bridge: '#F5A623', cont: ['#F5A623', '#E09416', '#F5A623'], win: '#0A2A43', winOpacity: 1,
      }) + '</svg>';
    document.body.appendChild(bar);

    let inflight = 0;
    let timer = null;
    const nativeFetch = window.fetch.bind(window);

    window.fetch = function(input){
      const url = String((input && input.url) || input || '');
      if(url.indexOf('/api/') === -1) return nativeFetch.apply(null, arguments);

      inflight++;
      if(inflight === 1 && !timer){
        timer = setTimeout(function(){ bar.classList.add('on'); }, 350);
      }
      const done = function(){
        inflight = Math.max(0, inflight - 1);
        if(inflight === 0){
          clearTimeout(timer);
          timer = null;
          bar.classList.remove('on');
        }
      };
      return nativeFetch.apply(null, arguments).finally(done);
    };
  }

  /* =========================================================
     3) KARTU STATISTIK: ikon, hitung naik, klik untuk filter
     ========================================================= */
  const ICONS = {
    list:      '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
    hourglass: '<path d="M6 2h12M6 22h12"/><path d="M8 2v4.5a4 4 0 0 0 1.6 3.2L12 12l-2.4 2.3A4 4 0 0 0 8 17.5V22M16 2v4.5a4 4 0 0 1-1.6 3.2L12 12l2.4 2.3a4 4 0 0 1 1.6 3.2V22"/>',
    check:     '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.7 2.7L16 9.5"/>',
    calendar:  '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
    login:     '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>',
    book:      '<path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v18H6.5A2.5 2.5 0 0 0 4 22.5z"/><path d="M4 4.5V22"/>',
  };

  /* id elemen angka -> ikon, warna, dan (opsional) filter status tabel */
  const STAT_MAP = {
    statTotal:        { ico: 'list',      tone: 'navy',  filter: 'semua' },
    statMenunggu:     { ico: 'hourglass', tone: 'gold',  filter: 'menunggu' },
    statDijawab:      { ico: 'check',     tone: 'green', filter: 'dijawab' },
    statHariIni:      { ico: 'calendar',  tone: 'blue' },
    logStatTotal:     { ico: 'list',      tone: 'navy' },
    logStatHariIni:   { ico: 'calendar',  tone: 'blue' },
    logStatLogin:     { ico: 'login',     tone: 'gold' },
    historyStatTotal: { ico: 'book',      tone: 'green' },
  };

  /** Animasi angka naik/turun setiap kali admin.js / superadmin.js menulis angka baru. */
  function watchNumber(el){
    let raf = 0;
    el.__fxShown = parseInt(el.textContent, 10) || 0;
    el.__fxLast = undefined;

    function write(v){
      el.__fxLast = String(v);
      el.__fxShown = v;
      el.textContent = v;
    }

    new MutationObserver(function(){
      const txt = el.textContent.trim();
      if(txt === el.__fxLast) return;               // tulisan kita sendiri
      if(!/^\d+$/.test(txt)){ el.__fxShown = 0; return; }

      const target = parseInt(txt, 10);
      const from = el.__fxShown || 0;
      cancelAnimationFrame(raf);
      if(REDUCED || target === from){ el.__fxShown = target; el.__fxLast = undefined; return; }

      write(from);                                   // kembalikan dulu agar tidak "berkedip" ke angka akhir
      const t0 = performance.now();
      const dur = Math.min(900, 380 + Math.abs(target - from) * 12);
      (function step(now){
        const k = Math.min(1, (now - t0) / dur);
        const eased = 1 - Math.pow(1 - k, 3);
        write(Math.round(from + (target - from) * eased));
        if(k < 1) raf = requestAnimationFrame(step);
      })(t0);
    }).observe(el, { childList: true, characterData: true, subtree: true });
  }

  function decorateStats(){
    const statusFilter = $('#statusFilter');

    Object.keys(STAT_MAP).forEach(function(id){
      const el = document.getElementById(id);
      const cfg = STAT_MAP[id];
      const card = el && el.closest('.stat-card');
      if(!card || card.__fxDone) return;
      card.__fxDone = true;

      card.classList.add('stat-card--fx', 'tone-' + cfg.tone);
      card.insertAdjacentHTML('afterbegin',
        '<span class="stat-ico" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
        ICONS[cfg.ico] + '</svg></span>');
      watchNumber(el);

      if(cfg.filter && statusFilter){
        card.dataset.filter = cfg.filter;
        card.setAttribute('role', 'button');
        card.setAttribute('tabindex', '0');
        card.setAttribute('title', 'Klik untuk menampilkan: ' + card.querySelector('.lbl').textContent);
        const apply = function(){
          statusFilter.value = cfg.filter;
          statusFilter.dispatchEvent(new Event('change', { bubbles: true }));   // admin.js sudah mendengarkan ini
          const tbl = $('.table-wrap');
          if(tbl && tbl.scrollIntoView) tbl.scrollIntoView({ behavior: REDUCED ? 'auto' : 'smooth', block: 'start' });
        };
        card.addEventListener('click', apply);
        card.addEventListener('keydown', function(e){
          if(e.key === 'Enter' || e.key === ' '){ e.preventDefault(); apply(); }
        });
      }
    });

    if(statusFilter){
      const sync = function(){
        $$('.stat-card[data-filter]').forEach(function(c){
          c.classList.toggle('is-active', c.dataset.filter === statusFilter.value);
        });
      };
      statusFilter.addEventListener('change', sync);
      sync();
    }
  }

  /* =========================================================
     4) BANNER SAPAAN DI DASHBOARD
     ========================================================= */
  function salam(){
    const h = new Date().getHours();
    return h < 11 ? 'Selamat pagi' : h < 15 ? 'Selamat siang' : h < 18 ? 'Selamat sore' : 'Selamat malam';
  }

  const HERO = {
    admin: {
      host: '#view-dashboard .admin-shell', source: 'statMenunggu', fallbackName: 'Petugas',
      summary: function(n){
        return n > 0
          ? 'Ada <strong>' + n + '</strong> pertanyaan menunggu jawaban Anda.'
          : 'Semua pertanyaan sudah terjawab. Kerja bagus!';
      },
    },
    superadmin: {
      host: '#view-log .admin-shell', source: 'logStatHariIni', fallbackName: 'Super Admin',
      summary: function(n){
        return n > 0
          ? 'Hari ini tercatat <strong>' + n + '</strong> aktivitas di sistem.'
          : 'Belum ada aktivitas tercatat hari ini.';
      },
    },
  };

  function setupHero(){
    const cfg = HERO[REALM] || HERO.admin;
    const host = $(cfg.host);
    if(!host || $('#fxHero')) return;

    host.insertAdjacentHTML('afterbegin',
      '<div class="dash-hero" id="fxHero">' +
        '<p class="dash-hero-date" data-hero-date></p>' +
        '<h2 class="dash-hero-title"><span data-hero-greet></span>, <span data-hero-name></span></h2>' +
        '<p class="dash-hero-sum" data-hero-sum></p>' +
        '<svg class="dash-hero-ship" viewBox="-6 -92 264 104" aria-hidden="true">' + shipBody(PAL_LIGHT) + '</svg>' +
        '<svg class="dash-hero-wave" viewBox="0 0 1600 30" preserveAspectRatio="none" aria-hidden="true"><path d="' + wavePath(14, 7, 200, 1600, 30) + '"/></svg>' +
      '</div>');

    const hero = $('#fxHero');
    const elDate = $('[data-hero-date]', hero);
    const elGreet = $('[data-hero-greet]', hero);
    const elName = $('[data-hero-name]', hero);
    const elSum = $('[data-hero-sum]', hero);
    const src = document.getElementById(cfg.source);

    function renderSummary(){
      const n = src ? (parseInt(src.textContent, 10) || 0) : 0;
      elSum.innerHTML = cfg.summary(n);
    }
    function renderHead(name){
      elDate.textContent = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
      elGreet.textContent = salam();
      elName.textContent = name || cfg.fallbackName;
    }

    renderHead(cfg.fallbackName);
    renderSummary();
    if(src) new MutationObserver(renderSummary).observe(src, { childList: true, characterData: true, subtree: true });

    /* Nama pengguna: ambil dari sesi setiap kali dashboard tampil (setelah login). */
    const dash = $('#dashboard');
    let wasVisible = false;
    function onVisibility(){
      const visible = !!dash && dash.style.display !== 'none';
      if(visible && !wasVisible && typeof window.bp2tlCheckSession === 'function'){
        renderHead(cfg.fallbackName);
        window.bp2tlCheckSession().then(function(s){
          if(s && s.loggedIn && s.username) renderHead(s.username);
        }).catch(function(){ /* tetap pakai nama bawaan */ });
      }
      wasVisible = visible;
    }
    if(dash){
      new MutationObserver(onVisibility).observe(dash, { attributes: true, attributeFilter: ['style'] });
      onVisibility();
    }
  }

  /* =========================================================
     5) KHUSUS ADMIN: badge tiket menunggu, bunyi lonceng, judul tab
     ========================================================= */
  const baseTitle = document.title;
  let badgeEl = null;
  let soundOn = store.get('bp2tl_fx_sound') === '1';
  let audioCtx = null;

  /** Bunyi lonceng kapal "ding-dong" dibuat langsung dengan WebAudio (tanpa file suara). */
  function ring(force){
    if(!soundOn && !force) return;
    try{
      const AC = window.AudioContext || window.webkitAudioContext;
      if(!AC) return;
      audioCtx = audioCtx || new AC();
      if(audioCtx.state === 'suspended') audioCtx.resume();
      const t = audioCtx.currentTime;
      [[1318.5, 0], [987.8, 0.32]].forEach(function(n){
        const o = audioCtx.createOscillator();
        const g = audioCtx.createGain();
        o.type = 'sine';
        o.frequency.value = n[0];
        g.gain.setValueAtTime(0.0001, t + n[1]);
        g.gain.exponentialRampToValueAtTime(0.2, t + n[1] + 0.02);
        g.gain.exponentialRampToValueAtTime(0.0001, t + n[1] + 1.4);
        o.connect(g); g.connect(audioCtx.destination);
        o.start(t + n[1]);
        o.stop(t + n[1] + 1.5);
      });
    }catch(e){ /* browser memblokir audio: abaikan */ }
  }

  function setupAdminExtras(){
    /* badge di menu Dashboard */
    const link = $('.sidebar-link[data-view="dashboard"]');
    if(link && !$('.nav-badge', link)){
      link.insertAdjacentHTML('beforeend', '<span class="nav-badge" id="navBadgeMenunggu" hidden>0</span>');
    }
    badgeEl = $('#navBadgeMenunggu');

    /* tombol bunyi di topbar */
    const user = $('.admin-user');
    if(user && !$('#fxSoundToggle')){
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.id = 'fxSoundToggle';
      btn.className = 'fx-sound';
      user.insertBefore(btn, $('#logoutBtn', user) || null);
      const paint = function(){
        btn.setAttribute('aria-pressed', soundOn ? 'true' : 'false');
        btn.title = soundOn ? 'Bunyi tiket baru: hidup (klik untuk mematikan)' : 'Bunyi tiket baru: mati (klik untuk menghidupkan)';
        btn.innerHTML =
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
          '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/>' +
          (soundOn ? '' : '<path d="M3 3l18 18"/>') + '</svg><span>' + (soundOn ? 'Bunyi' : 'Senyap') + '</span>';
      };
      paint();
      btn.addEventListener('click', function(){
        soundOn = !soundOn;
        store.set('bp2tl_fx_sound', soundOn ? '1' : '0');
        paint();
        if(soundOn) ring(true);                     // contoh bunyi + membuka izin audio browser
      });
    }
  }

  /* =========================================================
     6) KHUSUS ADMIN: BALASAN CEPAT di jendela jawab
        Catatan: setiap jawaban yang tersimpan juga menjadi bahan jawaban
        chatbot untuk pertanyaan serupa, jadi templat sengaja bersifat
        umum (tanpa nama/data pribadi).
     ========================================================= */
  const TEMPLATES = [
    { judul: 'Standar',
      teks: 'Yth. Bapak/Ibu,\n\nTerima kasih telah menghubungi BP2TL Jakarta.\n\n[isi jawaban]\n\nDemikian informasi yang dapat kami sampaikan. Apabila masih ada hal yang ingin ditanyakan, silakan ajukan kembali melalui portal ini.\n\nHormat kami,\nBP2TL Jakarta' },
    { judul: 'Arahkan ke kantor',
      teks: 'Yth. Bapak/Ibu,\n\nTerima kasih telah menghubungi BP2TL Jakarta.\n\n[isi jawaban]\n\nUntuk informasi lebih lanjut, Bapak/Ibu dapat mengunjungi kantor kami pada jam kerja atau menghubungi kontak resmi yang tercantum di bagian bawah halaman utama.\n\nHormat kami,\nBP2TL Jakarta' },
    { judul: 'Arahkan ke kanal resmi',
      teks: 'Yth. Bapak/Ibu,\n\nTerima kasih telah menghubungi BP2TL Jakarta.\n\nInformasi lengkap mengenai hal tersebut dapat diakses melalui [alamat/kanal resmi].\n\nHormat kami,\nBP2TL Jakarta' },
    { judul: 'Pengaduan diterima',
      teks: 'Yth. Bapak/Ibu,\n\nPengaduan Anda telah kami terima dan sedang kami tindak lanjuti. Mohon maaf atas ketidaknyamanan yang Anda alami.\n\n[tindak lanjut yang dilakukan]\n\nTerima kasih atas masukan Anda untuk perbaikan layanan kami.\n\nHormat kami,\nBP2TL Jakarta' },
  ];
  const TPL_KEY = 'bp2tl_fx_templates';

  function loadCustom(){
    try{
      const arr = JSON.parse(store.get(TPL_KEY) || '[]');
      return Array.isArray(arr) ? arr.filter(function(t){ return t && typeof t.judul === 'string' && typeof t.teks === 'string'; }) : [];
    }catch(e){ return []; }
  }
  function saveCustom(arr){ store.set(TPL_KEY, JSON.stringify(arr.slice(0, 20))); }

  function insertTemplate(ta, teks){
    ta.value = ta.value.trim() === '' ? teks : ta.value.replace(/\s+$/, '') + '\n\n' + teks;
    ta.focus();
    const a = ta.value.indexOf('[');
    if(a >= 0){
      const b = ta.value.indexOf(']', a);
      if(b > a) ta.setSelectionRange(a, b + 1);     // langsung blok bagian [isi ...] agar tinggal diketik
    }
    ta.dispatchEvent(new Event('input', { bubbles: true }));
  }

  function renderQuickReplies(box, ta){
    const custom = loadCustom();
    const chips = TEMPLATES.map(function(t, i){
      return '<button type="button" class="qr-chip" data-qr="d' + i + '">' + esc(t.judul) + '</button>';
    }).concat(custom.map(function(t, i){
      return '<span class="qr-chip qr-custom"><button type="button" data-qr="c' + i + '">' + esc(t.judul) + '</button>' +
             '<button type="button" class="x" data-qr-del="' + i + '" aria-label="Hapus templat ' + esc(t.judul) + '">&times;</button></span>';
    })).join('');

    box.innerHTML =
      '<div class="qr-title">Balasan cepat</div>' +
      '<div class="qr-chips">' + chips + '<button type="button" class="qr-chip qr-save" data-qr-save>+ Simpan jawaban ini sebagai templat</button></div>' +
      '<div class="qr-hint">Jawaban yang tersimpan juga dipakai chatbot untuk pertanyaan serupa &mdash; hindari menulis nama atau data pribadi.</div>';

    box.onclick = function(e){
      const t = e.target.closest('button');
      if(!t) return;
      if(t.hasAttribute('data-qr-save')){
        const teks = ta.value.trim();
        if(!teks){ window.alert('Tulis jawaban dulu di kotak di bawah, lalu simpan sebagai templat.'); return; }
        const judul = (window.prompt('Nama templat (maks. 30 karakter):') || '').trim().slice(0, 30);
        if(!judul) return;
        const arr = loadCustom(); arr.push({ judul: judul, teks: teks }); saveCustom(arr);
        renderQuickReplies(box, ta);
        return;
      }
      if(t.hasAttribute('data-qr-del')){
        const arr = loadCustom();
        const i = parseInt(t.getAttribute('data-qr-del'), 10);
        if(arr[i] && window.confirm('Hapus templat "' + arr[i].judul + '"?')){ arr.splice(i, 1); saveCustom(arr); renderQuickReplies(box, ta); }
        return;
      }
      const key = t.getAttribute('data-qr') || '';
      const idx = parseInt(key.slice(1), 10);
      const tpl = key.charAt(0) === 'd' ? TEMPLATES[idx] : loadCustom()[idx];
      if(tpl) insertTemplate(ta, tpl.teks);
    };
  }

  function esc(s){
    return String(s).replace(/[&<>"']/g, function(c){ return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
  }

  function setupQuickReplies(){
    const body = $('#modalBody');
    if(!body) return;
    new MutationObserver(function(){
      const ta = $('#answerText', body);
      if(!ta || $('.qr-box', body)) return;
      const field = ta.closest('.field') || ta.parentNode;
      const box = document.createElement('div');
      box.className = 'qr-box';
      field.insertBefore(box, ta);
      renderQuickReplies(box, ta);
    }).observe(body, { childList: true });
  }

  /* =========================================================
     7) API PUBLIK untuk admin.js
     ========================================================= */
  window.bp2tlFx = {
    /** Dipanggil admin.js setiap polling: jumlah tiket berstatus "menunggu". */
    setPending: function(n){
      n = parseInt(n, 10) || 0;
      if(badgeEl){
        badgeEl.textContent = n > 99 ? '99+' : String(n);
        badgeEl.hidden = !(n > 0);
      }
      document.title = (n > 0 ? '(' + n + ') ' : '') + baseTitle;
    },
    /** Dipanggil admin.js ketika jumlah tiket menunggu bertambah sejak polling sebelumnya. */
    notifyNewTickets: function(){ ring(false); },
  };

  /* =========================================================
     Mulai
     ========================================================= */
  document.addEventListener('DOMContentLoaded', function(){
     injectLoginBackground();  
    setupShipBar();
    decorateStats();
    setupHero();
    if(REALM === 'admin'){
      setupAdminExtras();
      setupQuickReplies();
    }
  });
})();