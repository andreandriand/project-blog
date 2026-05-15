<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * Regression coverage untuk:
 * - OPTIMIZATION-REPORT.md item #2 (XSS body sanitization via Purifier preset 'blog')
 * - OPTIMIZATION-REPORT.md item #4 (featured_image_path divalidasi via exists:media,path)
 * - Workflow approve/reject post (admin-only)
 *
 * Test gagal = patch security XSS atau path traversal di-revert.
 */

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_post_body_is_sanitized_when_admin_creates_post(): void
    {
        $admin = $this->admin();
        $category = Category::factory()->create();

        $maliciousBody = '<h2>Halo</h2><p>Aman</p>'
            .'<script>alert("xss")</script>'
            .'<a href="javascript:alert(1)">klik</a>'
            .'<img src=x onerror=alert(1)>';

        $this->actingAs($admin)
            ->post(route('admin.posts.store'), [
                'title' => 'Test XSS Sanitization',
                'category_id' => $category->id,
                'body' => $maliciousBody,
                'status' => 'draft',
            ])
            ->assertRedirect(route('admin.posts.index'));

        $post = Post::where('title', 'Test XSS Sanitization')->firstOrFail();

        $this->assertStringNotContainsString('<script', $post->body);
        $this->assertStringNotContainsString('onerror', $post->body);
        $this->assertStringNotContainsString('javascript:', $post->body);
        $this->assertStringContainsString('<h2>Halo</h2>', $post->body);
        $this->assertStringContainsString('<p>Aman</p>', $post->body);
    }

    public function test_featured_image_path_must_reference_existing_media_record(): void
    {
        $admin = $this->admin();
        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.posts.store'), [
                'title' => 'Test Path Traversal',
                'category_id' => $category->id,
                'body' => '<p>body</p>',
                'status' => 'draft',
                'featured_image_path' => '../../../etc/passwd',
            ])
            ->assertSessionHasErrors('featured_image_path');

        $this->assertDatabaseMissing('posts', ['title' => 'Test Path Traversal']);
    }

    public function test_featured_image_path_accepts_valid_media_record(): void
    {
        $admin = $this->admin();
        $category = Category::factory()->create();
        $media = Media::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.posts.store'), [
                'title' => 'Valid Media Reference',
                'category_id' => $category->id,
                'body' => '<p>body</p>',
                'status' => 'draft',
                'featured_image_path' => $media->path,
            ])
            ->assertRedirect(route('admin.posts.index'));

        $this->assertDatabaseHas('posts', [
            'title' => 'Valid Media Reference',
            'featured_image' => $media->path,
        ]);
    }

    public function test_admin_can_approve_post_and_publish_it(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->pending()->create();

        $this->actingAs($admin)
            ->patch(route('admin.posts.approve', $post))
            ->assertRedirect();

        $fresh = $post->fresh();
        $this->assertSame('published', $fresh->status);
        $this->assertNotNull($fresh->published_at);
        $this->assertNull($fresh->rejection_reason);
    }

    public function test_admin_can_reject_post_with_reason(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->pending()->create();

        $this->actingAs($admin)
            ->patch(route('admin.posts.reject', $post), [
                'rejection_reason' => 'Konten tidak relevan dengan kategori.',
            ])
            ->assertRedirect();

        $fresh = $post->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertSame('Konten tidak relevan dengan kategori.', $fresh->rejection_reason);
    }

    public function test_reject_requires_reason(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->pending()->create();

        $this->actingAs($admin)
            ->patch(route('admin.posts.reject', $post))
            ->assertSessionHasErrors('rejection_reason');
    }

    public function test_non_admin_cannot_approve_post(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $post = Post::factory()->pending()->create();

        $this->actingAs($author)
            ->patch(route('admin.posts.approve', $post))
            ->assertForbidden();
    }
}
