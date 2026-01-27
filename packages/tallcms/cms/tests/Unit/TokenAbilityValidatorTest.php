<?php

declare(strict_types=1);

namespace TallCms\Cms\Tests\Unit;

use TallCms\Cms\Tests\TestCase;
use TallCms\Cms\Validation\TokenAbilityValidator;

class TokenAbilityValidatorTest extends TestCase
{
    protected TokenAbilityValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new TokenAbilityValidator();
    }

    public function test_validates_known_abilities(): void
    {
        $result = $this->validator->validate(['pages:read', 'posts:write']);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['invalid']);
    }

    public function test_rejects_unknown_abilities(): void
    {
        $result = $this->validator->validate(['pages:read', 'unknown:ability']);

        $this->assertFalse($result['valid']);
        $this->assertContains('unknown:ability', $result['invalid']);
    }

    public function test_filters_to_valid_abilities(): void
    {
        $filtered = $this->validator->filter(['pages:read', 'unknown:ability', 'posts:write']);

        $this->assertContains('pages:read', $filtered);
        $this->assertContains('posts:write', $filtered);
        $this->assertNotContains('unknown:ability', $filtered);
    }

    public function test_returns_all_valid_abilities(): void
    {
        $abilities = $this->validator->getValidAbilities();

        $this->assertContains('pages:read', $abilities);
        $this->assertContains('pages:write', $abilities);
        $this->assertContains('pages:delete', $abilities);
        $this->assertContains('posts:read', $abilities);
        $this->assertContains('posts:write', $abilities);
        $this->assertContains('posts:delete', $abilities);
        $this->assertContains('categories:read', $abilities);
        $this->assertContains('media:read', $abilities);
        $this->assertContains('webhooks:manage', $abilities);
    }

    public function test_checks_single_ability_validity(): void
    {
        $this->assertTrue($this->validator->isValid('pages:read'));
        $this->assertTrue($this->validator->isValid('webhooks:manage'));
        $this->assertFalse($this->validator->isValid('invalid:ability'));
        $this->assertFalse($this->validator->isValid(''));
    }

    public function test_all_valid_abilities_are_defined(): void
    {
        $expected = [
            'pages:read', 'pages:write', 'pages:delete',
            'posts:read', 'posts:write', 'posts:delete',
            'categories:read', 'categories:write', 'categories:delete',
            'media:read', 'media:write', 'media:delete',
            'webhooks:manage',
        ];

        $this->assertEquals($expected, TokenAbilityValidator::VALID_ABILITIES);
    }
}
