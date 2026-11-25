/* =========================================================
   BP2TL Jakarta — leave-guard.js
   Menampilkan konfirmasi bawaan browser ("Yakin ingin memuat
   ulang / meninggalkan halaman ini?") saat pengguna menekan
   F5, tombol refresh, menutup tab, atau berpindah halaman.

   Catatan:
   - Teks dialog TIDAK bisa diubah; browser modern memakai teks
     standarnya sendiri demi keamanan.
   - Browser hanya menampilkan dialog bila pengguna sudah pernah
     berinteraksi (klik/ketik) di halaman. Ini aturan browser.
   - Untuk navigasi yang disengaja dari kode kita sendiri (mis.
     pintasan ketik "admin"), set:  window.bp2tlAllowLeave = true;
     sebelum mengubah window.location.
   ========================================================= */
(function(){
  'use strict';

  window.bp2tlAllowLeave = false;

  window.addEventListener('beforeunload', function(e){
    if(window.bp2tlAllowLeave) return;
    e.preventDefault();
    e.returnValue = ''; // diperlukan oleh Chrome/Edge/Safari
    return '';          // diperlukan oleh browser lama
  });
})();
