<?php

/**
 * Membuat ikon tab (favicon) dari logo BP2TL.
 * Jalankan SEKALI dari akar proyek:   php buat-favicon.php
 * Hasil: public/img/favicon-32.png, favicon-192.png, apple-touch-icon.png
 * Setelah selesai, file ini boleh dihapus.
 */

$sumber = __DIR__ . '/public/img/logo-1.png';
if (!is_file($sumber)) {
    exit("Tidak menemukan $sumber\n");
}
if (!function_exists('imagecreatefrompng')) {
    exit("Ekstensi GD belum aktif. Aktifkan 'gd' di Laragon: Menu > PHP > Extensions.\n");
}

$logo = imagecreatefrompng($sumber);
$lw = imagesx($logo);
$lh = imagesy($logo);

/** Kecilkan logo ke kanvas persegi $ukuran; $latar = [r, g, b] untuk latar solid, null = transparan. */
function buatIkon($logo, int $lw, int $lh, int $ukuran, ?array $latar = null, float $isi = 1.0)
{
    $kanvas = imagecreatetruecolor($ukuran, $ukuran);
    imagealphablending($kanvas, false);
    imagesavealpha($kanvas, true);

    if ($latar === null) {
        imagefill($kanvas, 0, 0, imagecolorallocatealpha($kanvas, 0, 0, 0, 127));
    } else {
        imagefill($kanvas, 0, 0, imagecolorallocate($kanvas, $latar[0], $latar[1], $latar[2]));
    }
    imagealphablending($kanvas, true);

    $sisi = (int) round($ukuran * $isi);            // $isi < 1 memberi ruang kosong di tepi
    $x = intdiv($ukuran - $sisi, 2);
    imagecopyresampled($kanvas, $logo, $x, $x, 0, 0, $sisi, $sisi, $lw, $lh);

    imagealphablending($kanvas, false);
    imagesavealpha($kanvas, true);

    return $kanvas;
}

$tujuan = [
    'favicon-32.png'       => buatIkon($logo, $lw, $lh, 32),
    'favicon-192.png'      => buatIkon($logo, $lw, $lh, 192),
    'apple-touch-icon.png' => buatIkon($logo, $lw, $lh, 180, [255, 255, 255], 0.88), // iOS tidak suka latar transparan
];

foreach ($tujuan as $nama => $gambar) {
    $jalur = __DIR__ . '/public/img/' . $nama;
    imagepng($gambar, $jalur, 9);
    printf("%-22s %4d x %-4d %6d byte\n", $nama, imagesx($gambar), imagesy($gambar), filesize($jalur));
}
echo "Selesai.\n";