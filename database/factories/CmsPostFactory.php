<?php

namespace Database\Factories;

use TallCms\Cms\Enums\ContentStatus;
use App\Models\CmsPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CmsPost>
 */
class CmsPostFactory extends Factory
{
    protected $model = CmsPost::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence();

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->paragraph(),
            'content' => [
                ['type' => 'paragraph', 'data' => ['content' => fake()->paragraphs(3, true)]],
            ],
            'meta_title' => fake()->optional()->sentence(),
            'meta_description' => fake()->optional()->sentence(),
            'featured_image' => null,
            'status' => ContentStatus::Draft->value,
            'published_at' => null,
            'author_id' => User::factory(),
            'is_featured' => false,
            'views' => 0,
        ];
    }

    /**
     * Indicate that the post is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentStatus::Published->value,
            'published_at' => now()->subHour(),
        ]);
    }

    /**
     * Indicate that the post is pending review.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentStatus::Pending->value,
            'submitted_at' => now(),
            'submitted_by' => $attributes['author_id'],
        ]);
    }

    /**
     * Indicate that the post is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentStatus::Published->value,
            'published_at' => now()->addDay(),
        ]);
    }

    /**
     * Indicate that the post is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
