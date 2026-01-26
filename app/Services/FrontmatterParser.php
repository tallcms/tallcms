<?php

namespace App\Services;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class FrontmatterParser
{
    /**
     * Parse YAML frontmatter from markdown content.
     *
     * @return array{frontmatter: array<string, mixed>, content: string, error: ?string}
     */
    public function parse(string $markdown): array
    {
        if (! str_starts_with($markdown, '---')) {
            return ['frontmatter' => [], 'content' => $markdown, 'error' => null];
        }

        $parts = preg_split('/^---$/m', $markdown, 3);
        if (count($parts) < 3) {
            return ['frontmatter' => [], 'content' => $markdown, 'error' => null];
        }

        $yaml = trim($parts[1]);
        $content = trim($parts[2]);

        try {
            $frontmatter = Yaml::parse($yaml) ?? [];
        } catch (ParseException $e) {
            return [
                'frontmatter' => [],
                'content' => $markdown,
                'error' => "Invalid YAML: {$e->getMessage()}",
            ];
        }

        return [
            'frontmatter' => $frontmatter,
            'content' => $content,
            'error' => null,
        ];
    }
}
