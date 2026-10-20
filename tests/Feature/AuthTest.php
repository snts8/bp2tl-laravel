<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function akun(string $username, string $role, string $password = 'rahasia123'): AdminUser
    {
        return AdminUser::create([
            'username'      => $username,
            'password_hash' => Hash::make($password),
            'role'          => $role,
        ]);
    }

    public function test_admin_bisa_login_lewat_realm_admin(): void
    {
        $this->akun('budi', 'admin');

        $this->postJson('/api/login?realm=admin', ['username' => 'budi', 'password' => 'rahasia123'])
            ->assertOk()
            ->assertJson(['success' => true, 'role' => 'admin']);

        $this->assertDatabaseHas('activity_log', ['action' => 'login', 'admin_username' => 'budi']);
    }

    public function test_cek_sesi_membaca_guard_sesuai_realm(): void
    {
        $this->getJson('/api/session?realm=admin')->assertJson(['loggedIn' => false, 'role' => null]);

        $admin = $this->akun('budi', 'admin');
        $this->actingAs($admin, 'admin')
            ->getJson('/api/session?realm=admin')
            ->assertJson(['loggedIn' => true, 'role' => 'admin', 'username' => 'budi']);
    }

    public function test_superadmin_ditolak_di_halaman_login_admin(): void
    {
        $this->akun('bos', 'superadmin');

        $this->postJson('/api/login?realm=admin', ['username' => 'bos', 'password' => 'rahasia123'])
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_kata_sandi_salah_ditolak(): void
    {
        $this->akun('budi', 'admin');

        $this->postJson('/api/login?realm=admin', ['username' => 'budi', 'password' => 'salah'])
            ->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'Nama pengguna atau kata sandi salah.']);
    }

    public function test_hash_awalan_2b_dari_node_tetap_bisa_login_setelah_dinormalkan(): void
    {
        // Hash nyata akun "admin" dari dump lama (password: bp2tl2026), awalan $2b$ -> $2y$
        $hash = '$2b$10$Bq6E5hpnvYj6r98w73MjwuXeVRQFCZvl6YGl1SoXK.t347Kek3moi';
        AdminUser::create([
            'username'      => 'admin',
            'password_hash' => \App\Console\Commands\ImporDataLama::normalkanHash($hash),
            'role'          => 'admin',
        ]);

        $this->postJson('/api/login?realm=admin', ['username' => 'admin', 'password' => 'bp2tl2026'])
            ->assertOk();
    }

    public function test_endpoint_admin_menolak_tamu_dan_superadmin_menolak_admin(): void
    {
        $this->getJson('/api/admin/tiket')->assertStatus(401);
        $this->getJson('/api/superadmin/log')->assertStatus(403);

        $admin = $this->akun('budi', 'admin');
        $this->actingAs($admin, 'admin')->getJson('/api/superadmin/log')->assertStatus(403);
    }

    public function test_logout_satu_realm_tidak_mengeluarkan_realm_lain(): void
    {
        $admin = $this->akun('budi', 'admin');
        $bos   = $this->akun('bos', 'superadmin');

        // Dua guard aktif bersamaan (setara login di dua tab pada satu browser)
        $this->actingAs($admin, 'admin')->actingAs($bos, 'superadmin');

        $this->postJson('/api/logout?realm=admin')->assertOk();

        $this->getJson('/api/session?realm=admin')->assertJson(['loggedIn' => false]);
        $this->getJson('/api/session?realm=superadmin')->assertJson(['loggedIn' => true, 'username' => 'bos']);
        $this->assertDatabaseHas('activity_log', ['action' => 'logout', 'admin_username' => 'budi']);
    }
}
