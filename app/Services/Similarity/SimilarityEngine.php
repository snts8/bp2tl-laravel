<?php

namespace App\Services\Similarity;

/**
 * Mesin pencocok pertanyaan serupa — port 1:1 dari api/similarity.php lama.
 *
 * Kelas ini MURNI (tidak menyentuh database/Laravel) sehingga mudah diuji:
 * ia menerima daftar entri pengetahuan dan mengembalikan hasil pencocokan.
 * Pemuatan entri dari database ada di SimilarityService.
 *
 * Cara menilai kemiripan (bukan sekadar "ada satu kata yang sama"):
 *  - Kalimat dipecah jadi kata kunci; kata umum dibuang, sinonim disamakan.
 *  - Tiap kata dibobot: kata langka (mis. nama diklat "oru") jauh lebih
 *    bernilai daripada kata umum ("diklat", "biaya").
 *  - Salah ketik ringan pada kata panjang ditoleransi (Levenshtein).
 *  - Dua jawaban berbeda dengan skor nyaris sama = ambigu -> tawarkan pilihan.
 *
 * PENTING — jangan menambahkan `declare(strict_types=1)` di file ini.
 * Kunci array PHP yang berupa angka ("2026", "1") otomatis menjadi int; kode
 * ini mengandalkan koersi longgar (string <-> int) agar sama persis dengan
 * perilaku aplikasi lama.
 *
 * Hasil cari():
 *   status 'match' : ada pertanyaan yang cukup serupa -> pakai jawabannya
 *   status 'saran' : mirip sebagian/ambigu -> tampilkan pilihan pertanyaan
 *   status 'none'  : tidak ada yang serupa
 */
final class SimilarityEngine
{
    public const AMBANG_COCOK   = 0.70; // skor minimal untuk dianggap "pertanyaan yang sama"
    public const AMBANG_SARAN   = 0.45; // skor minimal untuk ditawarkan sebagai saran
    public const SELISIH_AMBIGU = 0.06; // selisih skor terlalu tipis = ambigu
    public const BOBOT_ASING    = 0.30; // pengali bobot untuk kata yang tidak ada di database mana pun

    /** @var array<string,int>|null */
    private static ?array $stopwords = null;

    /** @var array<string,string>|null */
    private static ?array $synonyms = null;

    /** @return array<string,int> */
    public static function stopwords(): array
    {
        if (self::$stopwords === null) {
            self::$stopwords = array_flip([
            'ada', 'adalah', 'admin', 'agar', 'akan', 'aku', 'apa', 'apakah',
            'atau', 'bagaimana', 'banget', 'bantu', 'bantuannya', 'bapak', 'belum', 'berapaan',
            'berpa', 'bgmn', 'bisa', 'bisakah', 'brp', 'bu', 'cara', 'dalam',
            'dan', 'dari', 'deh', 'dengan', 'dgn', 'di', 'dong', 'gimana',
            'hai', 'halo', 'harus', 'ibu', 'info', 'informasi', 'ingin', 'ini',
            'itu', 'jika', 'juga', 'kah', 'kak', 'kalau', 'kasih', 'ke',
            'kok', 'lah', 'lalu', 'lewat', 'makasih', 'malam', 'masih', 'mau',
            'melalui', 'min', 'minta', 'mohon', 'nih', 'nya', 'oleh', 'pada',
            'pagi', 'pak', 'para', 'perlu', 'saja', 'saya', 'sebagai', 'sebuah',
            'sekali', 'selamat', 'siang', 'sih', 'sore', 'suatu', 'sudah', 'supaya',
            'tdk', 'tentang', 'terima', 'tidak', 'tolong', 'tsb', 'untuk', 'utk',
            'via', 'ya', 'yaa', 'yang', 'yg',
            // kata pengantar/basa-basi yang tidak menentukan topik pertanyaan
            'assalamualaikum', 'bertanya', 'ditanyakan', 'hallo', 'kepo', 'mengenai',
            'menanyakan', 'mengetahui', 'ketahui', 'mo', 'nanya', 'nanyain', 'penasaran',
            'pengen', 'pengin', 'perihal', 'permisi', 'pertanyaan', 'pingin', 'seputar',
            'sebenarnya', 'sebetulnya', 'soal', 'tahu', 'tanya', 'tanyakan', 'terkait',
            'waalaikumsalam'
            ]);
        }

        return self::$stopwords;
    }

    /** @return array<string,string> */
    public static function synonyms(): array
    {
        if (self::$synonyms === null) {
            self::$synonyms = [
            'harga' => 'biaya', 'tarif' => 'biaya', 'ongkos' => 'biaya',
            'bayar' => 'bayar', 'membayar' => 'bayar', 'pembayaran' => 'bayar',
            'pembayarannya' => 'bayar', 'persyaratan' => 'syarat', 'persyaratannya' => 'syarat',
            'syaratnya' => 'syarat', 'mendaftar' => 'daftar', 'pendaftaran' => 'daftar',
            'registrasi' => 'daftar', 'mendaftarkan' => 'daftar', 'pelatihan' => 'diklat',
            'kursus' => 'diklat', 'pendidikan' => 'diklat', 'daring' => 'online',
            'luring' => 'offline', 'dikat' => 'diklat', 'diklat' => 'diklat',
            'lokasi' => 'kantor', 'alamat' => 'kantor', 'biayanya' => 'biaya',
            'hargany' => 'biaya', 'hargaya' => 'biaya'
            ];
        }

        return self::$synonyms;
    }

    /**
     * Memecah teks menjadi himpunan kata kunci: [kata => true].
     *
     * @return array<int|string,bool>
     */
    public function tokens(string $teks): array
    {
        $teks  = mb_strtolower($teks, 'UTF-8');
        $words = preg_split('/[^\p{L}\p{N}]+/u', $teks, -1, PREG_SPLIT_NO_EMPTY);
        if ($words === false) {
            return [];
        }

        $stop = self::stopwords();
        $syn  = self::synonyms();
        $out  = [];
        foreach ($words as $w) {
            $w = (string) $w;
            if (isset($syn[$w])) {
                $w = $syn[$w];
            }
            if (isset($stop[$w])) {
                continue;
            }
            $out[$w] = true;
        }

        return $out;
    }

    /** Bobot kata: makin jarang muncul di database, makin besar. */
    private function bobot(array $df, int $n, $kata): float
    {
        $d = $df[$kata] ?? 0;
        if ($d < 1) {
            // Kata yang tidak muncul di pertanyaan/kata kunci mana pun tidak bisa
            // membedakan satu entri dari yang lain (biasanya basa-basi atau salah
            // ketik). Bobot penuh akan menenggelamkan kata kunci yang sebenarnya.
            return log(1 + $n) * self::BOBOT_ASING;
        }

        return log(1 + $n / $d);
    }

    /**
     * Cari kata di $himpunan yang mirip $kata (salah ketik ringan).
     * Hanya untuk kata huruf saja & panjang >= 5, supaya kode seperti
     * "att" vs "ant" atau "1x" vs "2x" tidak dianggap sama.
     */
    private function kataMirip(string $kata, array $himpunan): ?string
    {
        $len = mb_strlen($kata, 'UTF-8');
        if ($len < 5 || !preg_match('/^\p{L}+$/u', $kata)) {
            return null;
        }
        $batas = $len < 9 ? 1 : 2;
        foreach ($himpunan as $c => $_) {
            $c = (string) $c;
            if (!preg_match('/^\p{L}+$/u', $c)) {
                continue;
            }
            if (abs(mb_strlen($c, 'UTF-8') - $len) > $batas) {
                continue;
            }
            if (levenshtein($kata, $c) <= $batas) {
                return $c;
            }
        }

        return null;
    }

    /**
     * Skor kemiripan 0..1 antara kata-kata pertanyaan pengguna ($qt) dan satu entri.
     */
    private function skor(array $qt, array &$x, array $df, int $n): float
    {
        $num    = 0.0;
        $den    = 0.0;
        $cocokE = [];

        foreach ($qt as $t => $_) {
            $t   = (string) $t;
            $wt  = $this->bobot($df, $n, $t);
            $den += $wt;
            $c   = 0.0;

            if (isset($x['q'][$t])) {
                $c = 1.0;
                $cocokE[$t] = true;
            } else {
                $f = $this->kataMirip($t, $x['q']);
                if ($f !== null) {
                    $c = 0.9;
                    $cocokE[$f] = true;
                } elseif (isset($x['k'][$t])) {
                    $c = 0.8;
                } else {
                    if ($x['a'] === null) {
                        $x['a'] = $this->tokens($x['j']); // dihitung hanya kalau perlu
                    }
                    if (isset($x['a'][$t])) {
                        $c = 0.45;
                    }
                }
            }
            $num += $wt * $c;
        }

        $cq = $den > 0 ? $num / $den : 0.0;

        $de = 0.0;
        foreach ($x['q'] as $e => $_) {
            $de += $this->bobot($df, $n, (string) $e);
        }
        $ne = 0.0;
        foreach ($cocokE as $e => $_) {
            $ne += $this->bobot($df, $n, (string) $e);
        }
        $ce = $de > 0 ? $ne / $de : 0.0;

        return $cq * (0.55 + 0.45 * $ce);
    }

    /**
     * Cari pertanyaan yang serupa di seluruh basis pengetahuan.
     *
     * @param  array<int,array{code:?string,p:string,j:string,kk:string}>  $entries
     *         p = pertanyaan, j = jawaban, kk = kata kunci, code = kode tiket (null untuk data latih)
     * @return array{status:string,skor:float,pertanyaan:?string,jawaban:?string,code:?string,saran:array,saran_detail:array}
     */
    public function cari(string $teks, array $entries): array
    {
        $hasil = [
            'status'       => 'none',
            'skor'         => 0.0,
            'pertanyaan'   => null,
            'jawaban'      => null,
            'code'         => null,
            'saran'        => [],
            'saran_detail' => [], // sama dengan 'saran' tapi lengkap dengan jawabannya
        ];

        $qt = $this->tokens($teks);
        if (!$qt) {
            return $hasil;
        }

        $n = count($entries);
        if ($n === 0) {
            return $hasil;
        }

        // --- token tiap entri + hitung sebaran kata (df) untuk pembobotan ---
        $df = [];
        foreach ($entries as $i => $x) {
            $q = $this->tokens($x['p']);
            $k = $this->tokens($x['kk']);
            $entries[$i]['q'] = $q;
            $entries[$i]['k'] = $k;
            $entries[$i]['a'] = null;
            foreach ($q + $k as $kata => $_) {
                $df[$kata] = ($df[$kata] ?? 0) + 1;
            }
        }

        // --- nilai semua entri ---
        $skor = [];
        foreach ($entries as $i => $x) {
            $skor[$i] = $this->skor($qt, $entries[$i], $df, $n);
        }
        arsort($skor);
        $urut = array_slice($skor, 0, 8, true); // [indeks => skor], skor terbesar dulu

        $idxTerbaik  = null;
        $skorTerbaik = 0.0;
        foreach ($urut as $i => $s) {
            $idxTerbaik  = $i;
            $skorTerbaik = $s;
            break;
        }
        if ($idxTerbaik === null || $skorTerbaik < self::AMBANG_SARAN) {
            return $hasil;
        }

        $terbaik = $entries[$idxTerbaik];

        // pesaing terdekat yang jawabannya BERBEDA (kalau terlalu dekat = ambigu)
        $pesaing = null;
        foreach ($urut as $i => $s) {
            if ($i === $idxTerbaik) {
                continue;
            }
            if ($entries[$i]['j'] !== $terbaik['j']) {
                $pesaing = $s;
                break;
            }
        }
        $ambigu = $pesaing !== null
            && ($skorTerbaik - $pesaing) < self::SELISIH_AMBIGU
            && $skorTerbaik < 0.97;

        $hasil['skor'] = $skorTerbaik;

        if ($skorTerbaik >= self::AMBANG_COCOK && !$ambigu) {
            $hasil['status']     = 'match';
            $hasil['pertanyaan'] = $terbaik['p'];
            $hasil['jawaban']    = $terbaik['j'];
            $hasil['code']       = $terbaik['code'];

            return $hasil;
        }

        // saran: sampai 3 pertanyaan berbeda yang cukup mirip
        $sudah = [];
        foreach ($urut as $i => $s) {
            if ($s < self::AMBANG_SARAN || count($hasil['saran']) >= 3) {
                break;
            }
            $teksSaran = $entries[$i]['p'];
            if (isset($sudah[$teksSaran])) {
                continue;
            }
            $sudah[$teksSaran]       = true;
            $hasil['saran'][]        = $teksSaran;
            $hasil['saran_detail'][] = ['pertanyaan' => $teksSaran, 'jawaban' => $entries[$i]['j']];
        }
        $hasil['status'] = 'saran';

        return $hasil;
    }
}
