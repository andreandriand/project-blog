<?php

namespace Tests\Feature\Author;

use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * Regression coverage untuk Author/PostController:
 * - PostPolicy ownership: author hanya boleh edit/delete post miliknya sendiri.
 * - OPTIMIZATION-REPORT.md item #2: Purifier sanitize body sebelum simpan.
 * - OPTIMIZATION-REPORT.md item #4: featured_image_path WHERE user_id=auth().
 *   Author tidak boleh refer media milik user lain (lebih ketat dari admin).
 * - Workflow submit_review: status berubah ke 'pending'.
 */

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    private function author(): User
    {
        return User::factory()->create(['role' => 'author']);
    }

    public function test_author_can_create_own_post(): void
    {
        $author = $this->author();
        $category = Category::factory()->create();

        $this->actingAs($author)
            ->post(route('author.posts.store'), [
                'title' => 'My First Post',
                'category_id' => $category->id,
                'body' => '<p>Hello world</p>',
            ])
            ->assertRedirect(route('author.posts.index'));

        $this->assertDatabaseHas('posts', [
            'title' => 'My First Post',
            'user_id' => $author->id,
            'status' => 'draft',
        ]);
    }

    public function test_author_cannot_edit_other_authors_post(): void
    {
        $author = $this->author();
        $other = $this->author();
        $post = Post::factory()->create(['user_id' => $other->id]);

        $this->actingAs($author)
            ->get(route('author.posts.edit', $post))
            ->assertForbidden();
    }

    public function test_author_cannot_delete_other_authors_post(): void
    {
        $author = $this->author();
        $other = $this->author();
        $post = Post::factory()->create(['user_id' => $other->id]);

        $this->actingAs($author)
            ->delete(route('author.posts.destroy', $post))
            ->assertForbidden();

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_post_body_is_sanitized_on_author_create(): void
    {
        $author = $this->author();
        $category = Category::factory()->create();

        $this->actingAs($author)
            ->post(route('author.posts.store'), [
                'title' => 'XSS Attempt',
                'category_id' => $category->id,
                'body' => '<p>safe</p><script>alert(1)</script>',
            ])
            ->assertRedirect();

        $post = Post::where('title', 'XSS Attempt')->firstOrFail();
        $this->assertStringNotContainsString('<script', $post->body);
        $this->assertStringContainsString('<p>safe</p>', $post->body);
    }

    public function test_author_can_only_use_own_media_as_featured_image(): void
    {
        $author = $this->author();
        $other = $this->author();
        $category = Category::factory()->create();
        $foreignMedia = Media::factory()->create(['user_id' => $other->id]);

        $this->actingAs($author)
            ->post(route('author.posts.store'), [
                'title' => 'Steal Media',
                'category_id' => $category->id,
                'body' => '<p>body</p>',
                'featured_image_path' => $foreignMedia->path,
            ])
            ->assertSessionHasErrors('featured_image_path');

        $this->assertDatabaseMissing('posts', ['title' => 'Steal Media']);
    }

    public function test_author_can_use_own_media_as_featured_image(): void
    {
        $author = $this->author();
        $category = Category::factory()->create();
        $ownMedia = Media::factory()->create(['user_id' => $author->id]);

        $this->actingAs($author)
            ->post(route('author.posts.store'), [
                'title' => 'Use Own Media',
                'category_id' => $category->id,
                'body' => '<p>body</p>',
                'featured_image_path' => $ownMedia->path,
            ])
            ->assertRedirect(route('author.posts.index'));

        $this->assertDatabaseHas('posts', [
            'title' => 'Use Own Media',
            'featured_image' => $ownMedia->path,
        ]);
    }

    public function test_submit_review_changes_status_to_pending(): void
    {
        $author = $this->author();
        $category = Category::factory()->create();

        $this->actingAs($author)
            ->post(route('author.posts.store'), [
                'title' => 'Submit For Review',
                'category_id' => $category->id,
                'body' => '<p>body</p>',
                'submit_review' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('posts', [
            'title' => 'Submit For Review',
            'status' => 'pending',
        ]);
    }
}
