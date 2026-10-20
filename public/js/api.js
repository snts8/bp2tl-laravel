/* =========================================================
   BP2TL Jakarta — api.js  (versi Laravel)
   Lapisan penghubung front-end ke backend Laravel (/api/*).
   Nama-nama fungsi bp2tl* SAMA dengan versi PHP native, jadi
   main.js, chatbot.js, admin.js, superadmin.js tidak perlu diubah.

   Perbedaan dari versi lama:
   - Alamat endpoint bersih (/api/ajukan, bukan api/ajukan.php).
   - Setiap POST mengirim header X-CSRF-TOKEN (dari <meta name="csrf-token">).
     Bila server menjawab 419 (token kedaluwarsa), token disegarkan otomatis
     dan permintaan diulang satu kali.
   ========================================================= */

/* Diisi oleh Blade: <script>window.BP2TL_API_BASE = "/.../api"</script> */
const BP2TL_API_BASE = (typeof window !== 'undefined' && window.BP2TL_API_BASE) ? window.BP2TL_API_BASE : '/api';

/**
 * "Jalur" sesi halaman ini: 'admin' atau 'superadmin' (diset lewat
 * window.BP2TL_REALM di admin/superadmin). Dikirim sebagai ?realm=...
 */
function bp2tlRealm(){
  return (typeof window !== 'undefined' && window.BP2TL_REALM) ? window.BP2TL_REALM : 'admin';
}

function bp2tlCsrfMeta(){
  return document.querySelector('meta[name="csrf-token"]');
}

async function bp2tlRefreshCsrf(){
  try{
    const res = await fetch(`${BP2TL_API_BASE}/csrf-token`, {
      credentials:'same-origin',
      headers:{ 'Accept':'application/json' }
    });
    const json = await res.json();
    const meta = bp2tlCsrfMeta();
    if(json && json.token && meta){
      meta.setAttribute('content', json.token);
      return true;
    }
  }catch(e){ /* abaikan */ }
  return false;
}

async function bp2tlRequest(method, url, body, canRetry = true){
  try{
    const headers = { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' };
    const options = { method, headers, credentials:'same-origin' };
    if(method !== 'GET'){
      const meta = bp2tlCsrfMeta();
      headers['Content-Type'] = 'application/json';
      headers['X-CSRF-TOKEN'] = meta ? meta.getAttribute('content') : '';
      options.body = JSON.stringify(body ?? {});
    }
    const res = await fetch(url, options);
    if(res.status === 419 && canRetry && await bp2tlRefreshCsrf()){
      return bp2tlRequest(method, url, body, false);
    }
    return await res.json();
  }catch(err){
    console.error('Gagal menghubungi server:', err);
    return { success:false, message:'Tidak dapat terhubung ke server. Pastikan server sedang berjalan.' };
  }
}

function bp2tlApiPost(endpoint, body){
  return bp2tlRequest('POST', `${BP2TL_API_BASE}/${endpoint}?realm=${encodeURIComponent(bp2tlRealm())}`, body);
}

function bp2tlApiGet(endpoint, params = {}){
  const qs = new URLSearchParams({ ...params, realm: bp2tlRealm() }).toString();
  return bp2tlRequest('GET', `${BP2TL_API_BASE}/${endpoint}${qs ? '?' + qs : ''}`);
}

/* ---------- Publik: ajukan & lacak pertanyaan ---------- */
function bp2tlAjukan(data){ return bp2tlApiPost('ajukan', data); }
function bp2tlLacak(code){ return bp2tlApiGet('lacak', { kode: code }); }

/* ---------- Publik: chatbot ---------- */
function bp2tlTanyaChatbot(pesan){ return bp2tlApiPost('chatbot', { pesan }); }

/* ---------- Sesi ---------- */
function bp2tlLogin(username, password){ return bp2tlApiPost('login', { username, password }); }
function bp2tlLogout(){ return bp2tlApiPost('logout', {}); }
function bp2tlCheckSession(){ return bp2tlApiGet('session'); }

/* ---------- Admin: tiket ---------- */
function bp2tlList(filters = {}){ return bp2tlApiGet('admin/tiket', filters); }
function bp2tlJawab(code, jawaban){ return bp2tlApiPost('admin/jawab', { code, jawaban }); }
function bp2tlHapus(code){ return bp2tlApiPost('admin/hapus', { code }); }

/* ---------- Admin: data latih chatbot (menu "Chatbot") ---------- */
function bp2tlChatbotFaqList(search = ''){
  return bp2tlApiGet('admin/chatbot-faq', search ? { search } : {});
}
function bp2tlChatbotFaqCreate(pertanyaan, jawaban, kataKunci){
  return bp2tlApiPost('admin/chatbot-faq', { action:'create', pertanyaan, jawaban, kata_kunci: kataKunci });
}
function bp2tlChatbotFaqUpdate(id, pertanyaan, jawaban, kataKunci){
  return bp2tlApiPost('admin/chatbot-faq', { action:'update', id, pertanyaan, jawaban, kata_kunci: kataKunci });
}
function bp2tlChatbotFaqDelete(id){
  return bp2tlApiPost('admin/chatbot-faq', { action:'delete', id });
}

/* ---------- Pengaturan teks halaman ---------- */
function bp2tlSettingsGet(){ return bp2tlApiGet('settings'); }
function bp2tlSettingsSave(data){ return bp2tlApiPost('admin/settings', data); }

/* ---------- Super Admin ---------- */
function bp2tlSuperadminLog(filters = {}){ return bp2tlApiGet('superadmin/log', filters); }
function bp2tlSuperadminHistory(filters = {}){ return bp2tlApiGet('superadmin/history', filters); }
function bp2tlAccountsList(){ return bp2tlApiGet('superadmin/accounts'); }
function bp2tlAccountCreate(username, password, role){
  return bp2tlApiPost('superadmin/accounts', { action:'create', username, password, role });
}
function bp2tlAccountUpdate(id, username, password, role){
  return bp2tlApiPost('superadmin/accounts', { action:'update', id, username, password, role });
}
function bp2tlAccountDelete(id){
  return bp2tlApiPost('superadmin/accounts', { action:'delete', id });
}
function bp2tlResetLog(){ return bp2tlApiPost('superadmin/reset', { action:'hapus_log' }); }
function bp2tlResetPertanyaan(){ return bp2tlApiPost('superadmin/reset', { action:'hapus_pertanyaan' }); }

/* ---------- Util ---------- */
function bp2tlFormatDate(mysqlDateTime){
  if(!mysqlDateTime) return '-';
  const iso = mysqlDateTime.includes('T') ? mysqlDateTime : mysqlDateTime.replace(' ', 'T');
  const d = new Date(iso);
  if(isNaN(d.getTime())) return mysqlDateTime;
  return d.toLocaleDateString('id-ID', { day:'numeric', month:'long', year:'numeric' }) +
    ' pukul ' + d.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' });
}

function bp2tlEscapeHtml(str){
  const div = document.createElement('div');
  div.textContent = str == null ? '' : str;
  return div.innerHTML;
}

/**
 * Membedakan MEMUAT ULANG (refresh/F5) dari KUNJUNGAN BARU ke halaman
 * (mis. lewat pintasan ketik "admin" di beranda, klik tautan, atau tab
 * baru). Dipakai supaya:
 * - Refresh saat sudah login -> tetap di dashboard (sesi dipertahankan).
 * - Kunjungan baru ke halaman -> SELALU tampil form login, walau
 *   browser masih menyimpan sesi admin lain yang lupa "Keluar".
 */
function bp2tlIsPageReload(){
  try{
    const entries = performance.getEntriesByType && performance.getEntriesByType('navigation');
    if(entries && entries.length){
      return entries[0].type === 'reload';
    }
    // Fallback untuk browser lama
    if(performance.navigation){
      return performance.navigation.type === performance.navigation.TYPE_RELOAD;
    }
  }catch(e){ /* abaikan, anggap bukan reload */ }
  return false;
}