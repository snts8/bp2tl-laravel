document.addEventListener('DOMContentLoaded', () => {
  const fab = document.getElementById('chatbotFab');
  const panel = document.getElementById('chatbotPanel');
  const messages = document.getElementById('chatbotMessages');
  const form = document.getElementById('chatbotForm');
  const input = document.getElementById('chatbotInput');

  let greeted = false;

  function openPanel(){
    panel.classList.remove('closing');
    panel.classList.add('open');
    fab.classList.add('open', 'seen');
    fab.setAttribute('aria-expanded', 'true');
    input.focus();
    if(!greeted){
      greeted = true;
      const typingEl = addTyping();
      setTimeout(() => {
        typingEl.remove();
        addBotBubble('Halo sobat BP2TL! Saya chat bot BP2TL. Tanyakan seputar BP2TL atau layanan lainnya. saya akan menjawab sesuai dengan pertanyaanmu.');
      }, 700);
    }
  }

  function closePanel(){
    panel.classList.remove('open');
    panel.classList.add('closing');
    fab.classList.remove('open');
    fab.setAttribute('aria-expanded', 'false');
    const finish = () => panel.classList.remove('closing');
    panel.addEventListener('animationend', (e) => { if(e.target === panel) finish(); }, { once: true });
    setTimeout(finish, 350); /* jaga-jaga kalau animationend tidak terpanggil */
  }

  fab.addEventListener('click', () => {
    if(panel.classList.contains('open')) closePanel();
    else openPanel();
  });

  async function tanya(text){
    addUserBubble(text);
    const typingEl = addTyping();

    const res = await bp2tlTanyaChatbot(text);
    typingEl.remove();

    if(!res.success){
      addBotBubble(res.message || 'Maaf, terjadi kendala saat menghubungi server. Coba lagi sebentar lagi.');
      return;
    }

    if(res.ditemukan){
      addBotBubble(res.jawaban);
    }else if(Array.isArray(res.saran) && res.saran.length){
      addSuggestions(res.saran);
    }else{
      addBotBubble('Belum ada jawaban untuk pertanyaan ini di database kami. Anda bisa mengajukannya sebagai pertanyaan resmi lewat tab "Ajukan Pertanyaan" di halaman ini supaya dijawab langsung oleh admin.');
    }
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const text = input.value.trim();
    if(!text) return;
    input.value = '';
    input.focus();
    tanya(text);
  });

  /* Pertanyaan yang mirip/ambigu: tawarkan pilihan yang bisa diklik */
  function addSuggestions(list){
    const el = document.createElement('div');
    el.className = 'chat-bubble bot';

    const lead = document.createElement('div');
    lead.textContent = 'Saya belum menemukan jawaban yang pasti. Apakah maksud Anda salah satu dari ini?';

    const wrap = document.createElement('div');
    wrap.className = 'chat-suggest-list';
    list.forEach((q) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'chat-suggest';
      b.textContent = q;
      b.addEventListener('click', () => tanya(q));
      wrap.appendChild(b);
    });

    const foot = document.createElement('span');
    foot.className = 'src';
    foot.textContent = 'Bukan yang dimaksud? Ajukan sebagai pertanyaan resmi lewat tab "Ajukan Pertanyaan".';

    el.appendChild(lead);
    el.appendChild(wrap);
    el.appendChild(foot);
    messages.appendChild(el);
    scrollToBottom();
  }

  function addUserBubble(text){
    const el = document.createElement('div');
    el.className = 'chat-bubble user';
    el.textContent = text;
    messages.appendChild(el);
    scrollToBottom();
    return el;
  }
  /* ---------------------------------------------------------------
     formatBotText — mengubah teks panjang menjadi HTML rapi.
     Mendukung pola data nyata dari DB:
       • Baris diawali "- " / "• " / "· " / "* "  → <ul><li>
       • Baris diawali "N. "                        → <ol><li>
       • "Label:" sendirian di baris (Visi:, Misi:) → <h4> label
       • "**teks**" / "# teks"                     → heading / bold
       • **kata** di dalam kalimat                 → <strong>
       • "Nama : Nilai" (key-value)                → baris definisi
       • Baris kosong antar blok                   → pemisah paragraf
       • Teks panjang otomatis dibagi per kalimat  → <p> yang nyaman dibaca
     --------------------------------------------------------------- */
  function formatBotText(raw){
    if(!raw || !raw.trim()) return '';

    // Escape HTML — harus dilakukan pertama sebelum apapun
    function esc(s){
      return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    // Inline: **bold**, `code`
    function inline(s){
      return s
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/`(.+?)`/g, '<code>$1</code>');
    }

    // Normalisasi bullet karakter "·" (middle dot U+00B7) → "-"
    // dan " - " di awal baris dengan spasi aneh
    const normalized = raw
      .replace(/\r\n/g, '\n').replace(/\r/g, '\n')
      .replace(/^[ \t]*·[ \t]*/gm, '- ')          // · → bullet biasa
      .replace(/^[ \t]*•[ \t]*/gm, '- ')           // • → bullet biasa
      .replace(/^[ \t]*–[ \t]*/gm, '- ')           // – (em-dash) → bullet
      .replace(/^[ \t]*\*[ \t]+/gm, '- ')          // * item → bullet
      .replace(/\n{3,}/g, '\n\n');                  // max 2 baris kosong berurutan

    const lines = normalized.split('\n');
    const html  = [];
    let inUl = false, inOl = false, inP = false;

    function closeUl(){ if(inUl){ html.push('</ul>'); inUl = false; } }
    function closeOl(){ if(inOl){ html.push('</ol>'); inOl = false; } }
    function closeP() { if(inP) { html.push('</p>'); inP = false; } }
    function closeLists(){ closeUl(); closeOl(); }
    function closeAll() { closeP(); closeLists(); }

    lines.forEach(function(raw){
      const line = raw.trimEnd();
      const trimmed = line.trim();

      // ── Baris kosong ────────────────────────────────────────────
      if(trimmed === ''){ closeAll(); return; }

      // ── Markdown heading: # Judul ───────────────────────────────
      const hMatch = trimmed.match(/^(#{1,3})\s+(.+)/);
      if(hMatch){
        closeAll();
        const lvl = Math.min(hMatch[1].length + 2, 5);
        html.push('<h' + lvl + ' class="cb-h">' + inline(esc(hMatch[2])) + '</h' + lvl + '>');
        return;
      }

      // ── Label tunggal di akhir baris dengan titik dua ───────────
      // Contoh: "Visi:", "Misi:", "Pejabat:", "Fungsi:"
      // Syarat: TIDAK ada teks tambahan setelah titik dua (atau hanya spasi)
      const labelMatch = trimmed.match(/^([A-Za-zÀ-öø-ÿ\s]{2,40}):$/);
      if(labelMatch){
        closeAll();
        html.push('<p class="cb-label">' + esc(labelMatch[1].trim()) + '</p>');
        return;
      }

      // ── Bold penuh satu baris: **Teks** ─────────────────────────
      const boldFull = trimmed.match(/^\*\*(.+)\*\*$/);
      if(boldFull){
        closeAll();
        html.push('<p class="cb-label">' + esc(boldFull[1]) + '</p>');
        return;
      }

      // ── Ordered list: "1. ", "2. " ──────────────────────────────
      const olMatch = trimmed.match(/^(\d+)\.\s+(.+)/);
      if(olMatch){
        closeP(); closeUl();
        if(!inOl){ html.push('<ol class="cb-list">'); inOl = true; }
        html.push('<li>' + inline(esc(olMatch[2])) + '</li>');
        return;
      }

      // ── Bullet list: "- teks" ────────────────────────────────────
      const ulMatch = trimmed.match(/^-\s+(.+)/);
      if(ulMatch){
        closeP(); closeOl();
        if(!inUl){ html.push('<ul class="cb-list">'); inUl = true; }
        html.push('<li>' + inline(esc(ulMatch[1])) + '</li>');
        return;
      }

      // ── Key : Value — "Nama Jabatan : Nilai" ────────────────────
      // Hanya di luar list, dan hanya bila bagian kiri <= 50 karakter
      const kvMatch = trimmed.match(/^([^:]{2,50})\s*:\s+(.+)/);
      if(kvMatch && !inUl && !inOl){
        closeAll();
        html.push(
          '<p class="cb-kv"><span class="cb-kv-k">' + esc(kvMatch[1].trim()) + ':</span> ' +
          inline(esc(kvMatch[2].trim())) + '</p>'
        );
        return;
      }

      // ── Teks biasa → paragraf ───────────────────────────────────
      closeLists();
      if(!inP){ html.push('<p>'); inP = true; }
      else { html.push(' '); }                    // sambung dalam <p> yang sama
      html.push(inline(esc(trimmed)));
    });

    closeAll();
    return html.join('');
  }

  function addBotBubble(text){
    const el = document.createElement('div');
    el.className = 'chat-bubble bot';
    el.innerHTML = formatBotText(text);
    messages.appendChild(el);
    scrollToBottom();
    return el;
  }
  function addTyping(){
    const el = document.createElement('div');
    el.className = 'chat-typing';
    el.innerHTML = '<span></span><span></span><span></span>';
    messages.appendChild(el);
    scrollToBottom();
    return el;
  }
  function scrollToBottom(){
    messages.scrollTop = messages.scrollHeight;
  }
});