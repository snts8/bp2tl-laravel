<?php

namespace App\Services\Similarity;

use App\Models\ChatbotFaq;
use App\Models\PertanyaanHistory;

/**
 * Memuat basis pengetahuan dari database lalu menyerahkannya ke SimilarityEngine.
 *
 * Sumber pengetahuan (dibaca setiap kali ada pertanyaan, sama seperti aplikasi lama):
 *  1) chatbot_faq        : data latih chatbot (hasil impor + input manual admin)
 *  2) pertanyaan_history : salinan permanen tiket yang SUDAH dijawab admin
 *     (kecuali "Pengaduan Layanan"). Diisi saat admin menjawab, ikut terhapus saat
 *     admin menghapus satu tiket, tetapi TIDAK terhapus saat Super Admin hapus massal.
 *
 * Urutan entri (chatbot_faq dulu berurut id, lalu history berurut code) sengaja
 * dibuat eksplisit karena menentukan hasil saat dua entri berskor sama.
 */
class SimilarityService
{
    public function __construct(private readonly SimilarityEngine $engine)
    {
    }

    /**
     * @return array{status:string,skor:float,pertanyaan:?string,jawaban:?string,code:?string,saran:array,saran_detail:array}
     */
    public function cariSerupa(string $teks): array
    {
        // Kalimat yang isinya hanya kata umum/basa-basi: tak perlu membaca database.
        if ($this->engine->tokens($teks) === []) {
            return $this->engine->cari($teks, []);
        }

        return $this->engine->cari($teks, $this->muatEntri());
    }

    /**
     * @return array<int,array{code:?string,p:string,j:string,kk:string}>
     */
    public function muatEntri(): array
    {
        $entries = [];

        $faq = ChatbotFaq::query()->toBase()->orderBy('id')->get(['pertanyaan', 'jawaban', 'kata_kunci']);
        foreach ($faq as $r) {
            $entries[] = [
                'code' => null,
                'p'    => (string) $r->pertanyaan,
                'j'    => (string) $r->jawaban,
                'kk'   => (string) ($r->kata_kunci ?? ''),
            ];
        }

        // Sumber tiket = pertanyaan_history (bukan tabel pertanyaan), supaya otak chatbot
        // tetap utuh walau Super Admin mengosongkan seluruh tabel tiket.
        $tiket = PertanyaanHistory::query()->toBase()
            ->whereNotNull('jawaban')
            ->where('jawaban', '<>', '')
            ->orderBy('code')
            ->get(['code', 'pertanyaan', 'jawaban']);
        foreach ($tiket as $r) {
            $entries[] = [
                'code' => (string) $r->code,
                'p'    => (string) $r->pertanyaan,
                'j'    => (string) $r->jawaban,
                'kk'   => '',
            ];
        }

        return $entries;
    }
}
