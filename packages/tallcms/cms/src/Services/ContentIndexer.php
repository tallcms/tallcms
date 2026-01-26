<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use Illuminate\Support\Facades\Log;

class ContentIndexer
{
    /**
     * Extract all searchable text from Tiptap content.
     * Always returns a string, never arrays/objects.
     */
    public function extractSearchableText(mixed $content): string
    {
        if (empty($content)) {
            return '';
        }

        if (is_string($content)) {
            $decoded = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->cleanText($content);
            }
            $content = $decoded;
        }

        if (! is_array($content)) {
            return '';
        }

        $text = [];
        $this->walkContent($content, $text);

        return $this->cleanText(implode(' ', $text));
    }

    /**
     * Build complete search content for a model.
     * Combines title, excerpt, meta fields, and extracted block content.
     */
    public function buildSearchContent(
        ?string $title,
        ?string $excerpt,
        ?string $metaTitle,
        ?string $metaDescription,
        mixed $content
    ): string {
        $parts = array_filter([
            $title,
            $excerpt,
            $metaTitle,
            $metaDescription,
            $this->extractSearchableText($content),
        ], fn ($v) => is_string($v) && trim($v) !== '');

        return $this->cleanText(implode(' ', $parts));
    }

    protected function walkContent(array $node, array &$text): void
    {
        // Handle Tiptap customBlock nodes
        if (($node['type'] ?? '') === 'customBlock') {
            $blockId = $node['attrs']['id'] ?? '';
            $attrs = $node['attrs'] ?? [];
            $extracted = $this->extractFromBlock($blockId, $attrs);
            if ($extracted !== '') {
                $text[] = $extracted;
            }
        }

        // Handle text nodes
        if (isset($node['text']) && is_string($node['text'])) {
            $text[] = $node['text'];
        }

        // Recurse into content array
        if (isset($node['content']) && is_array($node['content'])) {
            foreach ($node['content'] as $child) {
                if (is_array($child)) {
                    $this->walkContent($child, $text);
                }
            }
        }
    }

    protected function extractFromBlock(string $blockId, array $attrs): string
    {
        return match ($blockId) {
            'content_block' => $this->join([
                $attrs['title'] ?? '',
                $attrs['subtitle'] ?? '',
                $attrs['body'] ?? '',
            ]),
            'hero' => $this->join([
                $attrs['heading'] ?? '',
                $attrs['subheading'] ?? '',
                $attrs['cta_text'] ?? '',
                $attrs['cta_secondary_text'] ?? '',
                $attrs['microcopy'] ?? '',
            ]),
            'call_to_action' => $this->join([
                $attrs['title'] ?? '',
                $attrs['description'] ?? '',
                $attrs['button_text'] ?? '',
                $attrs['secondary_button_text'] ?? '',
            ]),
            'faq' => $this->extractFaq($attrs),
            'pricing' => $this->extractPricing($attrs),
            'features' => $this->extractFeatures($attrs),
            'team' => $this->extractTeam($attrs),
            'testimonials' => $this->extractTestimonials($attrs),
            'timeline' => $this->extractTimeline($attrs),
            'stats' => $this->extractStats($attrs),
            'parallax', 'logos', 'posts', 'contact_form' => $this->join([
                $attrs['title'] ?? '',
                $attrs['subtitle'] ?? '',
            ]),
            'image_gallery', 'divider' => '',
            default => $this->handleUnknownBlock($blockId, $attrs),
        };
    }

    protected function handleUnknownBlock(string $blockId, array $attrs): string
    {
        Log::debug("ContentIndexer: Unknown block type '{$blockId}'", [
            'attrs_keys' => array_keys($attrs),
        ]);

        $commonFields = ['title', 'heading', 'subtitle', 'description', 'body', 'content', 'text'];
        $text = [];
        foreach ($commonFields as $field) {
            if (isset($attrs[$field]) && is_string($attrs[$field])) {
                $text[] = $attrs[$field];
            }
        }

        return $this->join($text);
    }

    protected function extractFaq(array $attrs): string
    {
        $text = [$attrs['heading'] ?? '', $attrs['subheading'] ?? ''];
        foreach ($attrs['items'] ?? [] as $item) {
            if (is_array($item)) {
                $text[] = $item['question'] ?? '';
                $text[] = $item['answer'] ?? '';
            }
        }

        return $this->join($text);
    }

    protected function extractPricing(array $attrs): string
    {
        $text = [$attrs['title'] ?? '', $attrs['subtitle'] ?? ''];
        foreach ($attrs['plans'] ?? [] as $plan) {
            if (is_array($plan)) {
                $text[] = $plan['name'] ?? '';
                $text[] = $plan['description'] ?? '';
                foreach ($plan['features'] ?? [] as $feature) {
                    $text[] = is_string($feature) ? $feature : ($feature['text'] ?? '');
                }
            }
        }

        return $this->join($text);
    }

    protected function extractFeatures(array $attrs): string
    {
        $text = [$attrs['title'] ?? '', $attrs['subtitle'] ?? ''];
        foreach ($attrs['features'] ?? [] as $feature) {
            if (is_array($feature)) {
                $text[] = $feature['title'] ?? '';
                $text[] = $feature['description'] ?? '';
            }
        }

        return $this->join($text);
    }

    protected function extractTeam(array $attrs): string
    {
        $text = [$attrs['title'] ?? '', $attrs['subtitle'] ?? ''];
        foreach ($attrs['members'] ?? [] as $member) {
            if (is_array($member)) {
                $text[] = $member['name'] ?? '';
                $text[] = $member['role'] ?? '';
                $text[] = $member['bio'] ?? '';
            }
        }

        return $this->join($text);
    }

    protected function extractTestimonials(array $attrs): string
    {
        $text = [$attrs['title'] ?? '', $attrs['subtitle'] ?? ''];
        foreach ($attrs['testimonials'] ?? [] as $testimonial) {
            if (is_array($testimonial)) {
                $text[] = $testimonial['quote'] ?? '';
                $text[] = $testimonial['author'] ?? '';
                $text[] = $testimonial['role'] ?? '';
                $text[] = $testimonial['company'] ?? '';
            }
        }

        return $this->join($text);
    }

    protected function extractTimeline(array $attrs): string
    {
        $text = [$attrs['title'] ?? '', $attrs['subtitle'] ?? ''];
        foreach ($attrs['events'] ?? $attrs['items'] ?? [] as $event) {
            if (is_array($event)) {
                $text[] = $event['title'] ?? '';
                $text[] = $event['description'] ?? '';
                $text[] = $event['date'] ?? '';
            }
        }

        return $this->join($text);
    }

    protected function extractStats(array $attrs): string
    {
        $text = [$attrs['title'] ?? '', $attrs['subtitle'] ?? ''];
        foreach ($attrs['stats'] ?? [] as $stat) {
            if (is_array($stat)) {
                $text[] = $stat['label'] ?? '';
                $text[] = $stat['description'] ?? '';
            }
        }

        return $this->join($text);
    }

    protected function join(array $items): string
    {
        return implode(' ', array_filter($items, fn ($v) => is_string($v) && $v !== ''));
    }

    protected function cleanText(string $text): string
    {
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}
