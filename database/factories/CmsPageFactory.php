<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\CmsPage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CmsPage>
 */
class CmsPageFactory extends Factory
{
    protected $model = CmsPage::class;

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
            'content' => [
                ['type' => 'paragraph', 'data' => ['content' => fake()->paragraphs(3, true)]],
            ],
            'meta_title' => fake()->optional()->sentence(),
            'meta_description' => fake()->optional()->sentence(),
            'featured_image' => null,
            'status' => ContentStatus::Draft->value,
            'is_homepage' => false,
            'published_at' => null,
            'parent_id' => null,
            'sort_order' => 0,
            'template' => null,
            'author_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the page is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentStatus::Published->value,
            'published_at' => now()->subHour(),
        ]);
    }

    /**
     * Indicate that the page is pending review.
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
     * Indicate that the page is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentStatus::Published->value,
            'published_at' => now()->addDay(),
        ]);
    }

    /**
     * Indicate that the page is the homepage.
     */
    public function homepage(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_homepage' => true,
        ]);
    }
}
