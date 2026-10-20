<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperadminTest extends TestCase
{
    use RefreshDatabase;

    private function bos(): AdminUser
    {
        return AdminUser::create(['username' => 'bos', 'password_hash' => Hash::make('x123456'), 'role' => 'superadmin']);
    }

    public function test_tidak_bisa_menghapus_akun_sendiri_atau_superadmin_terakhir(): void
    {
        $bos = $this->bos();

        $this->actingAs($bos, 'superadmin')
            ->postJson('/api/superadmin/accounts', ['action' => 'delete', 'id' => $bos->id])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Anda tidak bisa menghapus akun Anda sendiri saat sedang login.');
    }

    public function test_tidak_bisa_menurunkan_peran_sendiri(): void
    {
        $bos = $this->bos();

        $this->actingAs($bos, 'superadmin')
            ->postJson('/api/superadmin/accounts', ['action' => 'update', 'id' => $bos->id, 'username' => 'bos', 'role' => 'admin'])
            ->assertStatus(400);
    }

    public function test_buat_akun_validasi_dan_duplikat(): void
    {
        $bos = $this->bos();

        $this->actingAs($bos, 'superadmin')->postJson('/api/superadmin/accounts', ['action' => 'create', 'username' => 'ani', 'password' => '123', 'role' => 'admin'])
            ->assertStatus(400)->assertJsonPath('message', 'Kata sandi minimal 6 karakter.');

        $this->actingAs($bos, 'superadmin')->postJson('/api/superadmin/accounts', ['action' => 'create', 'username' => 'ani', 'password' => 'rahasia1', 'role' => 'admin'])
            ->assertOk();
        $this->actingAs($bos, 'superadmin')->postJson('/api/superadmin/accounts', ['action' => 'create', 'username' => 'ani', 'password' => 'rahasia1', 'role' => 'admin'])
            ->assertStatus(409);

        $hash = AdminUser::where('username', 'ani')->value('password_hash');
        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertTrue(Hash::check('rahasia1', $hash));
    }

    public function test_hapus_log_meninggalkan_satu_jejak(): void
    {
        $bos = $this->bos();
        $this->actingAs($bos, 'superadmin')->postJson('/api/superadmin/reset', ['action' => 'hapus_log'])->assertOk();

        $this->assertDatabaseCount('activity_log', 1);
        $this->assertDatabaseHas('activity_log', ['action' => 'hapus_log']);
    }

    public function test_log_dan_history_dapat_dibuka(): void
    {
        $bos = $this->bos();
        $this->actingAs($bos, 'superadmin')->getJson('/api/superadmin/log')->assertOk()->assertJsonStructure(['data', 'stats' => ['total', 'hariIni', 'loginHariIni']]);
        $this->actingAs($bos, 'superadmin')->getJson('/api/superadmin/history')->assertOk()->assertJsonStructure(['data', 'stats' => ['total', 'dijawab']]);
    }
}
