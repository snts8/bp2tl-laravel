<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\ChatbotFaq;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Data latih chatbot (chatbot_faq.php). Terpisah dari tiket masyarakat.
 *   GET  /api/admin/chatbot-faq?search=...
 *   POST /api/admin/chatbot-faq  { action: create|update|delete, ... }
 */
class ChatbotFaqController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $query = ChatbotFaq::query()
            ->select(['id', 'pertanyaan', 'jawaban', 'kata_kunci', 'created_at', 'updated_at']);

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('pertanyaan', 'like', $like)
                  ->orWhere('jawaban', 'like', $like)
                  ->orWhere('kata_kunci', 'like', $like);
            });
        }

        return $this->ok(['data' => $query->orderByDesc('created_at')->orderByDesc('id')->get()]);
    }

    public function handle(Request $request): JsonResponse
    {
        return match ((string) $request->input('action', '')) {
            'create' => $this->create($request),
            'update' => $this->update($request),
            'delete' => $this->delete($request),
            default  => $this->fail('Aksi tidak dikenali.', 400),
        };
    }

    private function create(Request $request): JsonResponse
    {
        [$pertanyaan, $jawaban, $kataKunci] = $this->fields($request);

        if ($pertanyaan === '' || $jawaban === '') {
            return $this->fail('Pertanyaan dan jawaban wajib diisi.', 400);
        }
        if ($err = $this->tooLong($pertanyaan, $kataKunci)) {
            return $err;
        }

        $faq = ChatbotFaq::create([
            'pertanyaan' => $pertanyaan,
            'jawaban'    => $jawaban,
            'kata_kunci' => $kataKunci !== '' ? $kataKunci : null,
        ]);

        ActivityLogger::log('tambah_faq_chatbot', "Menambahkan data latih chatbot: \"{$pertanyaan}\".", $request->user('admin'));

        return $this->ok(['id' => (int) $faq->id]);
    }

    private function update(Request $request): JsonResponse
    {
        $id = (int) $request->input('id', 0);
        [$pertanyaan, $jawaban, $kataKunci] = $this->fields($request);

        if ($id <= 0 || $pertanyaan === '' || $jawaban === '') {
            return $this->fail('Data tidak lengkap.', 400);
        }
        if ($err = $this->tooLong($pertanyaan, $kataKunci)) {
            return $err;
        }

        $faq = ChatbotFaq::find($id);
        if (! $faq) {
            return $this->fail('Data tidak ditemukan atau sudah dihapus.', 404);
        }

        $faq->update([
            'pertanyaan' => $pertanyaan,
            'jawaban'    => $jawaban,
            'kata_kunci' => $kataKunci !== '' ? $kataKunci : null,
        ]);

        ActivityLogger::log('ubah_faq_chatbot', "Memperbarui data latih chatbot: \"{$pertanyaan}\".", $request->user('admin'));

        return $this->ok();
    }

    private function delete(Request $request): JsonResponse
    {
        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            return $this->fail('ID data wajib diisi.', 400);
        }

        $faq = ChatbotFaq::find($id);
        if (! $faq) {
            return $this->fail('Data tidak ditemukan atau sudah dihapus.', 404);
        }

        $teks = $faq->pertanyaan;
        $faq->delete();

        ActivityLogger::log('hapus_faq_chatbot', "Menghapus data latih chatbot: \"{$teks}\".", $request->user('admin'));

        return $this->ok();
    }

    /** @return array{0:string,1:string,2:string} */
    private function fields(Request $request): array
    {
        return [
            trim((string) $request->input('pertanyaan', '')),
            trim((string) $request->input('jawaban', '')),
            trim((string) $request->input('kata_kunci', '')),
        ];
    }

    /** Batas kolom database (varchar 500 / 255) -> pesan ramah, bukan galat SQL. */
    private function tooLong(string $pertanyaan, string $kataKunci): ?JsonResponse
    {
        if (mb_strlen($pertanyaan) > 500) {
            return $this->fail('Pertanyaan maksimal 500 karakter.', 400);
        }
        if (mb_strlen($kataKunci) > 255) {
            return $this->fail('Kata kunci maksimal 255 karakter.', 400);
        }

        return null;
    }
}
