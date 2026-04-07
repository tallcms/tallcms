<?php

namespace TallCms\Cms\Tests\Unit;

use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Services\SeoService;
use TallCms\Cms\Tests\TestCase;

class SeoServiceJsonLdTest extends TestCase
{
    public function test_author_includes_job_title_when_set(): void
    {
        $post = new CmsPost;
        $post->title = 'Test Post';
        $post->slug = 'test-post';
        $post->content = 'Some content';

        $author = new \stdClass;
        $author->name = 'Jane Doe';
        $author->slug = 'jane-doe';
        $author->job_title = 'Senior Editor';
        $author->company = 'Acme Corp';
        $author->linkedin_url = 'https://linkedin.com/in/janedoe';
        $author->twitter_handle = '@janedoe';

        $post->setRelation('author', $author);
        $post->setRelation('categories', collect());

        $jsonLd = SeoService::getPostJsonLd($post);

        $this->assertEquals('Person', $jsonLd['author']['@type']);
        $this->assertEquals('Senior Editor', $jsonLd['author']['jobTitle']);
        $this->assertEquals('Acme Corp', $jsonLd['author']['worksFor']['name']);
        $this->assertEquals('Organization', $jsonLd['author']['worksFor']['@type']);
        $this->assertContains('https://linkedin.com/in/janedoe', $jsonLd['author']['sameAs']);
        $this->assertContains('https://x.com/janedoe', $jsonLd['author']['sameAs']);
    }

    public function test_author_omits_optional_fields_when_empty(): void
    {
        $post = new CmsPost;
        $post->title = 'Test Post';
        $post->slug = 'test-post';
        $post->content = 'Some content';

        $author = new \stdClass;
        $author->name = 'John Smith';
        $author->slug = 'john-smith';
        $author->job_title = null;
        $author->company = null;
        $author->linkedin_url = null;
        $author->twitter_handle = null;

        $post->setRelation('author', $author);
        $post->setRelation('categories', collect());

        $jsonLd = SeoService::getPostJsonLd($post);

        $this->assertArrayNotHasKey('jobTitle', $jsonLd['author']);
        $this->assertArrayNotHasKey('worksFor', $jsonLd['author']);
        $this->assertArrayNotHasKey('sameAs', $jsonLd['author']);
    }

    public function test_reviewed_by_included_when_expert_reviewer_set(): void
    {
        $post = new CmsPost;
        $post->title = 'Medical Guide';
        $post->slug = 'medical-guide';
        $post->content = 'Content';
        $post->expert_reviewer_name = 'Dr. Jane Smith';
        $post->expert_reviewer_title = 'Medical Doctor';
        $post->expert_reviewer_url = 'https://example.com/dr-smith';

        $post->setRelation('author', null);
        $post->setRelation('categories', collect());

        $jsonLd = SeoService::getPostJsonLd($post);

        $this->assertEquals('Person', $jsonLd['reviewedBy']['@type']);
        $this->assertEquals('Dr. Jane Smith', $jsonLd['reviewedBy']['name']);
        $this->assertEquals('Medical Doctor', $jsonLd['reviewedBy']['jobTitle']);
        $this->assertEquals('https://example.com/dr-smith', $jsonLd['reviewedBy']['url']);
    }

    public function test_reviewed_by_omitted_when_no_expert_reviewer(): void
    {
        $post = new CmsPost;
        $post->title = 'Basic Post';
        $post->slug = 'basic-post';
        $post->content = 'Content';

        $post->setRelation('author', null);
        $post->setRelation('categories', collect());

        $jsonLd = SeoService::getPostJsonLd($post);

        $this->assertArrayNotHasKey('reviewedBy', $jsonLd);
    }

    public function test_last_reviewed_included_when_set(): void
    {
        $post = new CmsPost;
        $post->title = 'Reviewed Post';
        $post->slug = 'reviewed-post';
        $post->content = 'Content';
        $post->last_reviewed_at = now();

        $post->setRelation('author', null);
        $post->setRelation('categories', collect());

        $jsonLd = SeoService::getPostJsonLd($post);

        $this->assertArrayHasKey('lastReviewed', $jsonLd);
    }

    public function test_citation_array_from_sources(): void
    {
        $post = new CmsPost;
        $post->title = 'Cited Post';
        $post->slug = 'cited-post';
        $post->content = 'Content';
        $post->sources = [
            ['title' => 'Source One', 'url' => 'https://example.com/one'],
            ['title' => 'Source Two', 'url' => 'https://example.com/two'],
        ];

        $post->setRelation('author', null);
        $post->setRelation('categories', collect());

        $jsonLd = SeoService::getPostJsonLd($post);

        $this->assertCount(2, $jsonLd['citation']);
        $this->assertEquals('CreativeWork', $jsonLd['citation'][0]['@type']);
        $this->assertEquals('Source One', $jsonLd['citation'][0]['name']);
        $this->assertEquals('https://example.com/one', $jsonLd['citation'][0]['url']);
    }

    public function test_citation_omitted_when_no_sources(): void
    {
        $post = new CmsPost;
        $post->title = 'No Sources';
        $post->slug = 'no-sources';
        $post->content = 'Content';
        $post->sources = null;

        $post->setRelation('author', null);
        $post->setRelation('categories', collect());

        $jsonLd = SeoService::getPostJsonLd($post);

        $this->assertArrayNotHasKey('citation', $jsonLd);
    }
}
