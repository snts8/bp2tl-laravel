/* =========================================================
   BP2TL Jakarta — admin.js
   Login dan data dashboard sekarang melalui backend PHP + sesi
   server (lihat api/login.php, api/session_check.php, dll),
   bukan lagi localStorage/sessionStorage.
   ========================================================= */

document.addEventListener('DOMContentLoaded', async () => {
  const loginScreen = document.getElementById('loginScreen');
  const dashboard = document.getElementById('dashboard');
  const loginForm = document.getElementById('loginForm');
  const loginError = document.getElementById('loginError');
  const loginSubmitBtn = document.getElementById('loginSubmitBtn');
  const logoutBtn = document.getElementById('logoutBtn');

  /* ---------- State (dideklarasikan lebih awal agar tidak kena
     temporal dead zone saat showDashboard() dipanggil dari
     pengecekan sesi di bawah, sebelum baris ini dulu berada) ---------- */
  let currentFilter = 'semua';
  let currentSearch = '';
  let searchDebounce = null;

  /* ---------- Auto-refresh (real time) ----------
     Dashboard tidak pakai WebSocket, jadi "real time" di sini dilakukan
     lewat polling: setiap beberapa detik, data pertanyaan diambil ulang
     dari server secara diam-diam (tanpa layar "Memuat data..." dan tanpa
     mengganggu admin yang sedang mengetik di kolom pencarian atau sedang
     membuka modal jawaban). Kalau ada tiket baru / status berubah, tabel
     & statistik diperbarui otomatis dan admin diberi tahu lewat toast. */
  const POLL_INTERVAL_MS = 5000;
  let pollTimer = null;
  let isFetchingTable = false;
    let lastSeenSignature = null;
  let lastMenunggu = null;   // jumlah tiket "menunggu" pada pengambilan data sebelumnya
  let hiddenTicks = 0;       // penghitung polling saat tab tidak aktif

  function startPolling(){
    stopPolling();
    pollTimer = setInterval(() => {
      // Jangan poll kalau: tab sedang tidak aktif/di-minimize, sedang ada
      // modal terbuka (admin sedang menulis jawaban), atau view yang
      // tampil bukan tabel Dashboard (mis. sedang di halaman Pengaturan).
            // Tab tidak aktif: tetap cek tiap 3 putaran (±15 dtk) supaya angka di judul tab & bunyi tiket baru tetap hidup.
      if(document.hidden && (++hiddenTicks % 3) !== 0) return;
      if(modalOverlay.classList.contains('open')) return;
      if(views.dashboard.style.display === 'none') return;
      renderTable({ silent: true });
    }, POLL_INTERVAL_MS);
  }
  function stopPolling(){
    if(pollTimer){ clearInterval(pollTimer); pollTimer = null; }
  }
  // Begitu tab aktif kembali (mis. admin pindah tab lalu balik), langsung
  // cek data terbaru tanpa perlu menunggu interval berikutnya.
  document.addEventListener('visibilitychange', () => {
    if(!document.hidden && dashboard.style.display !== 'none' && views.dashboard.style.display !== 'none'){
      renderTable({ silent: true });
    }
  });

  function showDashboard(){
    loginScreen.style.display = 'none';
    dashboard.style.display = 'block';
    renderAll();
    startPolling();
  }
  function showLogin(){
    dashboard.style.display = 'none';
    loginScreen.style.display = 'flex';
    stopPolling();
  }

  /* Refresh (F5) saat sudah login -> tetap pertahankan sesi & tampil
     dashboard. Tapi kunjungan BARU ke halaman ini (mis. lewat pintasan
     ketik "admin" di beranda) -> SELALU tampil form login dulu, supaya
     admin lain tidak otomatis masuk memakai sesi admin sebelumnya yang
     lupa "Keluar" di komputer yang sama. */
  if(bp2tlIsPageReload()){
    const session = await bp2tlCheckSession();
    if(session.loggedIn){
      showDashboard();
    } else {
      showLogin();
    }
  } else {
    showLogin();
  }

  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const username = document.getElementById('loginUser').value.trim();
    const password = document.getElementById('loginPass').value;

    loginSubmitBtn.disabled = true;
    loginSubmitBtn.textContent = 'Memeriksa...';
    const res = await bp2tlLogin(username, password);
    loginSubmitBtn.disabled = false;
    loginSubmitBtn.textContent = 'Masuk';

    if(res.success){
      loginError.style.display = 'none';
      loginForm.reset();
      showDashboard();
    }else{
      loginError.textContent = res.message || 'Nama pengguna atau kata sandi salah.';
      loginError.style.display = 'block';
    }
  });

  logoutBtn.addEventListener('click', async () => {
    await bp2tlLogout();
    showLogin();
  });

  /* ---------- Rendering ---------- */
  const searchInput = document.getElementById('searchInput');
  const statusFilter = document.getElementById('statusFilter');
  searchInput.addEventListener('input', () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
      currentSearch = searchInput.value.trim();
      renderTable();
    }, 300);
  });
  statusFilter.addEventListener('change', () => { currentFilter = statusFilter.value; renderTable(); });

  async function renderAll(){
    await renderTable();
  }

  async function renderTable(opts = {}){
    const silent = !!opts.silent;

    // Kalau permintaan sebelumnya (mis. dari polling) masih berjalan,
    // jangan tumpuk permintaan baru di atasnya.
    if(silent && isFetchingTable) return;
    isFetchingTable = true;

    const tbody = document.getElementById('tableBody');
    const isFirstLoad = tbody.children.length === 0;

    if(!silent){
      if(isFirstLoad){
        tbody.innerHTML = `<tr class="empty-row"><td colspan="6">Memuat data...</td></tr>`;
      }else{
        // Data lama tetap tampil (tidak dikosongkan) agar tabel tidak "berkedip"
        // atau berubah tinggi setiap kali mengetik di pencarian / ganti filter.
        tbody.style.opacity = '.5';
      }
    }

    const res = await bp2tlList({ status: currentFilter, search: currentSearch });
    isFetchingTable = false;
    if(!silent) tbody.style.opacity = '';

    if(!res.success){
      // Kalau ini polling diam-diam yang gagal (mis. koneksi putus sesaat),
      // jangan timpa tabel yang sedang tampil dengan pesan error — cukup
      // coba lagi di polling berikutnya.
      if(silent) return;
      tbody.innerHTML = `<tr class="empty-row"><td colspan="6">${bp2tlEscapeHtml(res.message || 'Gagal memuat data dari server.')}</td></tr>`;
      return;
    }

        // Tiket baru = jumlah "menunggu" naik sejak pengambilan sebelumnya (berlaku apa pun filter yang dipilih).
    const naik = lastMenunggu === null ? 0 : res.stats.menunggu - lastMenunggu;
    lastMenunggu = res.stats.menunggu;
    if(window.bp2tlFx){
      bp2tlFx.setPending(res.stats.menunggu);
      if(silent && naik > 0) bp2tlFx.notifyNewTickets(naik);
    }

    // Bandingkan "sidik jari" data ...

    // Bandingkan "sidik jari" data (jumlah + status tiap tiket) dengan hasil
    // sebelumnya. Kalau tidak ada perubahan sama sekali, dan ini polling
    // diam-diam, tidak perlu menggambar ulang tabel (menghindari kedipan
    // yang sia-sia setiap 5 detik).
    const signature = JSON.stringify([
      res.stats.total, res.stats.menunggu, res.stats.dijawab, res.stats.hariIni,
      res.data.map(i => `${i.code}:${i.status}:${i.jawaban ? 1 : 0}`),
    ]);
    const isFirstSignature = lastSeenSignature === null;
    const hasChanged = signature !== lastSeenSignature;

    if(silent && !hasChanged) return;

    // Kalau ini polling diam-diam DAN memang ada perubahan (bukan pemuatan
    // pertama kali), beri tahu admin lewat toast supaya jelas ini data baru,
    // bukan sekadar tampilan yang berkedip sendiri.
    if(silent && hasChanged && !isFirstSignature){
            showToast(naik > 0 ? `${naik} tiket baru masuk.` : 'Ada pembaruan data pertanyaan/pengaduan.');
    }
    lastSeenSignature = signature;

    document.getElementById('statTotal').textContent = res.stats.total;
    document.getElementById('statMenunggu').textContent = res.stats.menunggu;
    document.getElementById('statDijawab').textContent = res.stats.dijawab;
    document.getElementById('statHariIni').textContent = res.stats.hariIni;

    const list = res.data;
    if(list.length === 0){
      tbody.innerHTML = `<tr class="empty-row"><td colspan="6">Tidak ada pertanyaan yang cocok dengan filter ini.</td></tr>`;
      return;
    }

    tbody.innerHTML = list.map(item => `
      <tr data-code="${item.code}"${item.kategori === 'Pengaduan Layanan' ? ' class="row-pengaduan"' : ''}>
        <td class="code-cell">${item.code}</td>
        <td>${bp2tlEscapeHtml(item.nama)}</td>
        <td>${bp2tlEscapeHtml(item.kategori)}</td>
        <td>${bp2tlFormatDate(item.tanggal)}</td>
        <td>${item.status === 'dijawab'
              ? '<span class="status-badge status-dijawab">Dijawab</span>'
              : '<span class="status-badge status-menunggu">Menunggu</span>'}</td>
        <td class="actions-cell">
          <button type="button" class="btn btn-ghost btn-sm" data-open="${item.code}">${item.status === 'dijawab' ? 'Lihat' : 'Jawab'}</button>
          <button type="button" class="btn btn-danger btn-sm" data-delete="${item.code}">Hapus</button>
        </td>
      </tr>
    `).join('');

    tbody.querySelectorAll('[data-open]').forEach(btn => {
      btn.addEventListener('click', () => openModal(btn.dataset.open, list));
    });
    tbody.querySelectorAll('[data-delete]').forEach(btn => {
      btn.addEventListener('click', () => deleteItem(btn.dataset.delete));
    });
  }

  /* ---------- Hapus pertanyaan / pengaduan ---------- */
  async function deleteItem(code){
    const ok = window.confirm(`Hapus tiket ${code} secara permanen? Data yang sudah dihapus tidak dapat dikembalikan.`);
    if(!ok) return;

    const res = await bp2tlHapus(code);
    if(!res.success){
      showToast(res.message || 'Gagal menghapus data dari database.');
      return;
    }
    closeModal();
    renderAll();
    showToast('Tiket berhasil dihapus dari database.');
  }

  /* ---------- Modal jawab pertanyaan ---------- */
  const modalOverlay = document.getElementById('modalOverlay');
  const modalBody = document.getElementById('modalBody');

  function openModal(code, list){
    const item = list.find(i => i.code === code);
    if(!item) return;

    const answered = item.status === 'dijawab';
    modalBody.innerHTML = `
      <div class="modal-head">
        <div>
          <h3 style="margin-bottom:2px;">${item.code}</h3>
          ${answered
            ? '<span class="status-badge status-dijawab">Sudah dijawab</span>'
            : '<span class="status-badge status-menunggu">Menunggu jawaban</span>'}
        </div>
        <button type="button" class="modal-close" id="modalCloseBtn" aria-label="Tutup">&times;</button>
      </div>
      <dl>
        <dt>Nama</dt><dd>${bp2tlEscapeHtml(item.nama)}</dd>
        <dt>Kontak</dt><dd>${bp2tlEscapeHtml(item.kontak)}</dd>
        <dt>Kategori</dt><dd>${bp2tlEscapeHtml(item.kategori)}</dd>
        <dt>Diajukan</dt><dd>${bp2tlFormatDate(item.tanggal)}</dd>
      </dl>
      <div class="q-text">${bp2tlEscapeHtml(item.pertanyaan)}</div>

      <form id="answerForm">
        <div class="field">
          <label for="answerText">${answered ? 'Jawaban (dapat diperbarui)' : 'Tulis jawaban'}</label>
          <textarea id="answerText" required placeholder="Tulis jawaban resmi untuk pertanyaan ini...">${answered ? bp2tlEscapeHtml(item.jawaban) : ''}</textarea>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-danger" id="modalDeleteBtn">Hapus Tiket</button>
          <div style="flex:1;"></div>
          <button type="button" class="btn btn-ghost" id="modalCancelBtn">Batal</button>
          <button type="submit" class="btn btn-primary" id="answerSubmitBtn">${answered ? 'Perbarui Jawaban' : 'Kirim Jawaban'}</button>
        </div>
      </form>
    `;

    modalOverlay.classList.add('open');
    document.getElementById('modalCloseBtn').addEventListener('click', closeModal);
    document.getElementById('modalCancelBtn').addEventListener('click', closeModal);
    document.getElementById('modalDeleteBtn').addEventListener('click', () => deleteItem(item.code));
    document.getElementById('answerForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const text = document.getElementById('answerText').value.trim();
      if(!text) return;

      const submitBtn = document.getElementById('answerSubmitBtn');
      submitBtn.disabled = true;
      submitBtn.textContent = 'Menyimpan...';

      const res = await bp2tlJawab(item.code, text);

      submitBtn.disabled = false;
      submitBtn.textContent = answered ? 'Perbarui Jawaban' : 'Kirim Jawaban';

      if(!res.success){
        showToast(res.message || 'Gagal menyimpan jawaban ke database.');
        return;
      }
      closeModal();
      renderAll();
      showToast(answered ? 'Jawaban diperbarui di database.' : 'Jawaban tersimpan ke database.');
    });
  }

  function closeModal(){
    modalOverlay.classList.remove('open');
    modalBody.innerHTML = '';
  }
  modalOverlay.addEventListener('click', (e) => {
    if(e.target === modalOverlay) closeModal();
  });
  document.addEventListener('keydown', (e) => {
    if(e.key === 'Escape') closeModal();
  });

  function showToast(text){
    const toast = document.getElementById('toast');
    toast.textContent = text;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2500);
  }

  /* ---------- Hamburger menu (khusus mobile) ---------- */
  let adminNavToggle = document.getElementById('adminNavToggle');
  const adminSidebar   = document.querySelector('.admin-sidebar');
  const adminTopbar    = document.querySelector('.admin-topbar');
  if(!adminNavToggle && adminTopbar && adminSidebar){
    // Fallback: kalau HTML belum punya tombol hamburger, buat sendiri.
    adminNavToggle = document.createElement('button');
    adminNavToggle.type = 'button';
    adminNavToggle.className = 'admin-nav-toggle';
    adminNavToggle.id = 'adminNavToggle';
    adminNavToggle.setAttribute('aria-label', 'Buka menu');
    adminNavToggle.setAttribute('aria-expanded', 'false');
    adminNavToggle.innerHTML = '<span></span>';
    adminTopbar.insertBefore(adminNavToggle, adminTopbar.firstChild);
  }
  if(adminNavToggle && adminSidebar){
    // Menu mobile dibuat menempel tepat di bawah topbar (position:fixed), jadi tinggi
    // topbar perlu diukur karena bisa berubah (elemen membungkus di layar sempit).
    const syncTopbarHeight = () => {
      if(adminTopbar) document.documentElement.style.setProperty('--admin-topbar-h', adminTopbar.offsetHeight + 'px');
    };
    adminNavToggle.addEventListener('click', () => {
      syncTopbarHeight();
      const open = adminSidebar.classList.toggle('open');
      adminNavToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    window.addEventListener('resize', syncTopbarHeight);
    // Klik di luar menu (area konten) juga menutup menu.
    document.addEventListener('click', e => {
      if(!adminSidebar.classList.contains('open')) return;
      if(adminSidebar.contains(e.target) || adminNavToggle.contains(e.target)) return;
      adminSidebar.classList.remove('open');
      adminNavToggle.setAttribute('aria-expanded', 'false');
    });

    // Adaptif dua arah: begitu jendela dilebarkan ke ukuran desktop, reset status menu
    // hamburger; tombol Esc juga menutup menu di mobile.
    const closeNav = () => {
      adminSidebar.classList.remove('open');
      adminNavToggle.setAttribute('aria-expanded', 'false');
    };
    window.matchMedia('(min-width:861px)').addEventListener('change', e => { if(e.matches) closeNav(); });
    document.addEventListener('keydown', e => { if(e.key === 'Escape') closeNav(); });
  }

  /* ---------- Sidebar: pindah antar view Dashboard / Chatbot / Pengaturan ---------- */
  const sidebarLinks = document.querySelectorAll('.sidebar-link');
  const views = {
    dashboard: document.getElementById('view-dashboard'),
    chatbot: document.getElementById('view-chatbot'),
    pengaturan: document.getElementById('view-pengaturan'),
  };
  let settingsLoaded = false;

  sidebarLinks.forEach(link => {
    link.addEventListener('click', () => {
      const target = link.dataset.view;
      sidebarLinks.forEach(l => l.classList.toggle('active', l === link));
      Object.keys(views).forEach(key => {
        views[key].style.display = key === target ? 'block' : 'none';
      });
      if(target === 'pengaturan' && !settingsLoaded){
        loadSettings();
      }
      if(target === 'chatbot'){
        renderFaqTable();
      }
      // Setelah pilih menu lewat hamburger di mobile, tutup lagi dropdown-nya.
      if(adminSidebar && adminSidebar.classList.contains('open')){
        adminSidebar.classList.remove('open');
        adminNavToggle.setAttribute('aria-expanded', 'false');
      }
    });
  });

  /* =========================================================
     CHATBOT: data latih (pertanyaan & jawaban manual dari admin)
     ========================================================= */
  const chatbotFaqForm = document.getElementById('chatbotFaqForm');
  const faqSubmitBtn   = document.getElementById('faqSubmitBtn');
  const faqSearchInput = document.getElementById('faqSearchInput');
  const faqTableBody   = document.getElementById('faqTableBody');
  let faqCache = [];
  let faqSearchDebounce = null;

  function faqTruncate(text, max = 90){
    if(!text) return '';
    return text.length > max ? text.slice(0, max).trim() + '…' : text;
  }

  async function renderFaqTable(){
    faqTableBody.innerHTML = `<tr class="empty-row"><td colspan="4">Memuat data...</td></tr>`;

    const res = await bp2tlChatbotFaqList(faqSearchInput.value.trim());
    if(!res.success){
      faqTableBody.innerHTML = `<tr class="empty-row"><td colspan="4">${bp2tlEscapeHtml(res.message || 'Gagal memuat data dari server.')}</td></tr>`;
      return;
    }

    faqCache = res.data;
    if(faqCache.length === 0){
      faqTableBody.innerHTML = `<tr class="empty-row"><td colspan="4">Belum ada data latih chatbot. Tambahkan lewat form di atas.</td></tr>`;
      return;
    }

    // Teks panjang dipotong rapi lewat CSS (maks. 3 baris) — isi lengkap tetap
    // bisa dilihat lewat tombol Edit. Atribut data-label dipakai tampilan mobile.
    faqTableBody.innerHTML = faqCache.map(item => `
      <tr data-id="${item.id}">
        <td data-label="Pertanyaan"><div class="faq-clamp">${bp2tlEscapeHtml(item.pertanyaan)}</div></td>
        <td data-label="Jawaban"><div class="faq-clamp">${bp2tlEscapeHtml(faqTruncate(item.jawaban, 400))}</div></td>
        <td data-label="Kata Kunci">${item.kata_kunci ? `<div class="faq-clamp faq-clamp-2">${bp2tlEscapeHtml(item.kata_kunci)}</div>` : '<span class="hint">-</span>'}</td>
        <td class="actions-cell" data-label="Aksi">
          <div class="faq-actions">
            <button type="button" class="btn btn-ghost btn-sm" data-edit-faq="${item.id}">Edit</button>
            <button type="button" class="btn btn-danger btn-sm" data-delete-faq="${item.id}">Hapus</button>
          </div>
        </td>
      </tr>
    `).join('');

    faqTableBody.querySelectorAll('[data-edit-faq]').forEach(btn => {
      btn.addEventListener('click', () => openFaqModal(btn.dataset.editFaq));
    });
    faqTableBody.querySelectorAll('[data-delete-faq]').forEach(btn => {
      btn.addEventListener('click', () => deleteFaq(btn.dataset.deleteFaq));
    });
  }

  chatbotFaqForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const pertanyaan = document.getElementById('faqPertanyaan').value.trim();
    const jawaban    = document.getElementById('faqJawaban').value.trim();
    const kataKunci  = document.getElementById('faqKataKunci').value.trim();
    if(!pertanyaan || !jawaban) return;

    faqSubmitBtn.disabled = true;
    faqSubmitBtn.textContent = 'Menyimpan...';
    const res = await bp2tlChatbotFaqCreate(pertanyaan, jawaban, kataKunci);
    faqSubmitBtn.disabled = false;
    faqSubmitBtn.textContent = 'Tambah ke Chatbot';

    if(!res.success){
      showToast(res.message || 'Gagal menyimpan data ke database.');
      return;
    }
    chatbotFaqForm.reset();
    renderFaqTable();
    showToast('Data latih chatbot berhasil ditambahkan.');
  });

  faqSearchInput.addEventListener('input', () => {
    clearTimeout(faqSearchDebounce);
    faqSearchDebounce = setTimeout(renderFaqTable, 300);
  });

  function openFaqModal(id){
    const item = faqCache.find(i => String(i.id) === String(id));
    if(!item) return;

    modalBody.innerHTML = `
      <div class="modal-head">
        <h3 style="margin:0;">Ubah Data Latih Chatbot</h3>
        <button type="button" class="modal-close" id="modalCloseBtn" aria-label="Tutup">&times;</button>
      </div>
      <form id="faqEditForm">
        <div class="field">
          <label for="faqEditPertanyaan">Pertanyaan</label>
          <input type="text" id="faqEditPertanyaan" required value="${bp2tlEscapeHtml(item.pertanyaan)}">
        </div>
        <div class="field">
          <label for="faqEditJawaban">Jawaban</label>
          <textarea id="faqEditJawaban" required rows="4">${bp2tlEscapeHtml(item.jawaban)}</textarea>
        </div>
        <div class="field">
          <label for="faqEditKataKunci">Kata kunci tambahan (opsional)</label>
          <input type="text" id="faqEditKataKunci" value="${item.kata_kunci ? bp2tlEscapeHtml(item.kata_kunci) : ''}">
          <span class="hint">Pisahkan dengan koma.</span>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-danger" id="modalDeleteFaqBtn">Hapus</button>
          <div style="flex:1;"></div>
          <button type="button" class="btn btn-ghost" id="modalCancelBtn">Batal</button>
          <button type="submit" class="btn btn-primary" id="faqEditSubmitBtn">Simpan Perubahan</button>
        </div>
      </form>
    `;

    modalOverlay.classList.add('open');
    document.getElementById('modalCloseBtn').addEventListener('click', closeModal);
    document.getElementById('modalCancelBtn').addEventListener('click', closeModal);
    document.getElementById('modalDeleteFaqBtn').addEventListener('click', () => deleteFaq(item.id));
    document.getElementById('faqEditForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const pertanyaan = document.getElementById('faqEditPertanyaan').value.trim();
      const jawaban    = document.getElementById('faqEditJawaban').value.trim();
      const kataKunci  = document.getElementById('faqEditKataKunci').value.trim();
      if(!pertanyaan || !jawaban) return;

      const submitBtn = document.getElementById('faqEditSubmitBtn');
      submitBtn.disabled = true;
      submitBtn.textContent = 'Menyimpan...';

      const res = await bp2tlChatbotFaqUpdate(item.id, pertanyaan, jawaban, kataKunci);

      submitBtn.disabled = false;
      submitBtn.textContent = 'Simpan Perubahan';

      if(!res.success){
        showToast(res.message || 'Gagal menyimpan perubahan ke database.');
        return;
      }
      closeModal();
      renderFaqTable();
      showToast('Data latih chatbot berhasil diperbarui.');
    });
  }

  async function deleteFaq(id){
    const ok = window.confirm('Hapus data latih chatbot ini secara permanen?');
    if(!ok) return;

    const res = await bp2tlChatbotFaqDelete(id);
    if(!res.success){
      showToast(res.message || 'Gagal menghapus data dari database.');
      return;
    }
    closeModal();
    renderFaqTable();
    showToast('Data latih chatbot berhasil dihapus.');
  }

  /* ---------- Pengaturan: muat & simpan teks halaman ---------- */
  const settingsForm = document.getElementById('settingsForm');
  const settingsSubmitBtn = document.getElementById('settingsSubmitBtn');

  async function loadSettings(){
    settingsForm.querySelectorAll('[data-key]').forEach(el => { el.value = 'Memuat...'; el.disabled = true; });
    const res = await bp2tlSettingsGet();
    settingsForm.querySelectorAll('[data-key]').forEach(el => { el.disabled = false; el.value = ''; });

    if(!res.success){
      showToast(res.message || 'Gagal memuat pengaturan dari server.');
      return;
    }
    settingsForm.querySelectorAll('[data-key]').forEach(el => {
      const key = el.dataset.key;
      if(res.data && res.data[key] != null) el.value = res.data[key];
    });
    settingsLoaded = true;
  }

  settingsForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = {};
    settingsForm.querySelectorAll('[data-key]').forEach(el => {
      payload[el.dataset.key] = el.value.trim();
    });

    settingsSubmitBtn.disabled = true;
    settingsSubmitBtn.textContent = 'Menyimpan...';
    const res = await bp2tlSettingsSave(payload);
    settingsSubmitBtn.disabled = false;
    settingsSubmitBtn.textContent = 'Simpan Perubahan';

    if(!res.success){
      showToast(res.message || 'Gagal menyimpan pengaturan ke database.');
      return;
    }
    showToast('Perubahan teks halaman tersimpan. Cek beranda untuk melihat hasilnya.');
  });
});