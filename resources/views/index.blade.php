<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>BP2TL Jakarta Layanan Terpadu</title>
<meta name="description" content="Layanan terpadu BP2TL Jakarta untuk mengajukan dan melacak pertanyaan seputar transportasi laut, perizinan kapal, dan pelabuhan.">
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
<script>
/* Terapkan tema tersimpan sedini mungkin agar halaman tidak "berkedip"
   dari terang ke gelap saat baru dimuat. */
(function(){
  try{
    var saved = localStorage.getItem('bp2tl-theme');
    var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    document.documentElement.setAttribute('data-theme', saved || (prefersDark ? 'dark' : 'light'));
  }catch(e){}
})();
</script>
</head>
<body>

<header class="site-header">
  <div class="wrap">
    <a href="#beranda" class="brand">
      <span class="logo-group"><img src="{{ asset('img/logo-1.png') }}" alt="Logo Balai Pendidikan dan Pelatihan Transportasi Laut"><img src="{{ asset('img/logo-2.png') }}" alt="Logo Kementerian Perhubungan"><img src="{{ asset('img/logo-3.png') }}" alt="Logo Gunawisista Nimpuna Sistacara"></span>
      <span class="brand-text">
        <strong>BP2TL</strong>
        <span>Layanan Terpadu BP2TL Jakarta</span>
      </span>
    </a>

    <div class="header-right">
      <nav class="main-nav" id="mainNav">
        <ul>
          <li><a href="#beranda">Beranda</a></li>
          <li><a href="#tanya">Tanyakan Pertanyaan</a></li>
          <li><a href="#peta">Peta Lokasi</a></li>
        </ul>
      </nav>

      <button type="button" class="theme-toggle" id="themeToggle" aria-label="Ganti ke mode gelap" aria-pressed="false">
        <svg class="icon-moon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
        <svg class="icon-sun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
      </button>

      <button class="nav-toggle" id="navToggle" aria-label="Buka menu" aria-expanded="false" aria-controls="mainNav">
        <span></span>
      </button>
    </div>
  </div>
</header>

<main>

  <section class="hero" id="beranda">
    <div class="hero-bg" aria-hidden="true">
      <div class="hero-bg-slide active" data-setting-bg="hero_photo_1" style="background-image:url('https://picsum.photos/seed/bp2tl-port1/1600/900');"></div>
      <div class="hero-bg-slide" data-setting-bg="hero_photo_2" style="background-image:url('https://picsum.photos/seed/bp2tl-port2/1600/900');"></div>
      <div class="hero-bg-slide" data-setting-bg="hero_photo_3" style="background-image:url('https://picsum.photos/seed/bp2tl-port3/1600/900');"></div>
      <div class="hero-bg-slide" data-setting-bg="hero_photo_4" style="background-image:url('https://picsum.photos/seed/bp2tl-port4/1600/900');"></div>
      <div class="hero-bg-overlay"></div>
    </div>

    <div class="wrap hero-inner">
      <h1 data-setting="hero_title">Satu pintu layanan transportasi laut Jakarta</h1>
      <p class="lead" data-setting="hero_lead">
        Kanal resmi bagi masyarakat, pelaku usaha kapal, dan nelayan untuk mengajukan
        pertanyaan seputar perizinan kapal, pelabuhan, dan dermaga di Jakarta.
      </p>
      <div class="hero-actions">
        <a href="#tanya" class="btn btn-primary">Ajukan Pertanyaan</a>
        <a href="#tanya" class="btn btn-primary" data-goto-tab="lacak">Cek Status Pertanyaan atau Aduan</a>
      </div>
    </div>
  </section>

  <section class="section" id="tanya">
    <div class="wrap">
      <div class="section-head">
        <span class="kicker" data-setting="tanya_kicker">Layanan Pertanyaan</span>
        <h2 data-setting="tanya_title">Ajukan atau lacak pertanyaan Anda</h2>
        <p data-setting="tanya_desc">Isi formulir di bawah untuk mengajukan pertanyaan baru, atau gunakan kode tiket yang sudah Anda terima untuk memeriksa status jawabannya.</p>
      </div>

      <div class="tanya-card">
        <div class="panel-tabs" role="tablist">
          <button type="button" class="tab-btn active" data-tab="ajukan" role="tab" aria-selected="true">Ajukan Pertanyaan</button>
          <button type="button" class="tab-btn" data-tab="pengaduan" role="tab" aria-selected="false">Pengaduan</button>
          <button type="button" class="tab-btn" data-tab="lacak" role="tab" aria-selected="false">Cek Status Pertanyaan atau Aduan</button>
        </div>

        <div class="tab-panel active" id="panel-ajukan">
          <form id="askForm" novalidate>
            <div class="form-grid cols-2">
              <div class="field">
                <label for="askNama">Nama lengkap</label>
                <input type="text" id="askNama" name="nama" required placeholder="Nama Anda">
                <span class="field-error" id="askNamaError">Mohon isi nama lengkap terlebih dahulu.</span>
              </div>
              <div class="field">
                <label for="askKontak">No. HP atau email</label>
                <input type="text" id="askKontak" name="kontak" required placeholder="08xx-xxxx-xxxx atau email">
                <span class="field-error" id="askKontakError">Mohon isi nomor HP atau email terlebih dahulu.</span>
              </div>
              <div class="field">
                <label for="askKategori">Kategori pertanyaan</label>
                <select id="askKategori" name="kategori" required>
                  <option value="" disabled selected>Pilih kategori</option>
                  <option>Diklat Pelaut</option>
                  <option>Diklat Fungsional</option>
                  <option>Informasi Umum</option>
                  <option>Lainnya</option>
                </select>
                <span class="field-error" id="askKategoriError">Mohon pilih kategori pertanyaan terlebih dahulu.</span>
              </div>
              <div class="field" style="grid-column:1 / -1;">
                <label for="askPertanyaan">Pertanyaan Anda</label>
                <textarea id="askPertanyaan" name="pertanyaan" required placeholder="Tuliskan pertanyaan Anda selengkap mungkin"></textarea>
                <span class="hint">Semakin lengkap pertanyaan yang Anda berikan, semakin cepat anda mendapatkan jawaban.</span>
                <span class="field-error" id="askPertanyaanError">Mohon isi pertanyaan Anda terlebih dahulu.</span>
              </div>
            </div>
            <button type="submit" class="btn btn-primary" id="askSubmitBtn" style="margin-top:22px;">Kirim Pertanyaan</button>
          </form>
          <div id="ticketResult"></div>
        </div>

        <div class="tab-panel" id="panel-pengaduan">
          <form id="complaintForm" novalidate>
            <div class="form-grid cols-2">
              <div class="field">
                <label for="complaintNama">Nama lengkap</label>
                <input type="text" id="complaintNama" name="nama" required placeholder="Nama Anda">
                <span class="field-error" id="complaintNamaError">Mohon isi nama lengkap terlebih dahulu.</span>
              </div>
              <div class="field">
                <label for="complaintKontak">No. WA atau email</label>
                <input type="text" id="complaintKontak" name="kontak" required placeholder="08xx-xxxx-xxxx atau email">
                <span class="field-error" id="complaintKontakError">Mohon isi nomor HP atau email terlebih dahulu.</span>
              </div>
              <div class="field" style="grid-column:1 / -1;">
                <label for="complaintIsi">Isi pengaduan</label>
                <textarea id="complaintIsi" name="isi" required placeholder="Tuliskan pengaduan Anda selengkap mungkin"></textarea>
                <span class="hint">Sertakan lokasi, waktu kejadian, dan detail lain agar pengaduan Anda cepat ditindaklanjuti.</span>
                <span class="field-error" id="complaintIsiError">Mohon isi pengaduan Anda terlebih dahulu.</span>
              </div>
            </div>
            <button type="submit" class="btn btn-primary" id="complaintSubmitBtn" style="margin-top:22px;">Kirim Pengaduan</button>
          </form>
          <div id="complaintResult"></div>
        </div>

        <div class="tab-panel" id="panel-lacak">
          <form id="trackForm" novalidate>
            <div class="field" style="max-width:520px;">
              <label for="trackCode">Kode tiket</label>
              <div class="track-row">
                <input type="text" id="trackCode" name="kode" placeholder="Contoh: BP2TL-2026-4K9X2T" required>
                <button type="submit" class="btn btn-navy" id="trackSubmitBtn">Cek Status</button>
              </div>
              <span class="hint">Kode tiket diberikan setelah Anda mengirim pertanyaan atau pengaduan.</span>
            </div>
          </form>
          <div id="trackResult"></div>
        </div>
      </div>
    </div>
  </section>

  <section class="section section-alt" id="faq">
    <div class="wrap">
      <div class="section-head">
        <span class="kicker" data-setting="faq_kicker">Bantuan Cepat</span>
        <h2 data-setting="faq_title">Pertanyaan yang paling sering diajukan</h2>
        <p data-setting="faq_desc">Lima pertanyaan berikut paling banyak ditanyakan masyarakat kepada BP2TL Jakarta.</p>
      </div>

      <div class="faq-list">
        <div class="faq-item">
          <button class="faq-q" aria-expanded="false">
            <span class="faq-num">1</span>
            <span data-setting="faq_q1">Apa itu BP2TL Jakarta?</span>
            <span class="chev">+</span>
          </button>
          <div class="faq-a">
            <div class="faq-a-inner">
              <p data-setting="faq_a1">BP2TL (Balai Pelayanan Terpadu Transportasi Laut) Jakarta adalah unit layanan yang menangani perizinan kapal, informasi pelabuhan dan dermaga, serta pengaduan masyarakat terkait transportasi laut di wilayah Jakarta.</p>
            </div>
          </div>
        </div>
        <div class="faq-item">
          <button class="faq-q" aria-expanded="false">
            <span class="faq-num">2</span>
            <span data-setting="faq_q2">Bagaimana cara mengajukan pertanyaan atau pengaduan?</span>
            <span class="chev">+</span>
          </button>
          <div class="faq-a">
            <div class="faq-a-inner">
              <p data-setting="faq_a2">Buka tab &ldquo;Ajukan Pertanyaan&rdquo; atau &ldquo;Pengaduan&rdquo; di halaman ini, isi data dan isi pertanyaan/pengaduan Anda, lalu klik kirim. Anda akan langsung menerima kode tiket untuk melacak jawabannya.</p>
            </div>
          </div>
        </div>
        <div class="faq-item">
          <button class="faq-q" aria-expanded="false">
            <span class="faq-num">3</span>
            <span data-setting="faq_q3">Berapa lama waktu yang dibutuhkan untuk mendapat jawaban?</span>
            <span class="chev">+</span>
          </button>
          <div class="faq-a">
            <div class="faq-a-inner">
              <p data-setting="faq_a3">Pada umumnya admin BP2TL menjawab dalam 1&ndash;3 hari kerja, tergantung kompleksitas pertanyaan. Anda dapat memantau statusnya kapan saja melalui kode tiket.</p>
            </div>
          </div>
        </div>
        <div class="faq-item">
          <button class="faq-q" aria-expanded="false">
            <span class="faq-num">4</span>
            <span data-setting="faq_q4">Bagaimana cara melacak status pertanyaan saya?</span>
            <span class="chev">+</span>
          </button>
          <div class="faq-a">
            <div class="faq-a-inner">
              <p data-setting="faq_a4">Buka tab &ldquo;Cek Status Pertanyaan atau Aduan&rdquo;, masukkan kode tiket yang Anda terima saat pertama kali mengirim pertanyaan atau pengaduan, lalu klik Cek Status.</p>
            </div>
          </div>
        </div>
        <div class="faq-item">
          <button class="faq-q" aria-expanded="false">
            <span class="faq-num">5</span>
            <span data-setting="faq_q5">Apakah layanan ini dikenakan biaya?</span>
            <span class="chev">+</span>
          </button>
          <div class="faq-a">
            <div class="faq-a-inner">
              <p data-setting="faq_a5">Tidak. Layanan pertanyaan dan pengaduan melalui BP2TL Jakarta ini sepenuhnya gratis untuk seluruh masyarakat.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="section" id="peta">
    <div class="wrap">
      <div class="section-head">
        <span class="kicker" data-setting="peta_kicker">Lokasi Kami</span>
        <h2 data-setting="peta_title">Peta Lokasi BP2TL Jakarta</h2>
      </div>
            <div class="peta-grid">
        <div class="peta-map">
          <iframe
            data-map-src="alamat_text"
            src="https://www.google.com/maps?q=Pelabuhan+Tanjung+Priok,+Jakarta+Utara&output=embed"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            title="Lokasi kantor BP2TL Jakarta di peta"></iframe>
        </div>
        <div class="peta-info">
          <div class="ico">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.1-7-11a7 7 0 0 1 14 0c0 4.9-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
          </div>
          <h3>Alamat kantor</h3>
          <p data-setting="alamat_text">Jl. Pelabuhan Raya No. 1, Tanjung Priok, Jakarta Utara 14310</p>
          <a class="btn btn-navy btn-sm" data-map-link="alamat_text" href="https://www.google.com/maps?q=Pelabuhan+Tanjung+Priok,+Jakarta+Utara" target="_blank" rel="noopener">Buka di Google Maps</a>
        </div>
      </div>
      </div>
    </div>
  </section>

</main>

<footer class="site-footer">
  <div class="footer-top">
    <div class="wrap footer-grid">
      <div>
        <div class="footer-brand">
          <span class="logo-group"><img src="{{ asset('img/logo-1.png') }}" alt="Logo Balai Pendidikan dan Pelatihan Transportasi Laut"><img src="{{ asset('img/logo-2.png') }}" alt="Logo Kementerian Perhubungan"><img src="{{ asset('img/logo-3.png') }}" alt="Logo Gunawisista Nimpuna Sistacara"></span>
          <strong>BP2TL Jakarta</strong>
        </div>
        <p data-setting="footer_desc">Balai Pelayanan Terpadu Transportasi Laut &mdash; melayani perizinan, informasi, dan pengaduan transportasi laut masyarakat Jakarta.</p>
      </div>
      <div>
        <h4>Tautan</h4>
        <ul>
          <li><a href="#beranda" data-setting="footer_link1_text" data-setting-href="footer_link1_href">Beranda</a></li>
          <li><a href="#tanya" data-setting="footer_link2_text" data-setting-href="footer_link2_href">Tanyakan Pertanyaan</a></li>
          <li><a href="#faq" data-setting="footer_link3_text" data-setting-href="footer_link3_href">Pertanyaan Populer</a></li>
          <li><a href="#peta" data-setting="footer_link4_text" data-setting-href="footer_link4_href">Peta Lokasi</a></li>
          <li><a href="{{ url('/admin') }}" data-setting="footer_link5_text" data-setting-href="footer_link5_href">Portal Admin</a></li>
        </ul>
      </div>
      <div>
        <h4>Kontak</h4>
        <ul>
          <li data-setting="footer_kontak_alamat">Jl. Pelabuhan Raya No. 1, Tanjung Priok</li>
          <li data-setting="footer_kontak_telepon">(021) 4301 000</li>
          <li data-setting="footer_kontak_email">layanan@bp2tl.jakarta.go.id</li>
        </ul>
      </div>
    </div>
  </div>
  <div class="wrap footer-bottom">
    <span data-setting="footer_copyright">&copy; 2026 BP2TL Jakarta. Seluruh hak dilindungi.</span>
    <span data-setting="footer_tagline">Prototipe layanan digital</span>
  </div>
</footer>

<!-- ================= CHATBOT FLOATING ================= -->
<button type="button" class="chatbot-fab" id="chatbotFab" aria-label="Buka asisten tanya jawab" aria-expanded="false" aria-controls="chatbotPanel">
  <svg class="icon-chat" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5Z"/></svg>
  <svg class="icon-close" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"/></svg>
</button>

<div class="chatbot-panel" id="chatbotPanel">
  <div class="chatbot-head">
    <span class="dot" aria-hidden="true"></span>
    <div>
      <strong>Chatbot BP2TL</strong>
      <span>Jawaban diambil dari database layanan</span>
    </div>
  </div>
  <div class="chatbot-messages" id="chatbotMessages"></div>
  <form class="chatbot-input-row" id="chatbotForm">
    <label for="chatbotInput" class="sr-only">Tulis pertanyaan Anda</label>
    <input type="text" id="chatbotInput" placeholder="Tulis pertanyaan Anda..." autocomplete="off">
    <button type="submit" aria-label="Kirim">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7Z"/></svg>
    </button>
  </form>
</div>

<script>window.BP2TL_API_BASE = @json(request()->getBasePath() . '/api'); window.BP2TL_URLS = { admin: @json(url('/admin')) };</script>
<script src="{{ asset('js/api.js') }}"></script>
<script src="{{ asset('js/main.js') }}"></script>
<script src="{{ asset('js/chatbot.js') }}"></script>
</body>
</html>