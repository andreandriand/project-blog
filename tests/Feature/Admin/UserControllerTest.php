<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/*
 * Regression coverage untuk OPTIMIZATION-REPORT.md item #1 (password double-hashing).
 * Bug awal: Admin/UserController memanggil Hash::make() padahal model User pakai cast 'hashed'
 *           => password di-hash 2x => user tidak bisa login.
 * Fix: Hash::make() dihapus dari controller; cast model yang handle.
 *
 * Test ini gagal kalau ada yang menambahkan kembali Hash::make() di controller.
 */

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_create_user_with_password_that_actually_works_for_login(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), [
                'name' => 'Budi New',
                'email' => 'budi.new@example.com',
                'password' => 'Secret1234!',
                'password_confirmation' => 'Secret1234!',
                'role' => 'author',
            ])
            ->assertRedirect(route('admin.users.index'));

        $created = User::where('email', 'budi.new@example.com')->firstOrFail();

        $this->assertTrue(
            Hash::check('Secret1234!', $created->password),
            'Password yang disimpan harus bisa di-verify dengan plaintext aslinya. '.
            'Gagal = kemungkinan double-hash regression (item #1).'
        );
    }

    public function test_admin_can_update_user_password_and_login_works(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create(['role' => 'author']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'password' => 'NewPass5678!',
                'password_confirmation' => 'NewPass5678!',
                'role' => 'author',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue(Hash::check('NewPass5678!', $target->fresh()->password));
    }

    public function test_admin_can_update_user_without_changing_password(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create(['role' => 'author']);
        $originalHash = $target->password;

        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => 'Updated Name',
                'email' => $target->email,
                'role' => 'admin',
            ])
            ->assertRedirect(route('admin.users.index'));

        $fresh = $target->fresh();
        $this->assertSame('Updated Name', $fresh->name);
        $this->assertSame('admin', $fresh->role);
        $this->assertSame($originalHash, $fresh->password, 'Password tidak boleh berubah saat tidak diisi.');
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $author = User::factory()->create(['role' => 'author']);

        $this->actingAs($author)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }
}
