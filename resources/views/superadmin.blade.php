<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Super Admin — BP2TL Jakarta</title>
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
<link rel="stylesheet" href="{{ asset('css/fx.css') }}">
</head>
<body class="admin-body">

<!-- Layar ini hanya tampil sekejap saat sesi sedang diperiksa -->
<div class="admin-login" id="checkingScreen">
  <div class="admin-login-card">
    <div class="brand">
      <span class="logo-group"><img src="{{ asset('img/logo-1.png') }}" alt="Logo Balai Pendidikan dan Pelatihan Transportasi Laut"><img src="{{ asset('img/logo-2.png') }}" alt="Logo Kementerian Perhubungan"><img src="{{ asset('img/logo-3.png') }}" alt="Logo Gunawisista Nimpuna Sistacara"></span>
      <span class="brand-text"><strong>BP2TL</strong><span>Super Admin</span></span>
    </div>
    <h1>Memeriksa sesi...</h1>
    <p id="checkingText">Mohon tunggu sebentar.</p>
  </div>
</div>

<!-- ================= LOGIN (KHUSUS SUPER ADMIN) ================= -->
<div class="admin-login" id="loginScreen" style="display:none;">
  <div class="admin-login-card">
    <div class="brand">
      <span class="logo-group"><img src="{{ asset('img/logo-1.png') }}" alt="Logo Balai Pendidikan dan Pelatihan Transportasi Laut"><img src="{{ asset('img/logo-2.png') }}" alt="Logo Kementerian Perhubungan"><img src="{{ asset('img/logo-3.png') }}" alt="Logo Gunawisista Nimpuna Sistacara"></span>
      <span class="brand-text"><strong>BP2TL</strong><span>Portal Super Admin</span></span>
    </div>
    <h1>Masuk ke Super Admin</h1>
    <p>Khusus akun berperan Super Admin, untuk mengelola log aktivitas &amp; akun admin.</p>

    <form id="loginForm" style="margin-top:20px;" novalidate>
      <div class="field">
        <label for="loginUser">Nama pengguna</label>
        <input type="text" id="loginUser" required autocomplete="username">
      </div>
      <div class="field">
        <label for="loginPass">Kata sandi</label>
        <input type="password" id="loginPass" required autocomplete="current-password">
      </div>
      <div class="notice notice-error" id="loginError" style="display:none;">Nama pengguna atau kata sandi salah.</div>
      <button type="submit" class="btn btn-primary" id="loginSubmitBtn">Masuk</button>
      <div class="admin-login-links">
        <a href="{{ url('/admin') }}" class="admin-login-link">Masuk sebagai Admin biasa</a>
        <a href="{{ url('/') }}" class="admin-login-link">Kembali ke Beranda</a>
      </div>
    </form>
  </div>
</div>

<!-- ================= DASHBOARD SUPER ADMIN ================= -->
<div id="dashboard" style="display:none;">
  <div class="admin-topbar">
    <button type="button" class="admin-nav-toggle" id="adminNavToggle" aria-label="Buka menu" aria-expanded="false" aria-controls="sidebarNav">
      <span></span>
    </button>
    <div class="brand">
      <span class="logo-group"><img src="{{ asset('img/logo-1.png') }}" alt="Logo Balai Pendidikan dan Pelatihan Transportasi Laut"><img src="{{ asset('img/logo-2.png') }}" alt="Logo Kementerian Perhubungan"><img src="{{ asset('img/logo-3.png') }}" alt="Logo Gunawisista Nimpuna Sistacara"></span>
      <span class="brand-text"><strong>BP2TL</strong><span>Super Admin</span></span>
    </div>
    <div class="admin-user">
      <span id="currentUserLabel">Super Admin</span>
      <button type="button" class="btn btn-outline btn-sm" id="logoutBtn">Keluar</button>
    </div>
  </div>

  <div class="admin-layout">
    <aside class="admin-sidebar">
      <nav class="sidebar-nav" id="sidebarNav">
        <button type="button" class="sidebar-link active" data-view="log">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg>
          <span>Log Aktivitas</span>
        </button>
        <button type="button" class="sidebar-link" data-view="history">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M9 8h7M9 12h7"/></svg>
          <span>History Pertanyaan</span>
        </button>
        <button type="button" class="sidebar-link" data-view="akun">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          <span>Kelola Akun Admin</span>
        </button>
        <button type="button" class="sidebar-link" data-view="pengaturan">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          <span>Pengaturan</span>
        </button>
      </nav>
    </aside>

    <div class="admin-content">

      <!-- ========= VIEW: LOG AKTIVITAS ========= -->
      <section class="admin-view" id="view-log">
        <div class="admin-shell">
          <div class="settings-head">
            <h2>Log Aktivitas</h2>
            <p>Semua aktivitas yang terjadi di website ini: login/logout admin, pertanyaan &amp; pengaduan baru dari publik, jawaban admin, penghapusan tiket, perubahan pengaturan halaman, dan perubahan akun admin.</p>
          </div>

          <div class="stat-row">
            <div class="stat-card"><div class="num" id="logStatTotal">0</div><div class="lbl">Total Aktivitas</div></div>
            <div class="stat-card"><div class="num" id="logStatHariIni">0</div><div class="lbl">Aktivitas Hari Ini</div></div>
            <div class="stat-card"><div class="num" id="logStatLogin">0</div><div class="lbl">Login Hari Ini</div></div>
          </div>

          <div class="admin-toolbar">
            <div class="filters">
              <select id="logActionFilter">
                <option value="semua">Semua jenis aksi</option>
                <option value="login">Login</option>
                <option value="logout">Logout</option>
                <option value="pertanyaan_baru">Pertanyaan/Pengaduan Baru</option>
                <option value="jawab_otomatis">Dijawab Otomatis (tanpa tiket)</option>
                <option value="jawab_pertanyaan">Jawab Pertanyaan</option>
                <option value="hapus_pertanyaan">Hapus Pertanyaan</option>
                <option value="ubah_pengaturan">Ubah Pengaturan Halaman</option>
                <option value="buat_akun">Buat Akun Admin</option>
                <option value="ubah_akun">Ubah Akun Admin</option>
                <option value="hapus_akun">Hapus Akun Admin</option>
                <option value="tambah_faq_chatbot">Tambah Data Latih Chatbot</option>
                <option value="ubah_faq_chatbot">Ubah Data Latih Chatbot</option>
                <option value="hapus_faq_chatbot">Hapus Data Latih Chatbot</option>
                <option value="hapus_log">Hapus Log Aktivitas</option>
                <option value="hapus_semua_pertanyaan">Hapus Semua Data Tiket</option>
              </select>
              <input type="text" id="logSearchInput" placeholder="Cari nama pengguna atau deskripsi aktivitas...">
              <button type="button" class="btn btn-ghost btn-sm" id="logRefreshBtn">Muat Ulang</button>
            </div>
          </div>

          <div class="table-wrap">
            <table class="qtable">
              <thead>
                <tr>
                  <th>Waktu</th>
                  <th>Pengguna</th>
                  <th>Aksi</th>
                  <th>Deskripsi</th>
                  <th>IP</th>
                </tr>
              </thead>
              <tbody id="logTableBody"></tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ========= VIEW: HISTORY PERTANYAAN ========= -->
      <section class="admin-view" id="view-history" style="display:none;">
        <div class="admin-shell">
          <div class="settings-head">
            <h2>History Pertanyaan</h2>
            <p>Salinan permanen setiap pertanyaan masyarakat yang sudah dijawab admin (di luar kategori Pengaduan Layanan) — inilah sumber "otak" jawaban otomatis chatbot di beranda. Data di sini tersimpan terpisah dari dashboard Admin: hapus massal lewat menu Pengaturan Super Admin TIDAK akan menghapus riwayat ini. Riwayat sebuah tiket hanya ikut terhapus kalau tiket itu dihapus satu per satu lewat dashboard Admin biasa.</p>
          </div>

          <div class="stat-row">
            <div class="stat-card"><div class="num" id="historyStatTotal">0</div><div class="lbl">Total Riwayat Jawaban</div></div>
          </div>

          <div class="admin-toolbar">
            <div class="filters">
              <input type="text" id="historySearchInput" placeholder="Cari kode, nama, pertanyaan, atau jawaban...">
              <button type="button" class="btn btn-ghost btn-sm" id="historyRefreshBtn">Muat Ulang</button>
            </div>
          </div>

          <div class="table-wrap">
            <table class="qtable">
              <thead>
                <tr>
                  <th>Kode</th>
                  <th>Nama</th>
                  <th>Kategori</th>
                  <th>Pertanyaan</th>
                  <th>Jawaban</th>
                  <th>Status</th>
                  <th>Tanggal</th>
                </tr>
              </thead>
              <tbody id="historyTableBody"></tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ========= VIEW: KELOLA AKUN ADMIN ========= -->
      <section class="admin-view" id="view-akun" style="display:none;">
        <div class="admin-shell">
          <div class="settings-head" style="display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap;">
            <div>
              <h2>Kelola Akun Admin</h2>
              <p>Buat akun admin baru, atau ubah nama pengguna / kata sandi / peran akun yang sudah ada. Terhubung langsung ke tabel <code>admin_users</code> di database.</p>
            </div>
            <button type="button" class="btn btn-primary" id="addAccountBtn" style="flex:none;">+ Tambah Akun</button>
          </div>

          <div class="table-wrap">
            <table class="qtable">
              <thead>
                <tr>
                  <th>Nama Pengguna</th>
                  <th>Peran</th>
                  <th>Dibuat</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody id="accountsTableBody"></tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ========= VIEW: PENGATURAN ========= -->
      <section class="admin-view" id="view-pengaturan" style="display:none;">
        <div class="admin-shell">
          <div class="settings-head">
            <h2>Pengaturan</h2>
            <p>Area untuk mengosongkan data secara massal. Tindakan di bawah ini <strong>permanen dan tidak bisa dibatalkan</strong> — pastikan sudah yakin sebelum melanjutkan.</p>
          </div>

          <div class="danger-zone">
            <div class="danger-card">
              <div>
                <h3>Hapus Semua Log Aktivitas</h3>
                <p>Mengosongkan seluruh riwayat di menu Log Aktivitas (login/logout, jawaban admin, perubahan pengaturan, dst). Data pertanyaan &amp; pengaduan masyarakat di dashboard Admin <strong>tidak</strong> ikut terhapus.</p>
              </div>
              <button type="button" class="btn btn-danger" id="resetLogBtn">Hapus Log Aktivitas</button>
            </div>

            <div class="danger-card">
              <div>
                <h3>Hapus Semua Data Pertanyaan &amp; Pengaduan</h3>
                <p>Mengosongkan <strong>SEMUA</strong> tiket di dashboard Admin biasa beserta jawabannya. Kode tiket yang sudah dibagikan ke masyarakat tidak akan bisa dilacak lagi setelah ini. Log Aktivitas <strong>tidak</strong> ikut terhapus.</p>
              </div>
              <button type="button" class="btn btn-danger" id="resetPertanyaanBtn">Hapus Semua Data Tiket</button>
            </div>
          </div>
        </div>
      </section>

    </div>
  </div>
</div>

<!-- ================= MODAL (tambah/ubah akun) ================= -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal" role="dialog" aria-modal="true" id="modalBody"></div>
</div>

<div class="toast" id="toast"></div>

<script>window.BP2TL_REALM = 'superadmin';</script>
<script>window.BP2TL_API_BASE = @json(request()->getBasePath() . '/api');</script>
<script src="{{ asset('js/api.js') }}"></script>
<script src="{{ asset('js/fx.js') }}"></script>
<script src="{{ asset('js/superadmin.js') }}"></script>
</body>
</html>