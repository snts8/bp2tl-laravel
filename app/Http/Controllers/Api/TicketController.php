<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Pertanyaan;
use App\Services\ActivityLogger;
use App\Services\Similarity\SimilarityService;
use App\Services\TicketCodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Endpoint PUBLIK untuk tiket: ajukan (api/ajukan.php) & lacak (api/lacak.php).
 */
class TicketController extends Controller
{
    use RespondsWithJson;

    /**
     * POST /api/ajukan
     *
     * Cek dulu apakah sudah ada pertanyaan serupa yang terjawab. Kalau ada, jawabannya
     * dikembalikan TANPA membuat tiket. Tiket hanya dibuat untuk pertanyaan yang belum
     * ada padanannya.
     *
     * Pengecualian: kategori "Pengaduan Layanan" selalu jadi tiket, dan pengguna yang
     * merasa jawabannya belum sesuai bisa memaksa membuat tiket lewat `paksa: true`.
     */
    public function store(Request $request, SimilarityService $similarity): JsonResponse
    {
        $data = $request->validate([
            'nama'       => ['required', 'string', 'max:150'],
            'kontak'     => ['required', 'string', 'max:150'],
            'kategori'   => ['required', 'string', 'max:100'],
            'pertanyaan' => ['required', 'string', 'max:5000'],
        ], [
            'required' => 'Semua kolom wajib diisi.',
            'string'   => 'Data yang dikirim tidak valid.',
            'nama.max'       => 'Nama maksimal 150 karakter.',
            'kontak.max'     => 'Kontak maksimal 150 karakter.',
            'kategori.max'   => 'Kategori maksimal 100 karakter.',
            'pertanyaan.max' => 'Pertanyaan maksimal 5000 karakter.',
        ]);

        $nama       = $data['nama'];
        $kontak     = $data['kontak'];
        $kategori   = $data['kategori'];
        $pertanyaan = $data['pertanyaan'];

        $paksa = $request->boolean('paksa');

        if ($kategori !== Pertanyaan::KATEGORI_PENGADUAN && ! $paksa) {
            try {
                $cocok = $similarity->cariSerupa($pertanyaan);
            } catch (Throwable $e) {
                report($e);
                $cocok = ['status' => 'none']; // kegagalan pencocokan tidak boleh menghalangi pembuatan tiket
            }

            if ($cocok['status'] === 'match') {
                ActivityLogger::log(
                    'jawab_otomatis',
                    "Pertanyaan dijawab otomatis tanpa tiket (mirip: \"{$cocok['pertanyaan']}\").",
                    null,
                    "Publik ({$nama})"
                );

                return $this->ok([
                    'terjawab'           => true,
                    'pertanyaan_terkait' => $cocok['pertanyaan'],
                    'jawaban'            => $cocok['jawaban'],
                ]);
            }

            // Topiknya ada di database tapi belum cukup pasti untuk dipilihkan satu jawaban.
            // Jangan langsung membuat tiket: tampilkan pertanyaan mirip beserta jawabannya
            // dan biarkan pengguna memilih atau memaksa membuat tiket.
            if ($cocok['status'] === 'saran' && ! empty($cocok['saran_detail'])) {
                return $this->ok([
                    'mirip' => true,
                    'saran' => $cocok['saran_detail'],
                ]);
            }
        }

        $code = TicketCodeGenerator::generate();

        Pertanyaan::create([
            'code'       => $code,
            'nama'       => $nama,
            'kontak'     => $kontak,
            'kategori'   => $kategori,
            'pertanyaan' => $pertanyaan,
            'status'     => 'menunggu',
            'tanggal'    => now(),
        ]);

        ActivityLogger::log(
            'pertanyaan_baru',
            "Mengajukan \"{$kategori}\" baru (tiket {$code}).",
            null,
            "Publik ({$nama})"
        );

        return $this->ok(['code' => $code]);
    }

    /** GET /api/lacak?kode=BP2TL-2026-XXXXXX */
    public function show(Request $request): JsonResponse
    {
        $code = trim((string) $request->query('kode', ''));

        if ($code === '') {
            return $this->fail('Kode tiket wajib diisi.', 400);
        }

        $item = Pertanyaan::query()
            ->where('code', $code)
            ->first(['code', 'nama', 'kategori', 'pertanyaan', 'status', 'jawaban', 'tanggal', 'tanggal_jawab']);

        if (! $item) {
            return $this->fail('Kode tiket tidak ditemukan. Periksa kembali penulisannya.', 404);
        }

        return $this->ok(['data' => $item]);
    }
}
