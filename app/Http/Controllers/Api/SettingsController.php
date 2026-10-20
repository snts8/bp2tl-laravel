<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pengaturan teks halaman (tabel site_settings).
 *   GET  /api/settings        publik  — api/settings_get.php
 *   POST /api/admin/settings  admin   — api/settings_save.php
 */
class SettingsController extends Controller
{
    use RespondsWithJson;

    /**
     * Daftar putih kunci yang boleh disimpan, agar tidak ada key sembarangan
     * yang masuk ke database. (Kunci lama `kontak_kicker` & `kontak_title` ada di
     * database tetapi memang tidak pernah bisa diubah dari UI — tidak dipakai halaman.)
     */
    public const ALLOWED_KEYS = [
        'hero_title', 'hero_lead',
        'hero_photo_1', 'hero_photo_2', 'hero_photo_3', 'hero_photo_4',
        'tanya_kicker', 'tanya_title', 'tanya_desc',
        'faq_kicker', 'faq_title', 'faq_desc',
        'faq_q1', 'faq_a1', 'faq_q2', 'faq_a2', 'faq_q3', 'faq_a3', 'faq_q4', 'faq_a4', 'faq_q5', 'faq_a5',
        'peta_kicker', 'peta_title', 'alamat_text',
        'footer_desc',
        'footer_link1_text', 'footer_link1_href',
        'footer_link2_text', 'footer_link2_href',
        'footer_link3_text', 'footer_link3_href',
        'footer_link4_text', 'footer_link4_href',
        'footer_link5_text', 'footer_link5_href',
        'footer_kontak_alamat', 'footer_kontak_telepon', 'footer_kontak_email',
        'footer_copyright', 'footer_tagline',
    ];

    public function index(): JsonResponse
    {
        $data = SiteSetting::query()->toBase()->pluck('setting_value', 'setting_key')->all();

        return $this->ok(['data' => $data]);
    }

    public function save(Request $request): JsonResponse
    {
        $input = $request->all();

        $saved = 0;
        foreach (self::ALLOWED_KEYS as $key) {
            if (! array_key_exists($key, $input)) {
                continue;
            }

            $value = trim((string) $input[$key]);
            if ($value === '') {
                continue; // jangan simpan teks kosong, biar tidak menghapus judul section
            }

            SiteSetting::updateOrCreate(['setting_key' => $key], ['setting_value' => $value]);
            $saved++;
        }

        if ($saved > 0) {
            ActivityLogger::log('ubah_pengaturan', "Memperbarui {$saved} teks/pengaturan halaman.", $request->user('admin'));
        }

        return $this->ok(['saved' => $saved]);
    }
}
