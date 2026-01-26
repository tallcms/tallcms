<?php

namespace Tests\Unit;

use App\Services\FrontmatterParser;
use PHPUnit\Framework\TestCase;

class FrontmatterParserTest extends TestCase
{
    private FrontmatterParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new FrontmatterParser;
    }

    public function test_parses_valid_frontmatter(): void
    {
        $markdown = <<<'MD'
---
title: Test Title
slug: test-slug
audience: site-owner
category: getting-started
order: 10
---

# Content Here

This is the body.
MD;

        $result = $this->parser->parse($markdown);

        $this->assertNull($result['error']);
        $this->assertEquals('Test Title', $result['frontmatter']['title']);
        $this->assertEquals('test-slug', $result['frontmatter']['slug']);
        $this->assertEquals('site-owner', $result['frontmatter']['audience']);
        $this->assertEquals('getting-started', $result['frontmatter']['category']);
        $this->assertEquals(10, $result['frontmatter']['order']);
        $this->assertStringContainsString('# Content Here', $result['content']);
        $this->assertStringContainsString('This is the body.', $result['content']);
        $this->assertStringNotContainsString('---', $result['content']);
    }

    public function test_handles_missing_frontmatter(): void
    {
        $markdown = "# No frontmatter here\n\nJust content.";
        $result = $this->parser->parse($markdown);

        $this->assertNull($result['error']);
        $this->assertEmpty($result['frontmatter']);
        $this->assertEquals($markdown, $result['content']);
    }

    public function test_handles_invalid_yaml(): void
    {
        $markdown = <<<'MD'
---
invalid: yaml: here: broken
  - also bad
---

# Content
MD;

        $result = $this->parser->parse($markdown);

        $this->assertNotNull($result['error']);
        $this->assertStringContainsString('Invalid YAML', $result['error']);
    }

    public function test_handles_empty_frontmatter(): void
    {
        $markdown = <<<'MD'
---
---

# Content only
MD;

        $result = $this->parser->parse($markdown);

        $this->assertNull($result['error']);
        $this->assertEmpty($result['frontmatter']);
        $this->assertStringContainsString('# Content only', $result['content']);
    }

    public function test_parses_optional_fields(): void
    {
        $markdown = <<<'MD'
---
title: Guide with Extras
slug: guide-extras
audience: developer
category: developers
order: 20
time: 15
prerequisites:
  - installation
  - first-page
hidden: false
---

# Guide Content
MD;

        $result = $this->parser->parse($markdown);

        $this->assertNull($result['error']);
        $this->assertEquals(15, $result['frontmatter']['time']);
        $this->assertEquals(['installation', 'first-page'], $result['frontmatter']['prerequisites']);
        $this->assertFalse($result['frontmatter']['hidden']);
    }

    public function test_parses_hidden_field(): void
    {
        $markdown = <<<'MD'
---
title: Hidden Doc
slug: hidden-doc
audience: all
category: reference
order: 99
hidden: true
---

# Internal Only
MD;

        $result = $this->parser->parse($markdown);

        $this->assertNull($result['error']);
        $this->assertTrue($result['frontmatter']['hidden']);
    }

    public function test_handles_frontmatter_with_special_characters(): void
    {
        $markdown = <<<'MD'
---
title: "Using Blocks: A Guide"
slug: blocks-guide
description: "Learn about blocks — the building blocks of content"
---

# Content
MD;

        $result = $this->parser->parse($markdown);

        $this->assertNull($result['error']);
        $this->assertEquals('Using Blocks: A Guide', $result['frontmatter']['title']);
        $this->assertEquals('Learn about blocks — the building blocks of content', $result['frontmatter']['description']);
    }

    public function test_handles_incomplete_frontmatter_delimiters(): void
    {
        // Only one delimiter
        $markdown = "---\ntitle: Test\nNo closing delimiter";
        $result = $this->parser->parse($markdown);

        $this->assertNull($result['error']);
        $this->assertEmpty($result['frontmatter']);
        $this->assertEquals($markdown, $result['content']);
    }

    public function test_preserves_content_with_hr_elements(): void
    {
        $markdown = <<<'MD'
---
title: Test
slug: test
audience: all
category: reference
order: 1
---

# Section 1

Some content.

---

# Section 2

More content after horizontal rule.
MD;

        $result = $this->parser->parse($markdown);

        $this->assertNull($result['error']);
        $this->assertStringContainsString('# Section 1', $result['content']);
        $this->assertStringContainsString('---', $result['content']);
        $this->assertStringContainsString('# Section 2', $result['content']);
    }

    public function test_content_is_trimmed(): void
    {
        $markdown = <<<'MD'
---
title: Test
slug: test
audience: all
category: reference
order: 1
---


# Content with leading whitespace


MD;

        $result = $this->parser->parse($markdown);

        $this->assertNull($result['error']);
        $this->assertStringStartsWith('#', $result['content']);
    }
}
