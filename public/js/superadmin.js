/* =========================================================
   BP2TL Jakarta — superadmin.js
   Halaman ini khusus untuk akun berperan "superadmin". Isinya:
   - Log Aktivitas: memantau semua aktivitas di website (lewat
     tabel activity_log, endpoint api/superadmin_log.php).
   - Kelola Akun Admin: membuat / mengubah / menghapus akun admin
     langsung ke tabel admin_users (endpoint
     api/superadmin_accounts.php).
   ========================================================= */

document.addEventListener('DOMContentLoaded', async () => {
  const checkingScreen   = document.getElementById('checkingScreen');
  const loginScreen      = document.getElementById('loginScreen');
  const loginForm        = document.getElementById('loginForm');
  const loginError       = document.getElementById('loginError');
  const loginSubmitBtn   = document.getElementById('loginSubmitBtn');
  const dashboard        = document.getElementById('dashboard');
  const currentUserLabel = document.getElementById('currentUserLabel');
  const logoutBtn        = document.getElementById('logoutBtn');

  let session = null;

  /* ---------- Auto-refresh (real time) ----------
     Sama seperti dashboard Admin biasa: tidak pakai WebSocket, jadi "real
     time" dilakukan lewat polling diam-diam setiap beberapa detik. Log
     aktivitas dipoll hanya selama view "Log Aktivitas" yang sedang tampil
     (tidak perlu poll saat admin sedang di view "Kelola Akun"), tab masih
     aktif, dan tidak ada modal (mis. form akun) yang sedang terbuka. */
  const POLL_INTERVAL_MS = 5000;
  let pollTimer = null;
  let isFetchingLog = false;
  let lastSeenLogSignature = null;

  function startPolling(){
    stopPolling();
    pollTimer = setInterval(() => {
      if(document.hidden) return;
      if(modalOverlay.classList.contains('open')) return;
      if(views.log.style.display === 'none') return;
      renderLog({ silent: true });
    }, POLL_INTERVAL_MS);
  }
  function stopPolling(){
    if(pollTimer){ clearInterval(pollTimer); pollTimer = null; }
  }
  document.addEventListener('visibilitychange', () => {
    if(!document.hidden && dashboard.style.display !== 'none' && views.log.style.display !== 'none'){
      renderLog({ silent: true });
    }
  });

  function showDashboard(){
    checkingScreen.style.display = 'none';
    loginScreen.style.display = 'none';
    dashboard.style.display = 'block';
    currentUserLabel.textContent = session.username ? `Super Admin \u2014 ${session.username}` : 'Super Admin';
    renderLog(); // muat log begitu dashboard tampil
    startPolling();
  }

  function showLogin(){
    checkingScreen.style.display = 'none';
    dashboard.style.display = 'none';
    loginScreen.style.display = 'flex';
    stopPolling();
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

    // Backend (api/login.php) sudah menolak akun yang bukan Super Admin
    // untuk login lewat halaman ini, jadi res.success di sini pasti
    // berarti akun Super Admin yang sah.
    if(res.success){
      loginError.style.display = 'none';
      loginForm.reset();
      session = { loggedIn: true, role: res.role, username };
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

  /* ---------- Sidebar: pindah antar view ---------- */
  const sidebarLinks = document.querySelectorAll('.sidebar-link');
  const views = {
    log: document.getElementById('view-log'),
    history: document.getElementById('view-history'),
    akun: document.getElementById('view-akun'),
    pengaturan: document.getElementById('view-pengaturan'),
  };
  let accountsLoaded = false;

  sidebarLinks.forEach(link => {
    link.addEventListener('click', () => {
      const target = link.dataset.view;
      sidebarLinks.forEach(l => l.classList.toggle('active', l === link));
      Object.keys(views).forEach(key => {
        views[key].style.display = key === target ? 'block' : 'none';
      });
      if(target === 'akun' && !accountsLoaded){
        renderAccounts();
      }
      if(target === 'history'){
        // Selalu tarik data terbaru tiap kali menu ini dibuka, supaya
        // sinkron dengan tiket yang baru saja dihapus/dijawab di admin.
        renderHistory();
      }
      if(target === 'log'){
        // Balik ke view Log Aktivitas -> langsung tarik data terbaru,
        // jangan tunggu interval polling berikutnya.
        renderLog({ silent: true });
      }
      // Setelah pilih menu lewat hamburger di mobile, tutup lagi dropdown-nya.
      if(adminSidebar && adminSidebar.classList.contains('open')){
        adminSidebar.classList.remove('open');
        adminNavToggle.setAttribute('aria-expanded', 'false');
      }
    });
  });

  /* =========================================================
     LOG AKTIVITAS
     ========================================================= */
  const logActionFilter = document.getElementById('logActionFilter');
  const logSearchInput  = document.getElementById('logSearchInput');
  const logRefreshBtn   = document.getElementById('logRefreshBtn');
  const logTableBody    = document.getElementById('logTableBody');
  let logSearchDebounce = null;

  const ACTION_LABELS = {
    login: 'Login',
    logout: 'Logout',
    pertanyaan_baru: 'Pertanyaan/Pengaduan Baru',
    jawab_otomatis: 'Dijawab Otomatis (tanpa tiket)',
    jawab_pertanyaan: 'Jawab Pertanyaan',
    hapus_pertanyaan: 'Hapus Pertanyaan',
    ubah_pengaturan: 'Ubah Pengaturan Halaman',
    buat_akun: 'Buat Akun Admin',
    ubah_akun: 'Ubah Akun Admin',
    hapus_akun: 'Hapus Akun Admin',
    tambah_faq_chatbot: 'Tambah Data Latih Chatbot',
    ubah_faq_chatbot: 'Ubah Data Latih Chatbot',
    hapus_faq_chatbot: 'Hapus Data Latih Chatbot',
    hapus_log: 'Hapus Log Aktivitas',
    hapus_semua_pertanyaan: 'Hapus Semua Data Tiket',
  };

  async function renderLog(opts = {}){
    const silent = !!opts.silent;

    if(silent && isFetchingLog) return;
    isFetchingLog = true;

    if(!silent){
      logTableBody.innerHTML = `<tr class="empty-row"><td colspan="5">Memuat data...</td></tr>`;
    }

    const res = await bp2tlSuperadminLog({
      action: logActionFilter.value,
      search: logSearchInput.value.trim(),
    });
    isFetchingLog = false;

    if(!res.success){
      // Polling diam-diam yang gagal (mis. koneksi putus sesaat) tidak perlu
      // menimpa tabel yang sedang tampil dengan pesan error.
      if(silent) return;
      logTableBody.innerHTML = `<tr class="empty-row"><td colspan="5">${bp2tlEscapeHtml(res.message || 'Gagal memuat log dari server.')}</td></tr>`;
      return;
    }

    // Sidik jari data (statistik + baris log terbaru) untuk mendeteksi
    // apakah memang ada aktivitas baru sejak polling terakhir, supaya
    // tabel tidak digambar ulang tanpa alasan setiap 5 detik.
    const signature = JSON.stringify([
      res.stats?.total ?? 0, res.stats?.hariIni ?? 0, res.stats?.loginHariIni ?? 0,
      res.data.map(r => r.id),
    ]);
    const isFirstSignature = lastSeenLogSignature === null;
    const hasChanged = signature !== lastSeenLogSignature;

    if(silent && !hasChanged) return;

    if(silent && hasChanged && !isFirstSignature){
      showToast('Ada aktivitas baru di log.');
    }
    lastSeenLogSignature = signature;

    document.getElementById('logStatTotal').textContent   = res.stats?.total ?? 0;
    document.getElementById('logStatHariIni').textContent = res.stats?.hariIni ?? 0;
    document.getElementById('logStatLogin').textContent   = res.stats?.loginHariIni ?? 0;

    if(res.data.length === 0){
      logTableBody.innerHTML = `<tr class="empty-row"><td colspan="5">Belum ada aktivitas yang cocok dengan filter ini.</td></tr>`;
      return;
    }

    logTableBody.innerHTML = res.data.map(row => `
      <tr>
        <td>${bp2tlFormatDate(row.created_at)}</td>
        <td>${bp2tlEscapeHtml(row.admin_username)}</td>
        <td>${bp2tlEscapeHtml(ACTION_LABELS[row.action] || row.action)}</td>
        <td>${bp2tlEscapeHtml(row.deskripsi)}</td>
        <td>${bp2tlEscapeHtml(row.ip_address || '-')}</td>
      </tr>
    `).join('');
  }

  logActionFilter.addEventListener('change', renderLog);
  logSearchInput.addEventListener('input', () => {
    clearTimeout(logSearchDebounce);
    logSearchDebounce = setTimeout(renderLog, 300);
  });
  logRefreshBtn.addEventListener('click', renderLog);

  /* =========================================================
     HISTORY PERTANYAAN (otak chatbot)
     ========================================================= */
  const historySearchInput  = document.getElementById('historySearchInput');
  const historyRefreshBtn   = document.getElementById('historyRefreshBtn');
  const historyTableBody    = document.getElementById('historyTableBody');
  let historySearchDebounce = null;

  async function renderHistory(){
    historyTableBody.innerHTML = `<tr class="empty-row"><td colspan="7">Memuat data...</td></tr>`;

    const res = await bp2tlSuperadminHistory({
      search: historySearchInput.value.trim(),
    });

    if(!res.success){
      historyTableBody.innerHTML = `<tr class="empty-row"><td colspan="7">${bp2tlEscapeHtml(res.message || 'Gagal memuat data dari server.')}</td></tr>`;
      return;
    }

    document.getElementById('historyStatTotal').textContent   = res.stats?.total ?? 0;

    if(res.data.length === 0){
      historyTableBody.innerHTML = `<tr class="empty-row"><td colspan="7">Belum ada pertanyaan yang cocok dengan filter ini.</td></tr>`;
      return;
    }

    historyTableBody.innerHTML = res.data.map(row => `
      <tr>
        <td class="code-cell">${bp2tlEscapeHtml(row.code)}</td>
        <td>${bp2tlEscapeHtml(row.nama)}</td>
        <td>${bp2tlEscapeHtml(row.kategori)}</td>
        <td>${bp2tlEscapeHtml(row.pertanyaan)}</td>
        <td>${row.jawaban ? bp2tlEscapeHtml(row.jawaban) : '<span style="color:var(--slate-500);">Belum dijawab</span>'}</td>
        <td>${row.status === 'dijawab' ? '<span class="status-badge status-dijawab">Dijawab</span>' : '<span class="status-badge status-menunggu">Menunggu</span>'}</td>
        <td>${bp2tlFormatDate(row.tanggal)}</td>
      </tr>
    `).join('');
  }

  historySearchInput.addEventListener('input', () => {
    clearTimeout(historySearchDebounce);
    historySearchDebounce = setTimeout(renderHistory, 300);
  });
  historyRefreshBtn.addEventListener('click', renderHistory);

  /* =========================================================
     KELOLA AKUN ADMIN
     ========================================================= */
  const accountsTableBody = document.getElementById('accountsTableBody');
  const addAccountBtn     = document.getElementById('addAccountBtn');
  const modalOverlay      = document.getElementById('modalOverlay');
  const modalBody         = document.getElementById('modalBody');
  let accountsCache = [];

  async function renderAccounts(){
    accountsTableBody.innerHTML = `<tr class="empty-row"><td colspan="4">Memuat data...</td></tr>`;
    const res = await bp2tlAccountsList();
    accountsLoaded = true;

    if(!res.success){
      accountsTableBody.innerHTML = `<tr class="empty-row"><td colspan="4">${bp2tlEscapeHtml(res.message || 'Gagal memuat akun dari server.')}</td></tr>`;
      return;
    }

    accountsCache = res.data;
    if(accountsCache.length === 0){
      accountsTableBody.innerHTML = `<tr class="empty-row"><td colspan="4">Belum ada akun.</td></tr>`;
      return;
    }

    accountsTableBody.innerHTML = accountsCache.map(acc => `
      <tr data-id="${acc.id}">
        <td>${bp2tlEscapeHtml(acc.username)}${acc.username === session.username ? '<span class="you-tag">(Anda)</span>' : ''}</td>
        <td>${acc.role === 'superadmin'
              ? '<span class="role-badge role-badge-superadmin">Super Admin</span>'
              : '<span class="role-badge role-badge-admin">Admin</span>'}</td>
        <td>${bp2tlFormatDate(acc.created_at)}</td>
        <td class="actions-cell">
          <button type="button" class="btn btn-ghost btn-sm" data-edit="${acc.id}">Edit</button>
          <button type="button" class="btn btn-danger btn-sm" data-delete="${acc.id}">Hapus</button>
        </td>
      </tr>
    `).join('');

    accountsTableBody.querySelectorAll('[data-edit]').forEach(btn => {
      btn.addEventListener('click', () => openAccountModal('edit', btn.dataset.edit));
    });
    accountsTableBody.querySelectorAll('[data-delete]').forEach(btn => {
      btn.addEventListener('click', () => deleteAccount(btn.dataset.delete));
    });
  }

  addAccountBtn.addEventListener('click', () => openAccountModal('create'));

  function openAccountModal(mode, id){
    const isEdit = mode === 'edit';
    const acc = isEdit ? accountsCache.find(a => String(a.id) === String(id)) : null;
    if(isEdit && !acc) return;

    modalBody.innerHTML = `
      <div class="modal-head">
        <h3 style="margin:0;">${isEdit ? 'Ubah Akun' : 'Tambah Akun Baru'}</h3>
        <button type="button" class="modal-close" id="modalCloseBtn" aria-label="Tutup">&times;</button>
      </div>
      <form id="accountForm">
        <div class="field">
          <label for="accUsername">Nama pengguna</label>
          <input type="text" id="accUsername" required value="${isEdit ? bp2tlEscapeHtml(acc.username) : ''}" autocomplete="off">
        </div>
        <div class="field">
          <label for="accPassword">${isEdit ? 'Kata sandi baru (kosongkan jika tidak diubah)' : 'Kata sandi'}</label>
          <input type="password" id="accPassword" ${isEdit ? '' : 'required'} minlength="6" autocomplete="new-password" placeholder="Minimal 6 karakter">
        </div>
        <div class="field">
          <label for="accRole">Peran</label>
          <select id="accRole">
            <option value="admin" ${isEdit && acc.role === 'admin' ? 'selected' : ''}>Admin</option>
            <option value="superadmin" ${isEdit && acc.role === 'superadmin' ? 'selected' : ''}>Super Admin</option>
          </select>
          <span class="hint">Super Admin bisa mengakses halaman ini (Log Aktivitas &amp; Kelola Akun). Admin biasa hanya bisa mengakses dashboard pertanyaan &amp; pengaturan teks halaman.</span>
        </div>
        <div class="notice notice-error" id="accountFormError" style="display:none;"></div>
        <div class="modal-actions">
          <div style="flex:1;"></div>
          <button type="button" class="btn btn-ghost" id="modalCancelBtn">Batal</button>
          <button type="submit" class="btn btn-primary" id="accountSubmitBtn">${isEdit ? 'Simpan Perubahan' : 'Buat Akun'}</button>
        </div>
      </form>
    `;

    modalOverlay.classList.add('open');
    document.getElementById('modalCloseBtn').addEventListener('click', closeModal);
    document.getElementById('modalCancelBtn').addEventListener('click', closeModal);

    document.getElementById('accountForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const username = document.getElementById('accUsername').value.trim();
      const password = document.getElementById('accPassword').value;
      const role     = document.getElementById('accRole').value;
      const errorBox = document.getElementById('accountFormError');
      const submitBtn = document.getElementById('accountSubmitBtn');

      errorBox.style.display = 'none';
      submitBtn.disabled = true;
      submitBtn.textContent = 'Menyimpan...';

      const res = isEdit
        ? await bp2tlAccountUpdate(acc.id, username, password, role)
        : await bp2tlAccountCreate(username, password, role);

      submitBtn.disabled = false;
      submitBtn.textContent = isEdit ? 'Simpan Perubahan' : 'Buat Akun';

      if(!res.success){
        errorBox.textContent = res.message || 'Gagal menyimpan akun ke database.';
        errorBox.style.display = 'block';
        return;
      }

      closeModal();
      renderAccounts();
      showToast(isEdit ? 'Akun berhasil diperbarui.' : 'Akun baru berhasil dibuat.');

      // Kalau yang diubah adalah akun sendiri, sinkronkan label nama di topbar
      if(isEdit && acc.username === session.username){
        session.username = username;
        currentUserLabel.textContent = `Super Admin \u2014 ${username}`;
      }
    });
  }

  async function deleteAccount(id){
    const acc = accountsCache.find(a => String(a.id) === String(id));
    const ok = window.confirm(`Hapus akun "${acc ? acc.username : id}" secara permanen?`);
    if(!ok) return;

    const res = await bp2tlAccountDelete(id);
    if(!res.success){
      showToast(res.message || 'Gagal menghapus akun dari database.');
      return;
    }
    renderAccounts();
    showToast('Akun berhasil dihapus.');
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

  /* =========================================================
     PENGATURAN: hapus data massal (Log Aktivitas / semua tiket)
     ========================================================= */

  /**
   * Modal konfirmasi untuk aksi berbahaya/tidak bisa dibatalkan.
   * Tombol konfirmasi baru aktif setelah pengguna mengetik ulang kata
   * kunci yang ditentukan persis sama (tidak peka besar/kecil huruf) —
   * jauh lebih aman daripada window.confirm() biasa yang bisa ke-klik
   * "OK" tanpa sengaja/tanpa dibaca.
   */
  function openDangerModal({ title, message, confirmLabel, confirmWord, onConfirm }){
    modalBody.innerHTML = `
      <div class="modal-head">
        <h3 style="margin:0;">${bp2tlEscapeHtml(title)}</h3>
        <button type="button" class="modal-close" id="modalCloseBtn" aria-label="Tutup">&times;</button>
      </div>
      <div class="notice notice-error" style="margin-bottom:16px;">${bp2tlEscapeHtml(message)}</div>
      <div class="field">
        <label for="dangerConfirmInput">Ketik <strong>${bp2tlEscapeHtml(confirmWord)}</strong> untuk melanjutkan</label>
        <input type="text" id="dangerConfirmInput" autocomplete="off" placeholder="${bp2tlEscapeHtml(confirmWord)}">
      </div>
      <div class="modal-actions">
        <div style="flex:1;"></div>
        <button type="button" class="btn btn-ghost" id="modalCancelBtn">Batal</button>
        <button type="button" class="btn btn-danger" id="dangerConfirmBtn" disabled>${bp2tlEscapeHtml(confirmLabel)}</button>
      </div>
    `;

    modalOverlay.classList.add('open');
    const input = document.getElementById('dangerConfirmInput');
    const confirmBtn = document.getElementById('dangerConfirmBtn');

    input.addEventListener('input', () => {
      confirmBtn.disabled = input.value.trim().toUpperCase() !== confirmWord.toUpperCase();
    });
    document.getElementById('modalCloseBtn').addEventListener('click', closeModal);
    document.getElementById('modalCancelBtn').addEventListener('click', closeModal);

    confirmBtn.addEventListener('click', async () => {
      confirmBtn.disabled = true;
      const originalLabel = confirmBtn.textContent;
      confirmBtn.textContent = 'Menghapus...';
      await onConfirm();
      // Kalau onConfirm gagal (modal masih terbuka), aktifkan lagi tombolnya
      if(document.body.contains(confirmBtn)){
        confirmBtn.disabled = false;
        confirmBtn.textContent = originalLabel;
      }
    });
  }

  document.getElementById('resetLogBtn').addEventListener('click', () => {
    openDangerModal({
      title: 'Hapus Semua Log Aktivitas',
      message: 'Semua riwayat di Log Aktivitas akan dihapus permanen dan tidak bisa dikembalikan. Data pertanyaan & pengaduan masyarakat di dashboard Admin tidak akan terpengaruh.',
      confirmLabel: 'Hapus Log Aktivitas',
      confirmWord: 'HAPUS',
      onConfirm: async () => {
        const res = await bp2tlResetLog();
        if(!res.success){
          showToast(res.message || 'Gagal menghapus log dari database.');
          return;
        }
        closeModal();
        renderLog();
        showToast(`${res.count} baris log aktivitas berhasil dihapus.`);
      },
    });
  });

  document.getElementById('resetPertanyaanBtn').addEventListener('click', () => {
    openDangerModal({
      title: 'Hapus Semua Data Pertanyaan & Pengaduan',
      message: 'SEMUA tiket pertanyaan & pengaduan di dashboard Admin biasa (beserta jawabannya) akan dihapus permanen. Kode tiket yang sudah dibagikan ke masyarakat tidak akan bisa dilacak lagi setelah ini.',
      confirmLabel: 'Hapus Semua Data Tiket',
      confirmWord: 'HAPUS SEMUA',
      onConfirm: async () => {
        const res = await bp2tlResetPertanyaan();
        if(!res.success){
          showToast(res.message || 'Gagal menghapus data dari database.');
          return;
        }
        closeModal();
        showToast(`${res.count} tiket berhasil dihapus dari database.`);
      },
    });
  });

  /* ---------- Cek sesi & tampilkan layar yang sesuai ----------
     PENTING: baris ini sengaja diletakkan PALING BAWAH, setelah semua
     variabel (logTableBody, accountsTableBody, dst.) dan fungsi
     (renderLog, renderAccounts, dst.) di atas selesai didefinisikan.
     Kalau diletakkan di atas, showDashboard() -> renderLog() akan
     dipanggil sebelum logTableBody dkk. selesai dideklarasikan, yang
     bikin renderLog() gagal diam-diam (data terlihat kosong padahal
     sebenarnya tidak terhapus, cuma gagal ditampilkan).

     Refresh (F5) saat sudah login -> tetap pertahankan sesi & tampil
     dashboard. Tapi kunjungan BARU ke halaman ini -> SELALU tampil form
     login dulu, supaya Super Admin lain tidak otomatis masuk memakai
     sesi Super Admin sebelumnya yang lupa "Keluar" di komputer yang
     sama. */
  if(bp2tlIsPageReload()){
    const existing = await bp2tlCheckSession();
    if(existing.loggedIn && existing.role === 'superadmin'){
      session = existing;
      showDashboard();
    } else {
      showLogin();
    }
  } else {
    showLogin();
  }
});