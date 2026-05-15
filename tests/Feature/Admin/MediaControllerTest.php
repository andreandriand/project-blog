<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
 * Regression coverage untuk OPTIMIZATION-REPORT.md item #3 (SVG XSS).
 * SVG bisa berisi <script> aktif - whitelist mime hanya raster image.
 * Test gagal = whitelist di MediaController di-revert.
 */

class MediaControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_upload_jpeg_image(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.media.store'), [
                'files' => [UploadedFile::fake()->image('photo.jpg', 800, 600)],
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('media', 1);
    }

    public function test_svg_upload_is_rejected(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $svgContent = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';
        $svgFile = UploadedFile::fake()->createWithContent('evil.svg', $svgContent);

        $this->actingAs($admin)
            ->post(route('admin.media.store'), [
                'files' => [$svgFile],
            ])
            ->assertSessionHasErrors('files.0');

        $this->assertDatabaseCount('media', 0);
    }

    public function test_oversized_image_is_rejected(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $bigFile = UploadedFile::fake()->image('huge.jpg')->size(6000);

        $this->actingAs($admin)
            ->post(route('admin.media.store'), [
                'files' => [$bigFile],
            ])
            ->assertSessionHasErrors('files.0');
    }

    public function test_non_admin_cannot_access_admin_media_index(): void
    {
        $author = User::factory()->create(['role' => 'author']);

        $this->actingAs($author)
            ->get(route('admin.media.index'))
            ->assertForbidden();
    }
}
