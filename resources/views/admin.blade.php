<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Admin — BP2TL Jakarta</title>
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
<link rel="stylesheet" href="{{ asset('css/fx.css') }}">
</head>
<body class="admin-body">

<!-- ================= LOGIN ================= -->
<div class="admin-login" id="loginScreen">
  <div class="admin-login-card">
    <div class="brand">
      <span class="logo-group"><img src="{{ asset('img/logo-1.png') }}" alt="Logo Balai Pendidikan dan Pelatihan Transportasi Laut"><img src="{{ asset('img/logo-2.png') }}" alt="Logo Kementerian Perhubungan"><img src="{{ asset('img/logo-3.png') }}" alt="Logo Gunawisista Nimpuna Sistacara"></span>
      <span class="brand-text"><strong>BP2TL</strong><span>Portal Admin</span></span>
    </div>
    <h1>Masuk ke dashboard</h1>
    <p>Khusus petugas BP2TL Jakarta untuk mengelola pertanyaan masyarakat.</p>

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
        <a href="{{ url('/superadmin') }}" class="admin-login-link">Masuk sebagai Super Admin</a>
        <a href="{{ url('/') }}" class="admin-login-link">Kembali ke Beranda</a>
      </div>
    </form>
  </div>
</div>

<!-- ================= DASHBOARD ================= -->
<div id="dashboard" style="display:none;">
  <div class="admin-topbar">
    <button type="button" class="admin-nav-toggle" id="adminNavToggle" aria-label="Buka menu" aria-expanded="false" aria-controls="sidebarNav">
      <span></span>
    </button>
    <div class="brand">
      <span class="logo-group"><img src="{{ asset('img/logo-1.png') }}" alt="Logo Balai Pendidikan dan Pelatihan Transportasi Laut"><img src="{{ asset('img/logo-2.png') }}" alt="Logo Kementerian Perhubungan"><img src="{{ asset('img/logo-3.png') }}" alt="Logo Gunawisista Nimpuna Sistacara"></span>
      <span class="brand-text"><strong>BP2TL</strong><span>Dashboard Admin</span></span>
    </div>
    <div class="admin-user">
      <span>Petugas Layanan</span>
      <button type="button" class="btn btn-outline btn-sm" id="logoutBtn">Keluar</button>
    </div>
  </div>

  <div class="admin-layout">
    <aside class="admin-sidebar">
      <nav class="sidebar-nav" id="sidebarNav">
        <button type="button" class="sidebar-link active" data-view="dashboard">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="12" width="8" height="9" rx="1.5"/><rect x="3" y="15" width="8" height="6" rx="1.5"/></svg>
          <span>Dashboard</span>
        </button>
        <button type="button" class="sidebar-link" data-view="chatbot">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
          <span>Chatbot</span>
        </button>
        <button type="button" class="sidebar-link" data-view="pengaturan">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
          <span>Pengaturan</span>
        </button>
      </nav>
    </aside>

    <div class="admin-content">

      <!-- ========= VIEW: DASHBOARD ========= -->
      <section class="admin-view" id="view-dashboard">
        <div class="admin-shell">
          <div class="stat-row">
            <div class="stat-card"><div class="num" id="statTotal">0</div><div class="lbl">Total Pertanyaan</div></div>
            <div class="stat-card"><div class="num" id="statMenunggu">0</div><div class="lbl">Menunggu Jawaban</div></div>
            <div class="stat-card"><div class="num" id="statDijawab">0</div><div class="lbl">Sudah Dijawab</div></div>
            <div class="stat-card"><div class="num" id="statHariIni">0</div><div class="lbl">Masuk Hari Ini</div></div>
          </div>

          <div class="admin-toolbar">
            <div class="filters">
              <select id="statusFilter">
                <option value="semua">Semua status</option>
                <option value="menunggu">Menunggu</option>
                <option value="dijawab">Dijawab</option>
              </select>
              <input type="text" id="searchInput" placeholder="Cari kode, nama, atau isi pertanyaan...">
            </div>
          </div>

          <div class="table-wrap">
            <table class="qtable">
              <thead>
                <tr>
                  <th>Kode</th>
                  <th>Nama</th>
                  <th>Kategori</th>
                  <th>Tanggal</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody id="tableBody"></tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ========= VIEW: CHATBOT ========= -->
      <section class="admin-view" id="view-chatbot" style="display:none;">
        <div class="admin-shell">
          <div class="settings-head">
            <h2>Latih Chatbot</h2>
            <p>Tambahkan pasangan pertanyaan &amp; jawaban di sini untuk melengkapi pengetahuan chatbot di beranda — di luar pertanyaan masyarakat yang sudah dijawab lewat menu Dashboard. Semakin banyak entri di sini, semakin lengkap jawaban chatbot ke pengunjung.</p>
          </div>

          <form id="chatbotFaqForm" class="settings-group">
            <h3>Tambah Pertanyaan &amp; Jawaban Baru</h3>
            <div class="field">
              <label for="faqPertanyaan">Pertanyaan</label>
              <input type="text" id="faqPertanyaan" required placeholder="Contoh: Apa saja syarat membuat SIB kapal?">
            </div>
            <div class="field">
              <label for="faqJawaban">Jawaban</label>
              <textarea id="faqJawaban" required rows="4" placeholder="Tulis jawaban resmi yang akan ditampilkan chatbot..."></textarea>
            </div>
            <div class="field">
              <label for="faqKataKunci">Kata kunci tambahan (opsional)</label>
              <input type="text" id="faqKataKunci" placeholder="mis. SIB, surat izin berlayar, izin berlayar">
              <span class="hint">Pisahkan dengan koma. Membantu chatbot mengenali variasi kata/singkatan yang mungkin dipakai masyarakat, di luar kata-kata yang sudah ada di pertanyaan &amp; jawaban di atas.</span>
            </div>
            <button type="submit" class="btn btn-primary" id="faqSubmitBtn">Tambah ke Chatbot</button>
          </form>

          <div class="admin-toolbar" style="margin-top:28px;">
            <div class="filters">
              <input type="text" id="faqSearchInput" placeholder="Cari pertanyaan, jawaban, atau kata kunci...">
            </div>
          </div>

          <div class="table-wrap faq-wrap">
            <table class="qtable faq-table">
              <colgroup>
                <col style="width:30%">
                <col style="width:36%">
                <col style="width:16%">
                <col style="width:18%">
              </colgroup>
              <thead>
                <tr>
                  <th>Pertanyaan</th>
                  <th>Jawaban</th>
                  <th>Kata Kunci</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody id="faqTableBody"></tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ========= VIEW: PENGATURAN ========= -->
      <section class="admin-view" id="view-pengaturan" style="display:none;">
        <div class="admin-shell">
          <div class="settings-head">
            <h2>Pengaturan Teks Halaman</h2>
            <p>Ubah teks yang tampil di halaman utama (beranda) — Hero, section pertanyaan, FAQ, dan peta lokasi — tanpa perlu mengedit kode.</p>
          </div>

          <form id="settingsForm">
            <div class="settings-group">
              <h3>Section Hero (paling atas beranda)</h3>
              <div class="field">
                <label for="setHeroTitle">Judul utama</label>
                <input type="text" id="setHeroTitle" data-key="hero_title">
              </div>
              <div class="field">
                <label for="setHeroLead">Teks deskripsi</label>
                <textarea id="setHeroLead" data-key="hero_lead" rows="4"></textarea>
              </div>
              <div class="field">
                <label for="setHeroPhoto1">Foto latar 1 (URL gambar)</label>
                <input type="text" id="setHeroPhoto1" data-key="hero_photo_1" placeholder="https://...">
              </div>
              <div class="field">
                <label for="setHeroPhoto2">Foto latar 2 (URL gambar)</label>
                <input type="text" id="setHeroPhoto2" data-key="hero_photo_2" placeholder="https://...">
              </div>
              <div class="field">
                <label for="setHeroPhoto3">Foto latar 3 (URL gambar)</label>
                <input type="text" id="setHeroPhoto3" data-key="hero_photo_3" placeholder="https://...">
              </div>
              <div class="field">
                <label for="setHeroPhoto4">Foto latar 4 (URL gambar)</label>
                <input type="text" id="setHeroPhoto4" data-key="hero_photo_4" placeholder="https://...">
                <span class="hint">Tempel link gambar (contoh: hasil upload ke Google Drive/Imgur yang sudah "share public", atau folder gambar di server). Keempat foto akan bergantian otomatis dengan animasi fade di background Hero.</span>
              </div>
            </div>

            <div class="settings-group">
              <h3>Section Ajukan / Lacak Pertanyaan</h3>
              <div class="form-grid cols-2">
                <div class="field">
                  <label for="setTanyaKicker">Label kecil</label>
                  <input type="text" id="setTanyaKicker" data-key="tanya_kicker">
                </div>
                <div class="field">
                  <label for="setTanyaTitle">Judul</label>
                  <input type="text" id="setTanyaTitle" data-key="tanya_title">
                </div>
                <div class="field" style="grid-column:1 / -1;">
                  <label for="setTanyaDesc">Deskripsi</label>
                  <textarea id="setTanyaDesc" data-key="tanya_desc" rows="3"></textarea>
                </div>
              </div>
            </div>

            <div class="settings-group">
              <h3>Section FAQ</h3>
              <div class="form-grid cols-2">
                <div class="field">
                  <label for="setFaqKicker">Label kecil</label>
                  <input type="text" id="setFaqKicker" data-key="faq_kicker">
                </div>
                <div class="field">
                  <label for="setFaqTitle">Judul</label>
                  <input type="text" id="setFaqTitle" data-key="faq_title">
                </div>
                <div class="field" style="grid-column:1 / -1;">
                  <label for="setFaqDesc">Deskripsi</label>
                  <textarea id="setFaqDesc" data-key="faq_desc" rows="3"></textarea>
                </div>
              </div>
            </div>

            <div class="settings-group">
              <h3>Section FAQ &mdash; 5 Pertanyaan &amp; Jawaban</h3>
              <span class="hint" style="display:block; margin-bottom:16px;">Ubah teks pertanyaan dan jawaban yang tampil di daftar FAQ pada beranda. Kosongkan jika tidak ingin mengubah teks yang sudah ada.</span>

              <div class="field">
                <label for="setFaqQ1">Pertanyaan 1</label>
                <input type="text" id="setFaqQ1" data-key="faq_q1">
              </div>
              <div class="field">
                <label for="setFaqA1">Jawaban 1</label>
                <textarea id="setFaqA1" data-key="faq_a1" rows="3"></textarea>
              </div>

              <div class="field">
                <label for="setFaqQ2">Pertanyaan 2</label>
                <input type="text" id="setFaqQ2" data-key="faq_q2">
              </div>
              <div class="field">
                <label for="setFaqA2">Jawaban 2</label>
                <textarea id="setFaqA2" data-key="faq_a2" rows="3"></textarea>
              </div>

              <div class="field">
                <label for="setFaqQ3">Pertanyaan 3</label>
                <input type="text" id="setFaqQ3" data-key="faq_q3">
              </div>
              <div class="field">
                <label for="setFaqA3">Jawaban 3</label>
                <textarea id="setFaqA3" data-key="faq_a3" rows="3"></textarea>
              </div>

              <div class="field">
                <label for="setFaqQ4">Pertanyaan 4</label>
                <input type="text" id="setFaqQ4" data-key="faq_q4">
              </div>
              <div class="field">
                <label for="setFaqA4">Jawaban 4</label>
                <textarea id="setFaqA4" data-key="faq_a4" rows="3"></textarea>
              </div>

              <div class="field">
                <label for="setFaqQ5">Pertanyaan 5</label>
                <input type="text" id="setFaqQ5" data-key="faq_q5">
              </div>
              <div class="field">
                <label for="setFaqA5">Jawaban 5</label>
                <textarea id="setFaqA5" data-key="faq_a5" rows="3"></textarea>
              </div>
            </div>

            <div class="settings-group">
              <h3>Section Peta Lokasi</h3>
              <div class="form-grid cols-2">
                <div class="field">
                  <label for="setPetaKicker">Label kecil</label>
                  <input type="text" id="setPetaKicker" data-key="peta_kicker">
                </div>
                <div class="field">
                  <label for="setPetaTitle">Judul</label>
                  <input type="text" id="setPetaTitle" data-key="peta_title">
                </div>
                <div class="field" style="grid-column:1 / -1;">
                  <label for="setAlamatText">Teks alamat (tampil di samping peta)</label>
                  <textarea id="setAlamatText" data-key="alamat_text" rows="2"></textarea>
                </div>
              </div>
            </div>

            <div class="settings-group">
              <h3>Footer</h3>
              <span class="hint" style="display:block; margin-bottom:16px;">Ubah teks deskripsi, daftar tautan (teks &amp; link tujuan), info kontak, dan baris hak cipta yang tampil di footer paling bawah halaman utama.</span>

              <div class="field">
                <label for="setFooterDesc">Deskripsi singkat (di bawah logo)</label>
                <textarea id="setFooterDesc" data-key="footer_desc" rows="3"></textarea>
              </div>

              <h4 style="margin:18px 0 4px;">Daftar Tautan</h4>

              <div class="form-grid cols-2">
                <div class="field">
                  <label for="setFooterLink1Text">Tautan 1 &mdash; Teks</label>
                  <input type="text" id="setFooterLink1Text" data-key="footer_link1_text">
                </div>
                <div class="field">
                  <label for="setFooterLink1Href">Tautan 1 &mdash; Link tujuan</label>
                  <input type="text" id="setFooterLink1Href" data-key="footer_link1_href" placeholder="#beranda atau https://...">
                </div>

                <div class="field">
                  <label for="setFooterLink2Text">Tautan 2 &mdash; Teks</label>
                  <input type="text" id="setFooterLink2Text" data-key="footer_link2_text">
                </div>
                <div class="field">
                  <label for="setFooterLink2Href">Tautan 2 &mdash; Link tujuan</label>
                  <input type="text" id="setFooterLink2Href" data-key="footer_link2_href" placeholder="#tanya atau https://...">
                </div>

                <div class="field">
                  <label for="setFooterLink3Text">Tautan 3 &mdash; Teks</label>
                  <input type="text" id="setFooterLink3Text" data-key="footer_link3_text">
                </div>
                <div class="field">
                  <label for="setFooterLink3Href">Tautan 3 &mdash; Link tujuan</label>
                  <input type="text" id="setFooterLink3Href" data-key="footer_link3_href" placeholder="#faq atau https://...">
                </div>

                <div class="field">
                  <label for="setFooterLink4Text">Tautan 4 &mdash; Teks</label>
                  <input type="text" id="setFooterLink4Text" data-key="footer_link4_text">
                </div>
                <div class="field">
                  <label for="setFooterLink4Href">Tautan 4 &mdash; Link tujuan</label>
                  <input type="text" id="setFooterLink4Href" data-key="footer_link4_href" placeholder="#peta atau https://...">
                </div>

                <div class="field">
                  <label for="setFooterLink5Text">Tautan 5 &mdash; Teks</label>
                  <input type="text" id="setFooterLink5Text" data-key="footer_link5_text">
                </div>
                <div class="field">
                  <label for="setFooterLink5Href">Tautan 5 &mdash; Link tujuan</label>
                  <input type="text" id="setFooterLink5Href" data-key="footer_link5_href" placeholder="admin.html atau https://...">
                </div>
              </div>

              <h4 style="margin:18px 0 4px;">Kontak</h4>
              <div class="form-grid cols-2">
                <div class="field" style="grid-column:1 / -1;">
                  <label for="setFooterAlamat">Alamat</label>
                  <input type="text" id="setFooterAlamat" data-key="footer_kontak_alamat">
                </div>
                <div class="field">
                  <label for="setFooterTelepon">Telepon</label>
                  <input type="text" id="setFooterTelepon" data-key="footer_kontak_telepon">
                </div>
                <div class="field">
                  <label for="setFooterEmail">Email</label>
                  <input type="text" id="setFooterEmail" data-key="footer_kontak_email">
                </div>
              </div>

              <h4 style="margin:18px 0 4px;">Baris paling bawah</h4>
              <div class="form-grid cols-2">
                <div class="field">
                  <label for="setFooterCopyright">Teks hak cipta</label>
                  <input type="text" id="setFooterCopyright" data-key="footer_copyright">
                </div>
                <div class="field">
                  <label for="setFooterTagline">Teks kecil kanan (tagline)</label>
                  <input type="text" id="setFooterTagline" data-key="footer_tagline">
                </div>
              </div>
            </div>

            <button type="submit" class="btn btn-primary" id="settingsSubmitBtn" style="margin-top:6px;">Simpan Perubahan</button>
          </form>
        </div>
      </section>

    </div>
  </div>
</div>

<!-- ================= MODAL JAWAB ================= -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal" role="dialog" aria-modal="true" id="modalBody"></div>
</div>

<div class="toast" id="toast"></div>

<script>window.BP2TL_REALM = 'admin';</script>
<script>window.BP2TL_API_BASE = @json(request()->getBasePath() . '/api');</script>
<script src="{{ asset('js/api.js') }}"></script>
<script src="{{ asset('js/fx.js') }}"></script>
<script src="{{ asset('js/admin.js') }}"></script>   <!-- di superadmin: js/superadmin.js -->
</body>
</html>