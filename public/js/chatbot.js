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
  function addBotBubble(text){
    const el = document.createElement('div');
    el.className = 'chat-bubble bot';
    el.textContent = text;
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