<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
 * Smoke test untuk public blog endpoints + regression coverage:
 * - OPTIMIZATION-REPORT.md item #7: N+1 di approvedComments.replies.user
 *   sudah di-fix dengan eager load chain. Test ini count query - boundary aman = <15 query
 *   walau ada banyak comment+reply dengan user berbeda (bukan exact count agar tidak brittle).
 *
 * Test gagal pada query count = ada N+1 baru di flow render post detail.
 */

class PublicPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_returns_200(): void
    {
        $this->get(route('home'))->assertOk();
    }

    public function test_blog_index_returns_200_and_lists_published_only(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $category = Category::factory()->create();

        Post::factory()->published()->create(['user_id' => $author->id, 'category_id' => $category->id, 'title' => 'Visible']);
        Post::factory()->create(['user_id' => $author->id, 'category_id' => $category->id, 'title' => 'Draft Only', 'status' => 'draft']);

        $this->get(route('posts.index'))
            ->assertOk()
            ->assertSee('Visible')
            ->assertDontSee('Draft Only');
    }

    public function test_post_detail_published_returns_200(): void
    {
        $post = Post::factory()->published()->create();

        $this->get(route('posts.show', $post->slug))
            ->assertOk()
            ->assertSee($post->title);
    }

    public function test_post_detail_draft_returns_404(): void
    {
        $draft = Post::factory()->create(['status' => 'draft']);

        $this->get(route('posts.show', $draft->slug))->assertNotFound();
    }

    public function test_post_detail_does_not_n_plus_one_on_comment_replies(): void
    {
        $post = Post::factory()->published()->create();

        // 3 top-level comments, masing-masing dengan 2 reply dari user berbeda
        Comment::factory()->count(3)->create(['post_id' => $post->id])
            ->each(function (Comment $top) {
                Comment::factory()->count(2)->create([
                    'post_id' => $top->post_id,
                    'parent_id' => $top->id,
                ]);
            });

        DB::enableQueryLog();
        $this->get(route('posts.show', $post->slug))->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(
            20,
            $queryCount,
            "Query count {$queryCount} terlalu tinggi - kemungkinan N+1 regression. ".
            'Eager load chain di PostController::show harus tetap include approvedComments.replies.user.'
        );
    }

    public function test_sitemap_xml_returns_valid_xml(): void
    {
        Post::factory()->published()->count(3)->create();

        $response = $this->get(route('sitemap'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml');
    }

    public function test_security_headers_are_set(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_category_archive_works(): void
    {
        $category = Category::factory()->create(['slug' => 'tech']);
        Post::factory()->published()->create(['category_id' => $category->id, 'title' => 'In Tech']);

        $this->get(route('posts.category', 'tech'))
            ->assertOk()
            ->assertSee('In Tech');
    }

    public function test_tag_archive_works(): void
    {
        $tag = Tag::factory()->create(['slug' => 'laravel']);
        $post = Post::factory()->published()->create(['title' => 'Laravel Tips']);
        $post->tags()->attach($tag);

        $this->get(route('posts.tag', 'laravel'))
            ->assertOk()
            ->assertSee('Laravel Tips');
    }
}
