<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(6),
            'excerpt' => fake()->sentence(15),
            'body' => '<p>'.fake()->paragraphs(3, true).'</p>',
            'featured_image' => null,
            'status' => 'draft',
            'is_featured' => false,
            'published_at' => null,
            'views_count' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => 'rejected',
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }
}
