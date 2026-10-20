<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\ChatbotFaq;
use App\Models\Pertanyaan;
use App\Models\PertanyaanHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TicketFlowTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'admin'): AdminUser
    {
        return AdminUser::create(['username' => $role . '1', 'password_hash' => Hash::make('x123456'), 'role' => $role]);
    }

    private function ajukan(array $override = [])
    {
        return $this->postJson('/api/ajukan', $override + [
            'nama'       => 'Budi',
            'kontak'     => '0812',
            'kategori'   => 'Informasi Umum',
            'pertanyaan' => 'apakah bp2tl punya asrama untuk peserta?',
        ]);
    }

    public function test_ajukan_membuat_tiket_berformat_benar_dan_bisa_dilacak(): void
    {
        $res = $this->ajukan()->assertOk()->assertJson(['success' => true]);
        $code = $res->json('code');

        $this->assertMatchesRegularExpression('/^BP2TL-\d{4}-[A-HJ-NP-Z2-9]{6}$/', $code);
        $this->getJson('/api/lacak?kode=' . $code)
            ->assertOk()
            ->assertJsonPath('data.status', 'menunggu')
            ->assertJsonMissingPath('data.kontak'); // kontak tidak boleh bocor ke publik
    }

    public function test_kolom_kosong_ditolak_400_dengan_pesan_indonesia(): void
    {
        $this->ajukan(['nama' => ''])
            ->assertStatus(400)
            ->assertJson(['success' => false, 'message' => 'Semua kolom wajib diisi.']);
    }

    public function test_lacak_kode_tak_dikenal_404(): void
    {
        $this->getJson('/api/lacak?kode=BP2TL-2026-ZZZZZZ')->assertStatus(404);
    }

    public function test_pertanyaan_yang_sudah_ada_jawabannya_dijawab_tanpa_tiket(): void
    {
        ChatbotFaq::create(['pertanyaan' => 'Jam buka loket pelayanan', 'jawaban' => 'Pukul 08.00-16.00.']);

        $this->ajukan(['pertanyaan' => 'Jam buka loket pelayanan'])
            ->assertOk()
            ->assertJson(['terjawab' => true, 'jawaban' => 'Pukul 08.00-16.00.']);

        $this->assertSame(0, Pertanyaan::count());
    }

    public function test_pengaduan_dan_paksa_selalu_membuat_tiket(): void
    {
        ChatbotFaq::create(['pertanyaan' => 'Jam buka loket pelayanan', 'jawaban' => 'Pukul 08.00-16.00.']);

        $this->ajukan(['pertanyaan' => 'Jam buka loket pelayanan', 'kategori' => 'Pengaduan Layanan'])
            ->assertOk()->assertJsonStructure(['code']);
        $this->ajukan(['pertanyaan' => 'Jam buka loket pelayanan', 'paksa' => true])
            ->assertOk()->assertJsonStructure(['code']);

        $this->assertSame(2, Pertanyaan::count());
    }

    public function test_menjawab_menyalin_ke_history_kecuali_pengaduan(): void
    {
        $admin = $this->admin();
        $kode1 = $this->ajukan()->json('code');
        $kode2 = $this->ajukan(['kategori' => 'Pengaduan Layanan', 'pertanyaan' => 'pelayanan lambat sekali'])->json('code');

        $this->actingAs($admin, 'admin')->postJson('/api/admin/jawab', ['code' => $kode1, 'jawaban' => 'Ada asrama.'])->assertOk();
        $this->actingAs($admin, 'admin')->postJson('/api/admin/jawab', ['code' => $kode2, 'jawaban' => 'Mohon maaf.'])->assertOk();

        $this->assertDatabaseHas('pertanyaan_history', ['code' => $kode1, 'jawaban' => 'Ada asrama.']);
        $this->assertDatabaseMissing('pertanyaan_history', ['code' => $kode2]);
        $this->assertDatabaseHas('pertanyaan', ['code' => $kode2, 'status' => 'dijawab']);
    }

    public function test_hapus_satu_tiket_ikut_menghapus_history_tetapi_hapus_massal_tidak(): void
    {
        $admin = $this->admin();
        $bos   = $this->admin('superadmin');
        $a = $this->ajukan(['pertanyaan' => 'apakah ada asrama untuk peserta'])->json('code');
        $b = $this->ajukan(['pertanyaan' => 'apakah tersedia kantin di lokasi'])->json('code');

        foreach ([$a, $b] as $k) {
            $this->actingAs($admin, 'admin')->postJson('/api/admin/jawab', ['code' => $k, 'jawaban' => 'Ya.'])->assertOk();
        }
        $this->assertSame(2, PertanyaanHistory::count());

        // hapus satuan -> history ikut terhapus
        $this->actingAs($admin, 'admin')->postJson('/api/admin/hapus', ['code' => $a])->assertOk();
        $this->assertDatabaseMissing('pertanyaan_history', ['code' => $a]);

        // hapus massal Super Admin -> history TETAP
        $this->actingAs($bos, 'superadmin')->postJson('/api/superadmin/reset', ['action' => 'hapus_pertanyaan'])
            ->assertOk()->assertJson(['count' => 1]);
        $this->assertSame(0, Pertanyaan::count());
        $this->assertDatabaseHas('pertanyaan_history', ['code' => $b]);
    }

    public function test_chatbot_menjawab_dari_history_walau_tiket_sudah_dihapus_massal(): void
    {
        $admin = $this->admin();
        $bos   = $this->admin('superadmin');
        $k = $this->ajukan(['pertanyaan' => 'apakah tersedia kantin di lokasi'])->json('code');
        $this->actingAs($admin, 'admin')->postJson('/api/admin/jawab', ['code' => $k, 'jawaban' => 'Kantin tersedia.']);
        $this->actingAs($bos, 'superadmin')->postJson('/api/superadmin/reset', ['action' => 'hapus_pertanyaan']);

        $this->postJson('/api/chatbot', ['pesan' => 'apakah tersedia kantin di lokasi'])
            ->assertOk()
            ->assertJson(['ditemukan' => true, 'jawaban' => 'Kantin tersedia.', 'code' => $k]);
    }

    public function test_dashboard_admin_memuat_daftar_dan_statistik(): void
    {
        $admin = $this->admin();
        $this->ajukan();

        $this->actingAs($admin, 'admin')->getJson('/api/admin/tiket?status=menunggu')
            ->assertOk()
            ->assertJsonPath('stats.total', 1)
            ->assertJsonPath('stats.menunggu', 1)
            ->assertJsonPath('stats.hariIni', 1)
            ->assertJsonCount(1, 'data');
    }

    public function test_tanggal_dikirim_dalam_format_mysql_bukan_iso(): void
    {
        $admin = $this->admin();
        $this->ajukan();

        $tanggal = $this->actingAs($admin, 'admin')->getJson('/api/admin/tiket')->json('data.0.tanggal');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $tanggal);
    }
}
