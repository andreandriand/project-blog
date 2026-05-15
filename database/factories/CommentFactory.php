<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'user_id' => User::factory(),
            'author_name' => null,
            'author_email' => null,
            'body' => fake()->paragraph(),
            'is_approved' => true,
            'parent_id' => null,
        ];
    }

    public function guest(): static
    {
        return $this->state(fn () => [
            'user_id' => null,
            'author_name' => fake()->name(),
            'author_email' => fake()->safeEmail(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['is_approved' => false]);
    }
}
