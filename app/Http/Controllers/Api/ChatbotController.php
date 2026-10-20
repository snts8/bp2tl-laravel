<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Services\Similarity\SimilarityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * POST /api/chatbot (api/chatbot.php). Jawaban berasal dari basis pengetahuan
 * di database, BUKAN dari AI.
 *
 *   ditemukan = true  -> jawaban langsung ditampilkan
 *   ditemukan = false -> 'saran' berisi sampai 3 pertanyaan yang mungkin dimaksud
 *                        (boleh kosong = benar-benar tidak ada yang serupa)
 */
class ChatbotController extends Controller
{
    use RespondsWithJson;

    public function ask(Request $request, SimilarityService $similarity): JsonResponse
    {
        $pesan = trim((string) $request->input('pesan', ''));

        if ($pesan === '') {
            return $this->ok(['ditemukan' => false, 'saran' => []]);
        }

        try {
            $cocok = $similarity->cariSerupa($pesan);
        } catch (Throwable $e) {
            report($e);

            return $this->fail('Maaf, chatbot sedang mengalami kendala. Coba lagi sebentar lagi.', 500);
        }

        if ($cocok['status'] === 'match') {
            return $this->ok([
                'ditemukan'          => true,
                'pertanyaan_terkait' => $cocok['pertanyaan'],
                'jawaban'            => $cocok['jawaban'],
                'code'               => $cocok['code'], // null kalau sumbernya data latih chatbot_faq
                'saran'              => [],
            ]);
        }

        return $this->ok([
            'ditemukan' => false,
            'saran'     => $cocok['saran'],
        ]);
    }
}
