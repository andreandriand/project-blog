<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        $filename = Str::uuid().'.jpg';

        return [
            'user_id' => User::factory(),
            'filename' => $filename,
            'original_name' => fake()->word().'.jpg',
            'path' => 'media/'.$filename,
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(50_000, 2_000_000),
            'width' => 800,
            'height' => 600,
            'alt_text' => null,
        ];
    }
}
