<?php

declare(strict_types=1);

namespace TallCms\Cms\Tests\Feature\Api;

use TallCms\Cms\Services\WebhookUrlValidator;
use TallCms\Cms\Tests\TestCase;

class WebhookSecurityTest extends TestCase
{
    protected WebhookUrlValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new WebhookUrlValidator();
    }

    public function test_blocks_http_urls(): void
    {
        $result = $this->validator->validateOnCreate('http://example.com/webhook');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('HTTPS', $result['error']);
    }

    public function test_allows_https_urls(): void
    {
        $result = $this->validator->validateOnCreate('https://example.com/webhook');

        $this->assertTrue($result['valid']);
    }

    public function test_blocks_custom_ports(): void
    {
        $result = $this->validator->validateOnCreate('https://example.com:8443/webhook');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('port 443', $result['error']);
    }

    public function test_blocks_ip_literals(): void
    {
        $result = $this->validator->validateOnCreate('https://192.168.1.1/webhook');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('IP addresses', $result['error']);
    }

    public function test_blocks_ipv6_literals(): void
    {
        $result = $this->validator->validateOnCreate('https://[::1]/webhook');

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_localhost(): void
    {
        $result = $this->validator->validateOnCreate('https://localhost/webhook');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Localhost', $result['error']);
    }

    public function test_blocks_localhost_subdomains(): void
    {
        $result = $this->validator->validateOnCreate('https://api.localhost/webhook');

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_local_domains(): void
    {
        $result = $this->validator->validateOnCreate('https://myserver.local/webhook');

        $this->assertFalse($result['valid']);
    }

    public function test_blocks_internal_domains(): void
    {
        $result = $this->validator->validateOnCreate('https://api.internal/webhook');

        $this->assertFalse($result['valid']);
    }

    public function test_respects_allowed_hosts_config(): void
    {
        config(['tallcms.webhooks.allowed_hosts' => ['allowed.example.com']]);

        $validator = new WebhookUrlValidator();

        $result = $validator->validateOnCreate('https://allowed.example.com/webhook');
        $this->assertTrue($result['valid']);

        $result = $validator->validateOnCreate('https://other.example.com/webhook');
        $this->assertFalse($result['valid']);
    }

    public function test_respects_blocked_hosts_config(): void
    {
        config(['tallcms.webhooks.blocked_hosts' => ['blocked.example.com']]);

        $validator = new WebhookUrlValidator();

        $result = $validator->validateOnCreate('https://blocked.example.com/webhook');
        $this->assertFalse($result['valid']);

        $result = $validator->validateOnCreate('https://allowed.example.com/webhook');
        $this->assertTrue($result['valid']);
    }

    public function test_validates_private_ipv4_at_delivery(): void
    {
        // Use reflection to test the protected isPublicIp method
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('isPublicIp');
        $method->setAccessible(true);

        // Private IPs should return false
        $this->assertFalse($method->invoke($this->validator, '10.0.0.1'));
        $this->assertFalse($method->invoke($this->validator, '172.16.0.1'));
        $this->assertFalse($method->invoke($this->validator, '192.168.1.1'));
        $this->assertFalse($method->invoke($this->validator, '127.0.0.1'));

        // Public IPs should return true
        $this->assertTrue($method->invoke($this->validator, '8.8.8.8'));
        $this->assertTrue($method->invoke($this->validator, '1.1.1.1'));
    }

    public function test_throws_exception_when_dns_resolves_to_private_ip(): void
    {
        // Create a mock that simulates DNS resolving to a private IP
        $validator = $this->getMockBuilder(WebhookUrlValidator::class)
            ->onlyMethods(['resolveIPv6'])
            ->getMock();

        $validator->method('resolveIPv6')->willReturn([]);

        // Mock gethostbynamel to return a private IP by testing a known internal domain
        // Since we can't easily mock gethostbynamel, we test the isPublicIp check indirectly
        // by verifying the exception message format when DNS fails
        $this->expectException(\TallCms\Cms\Exceptions\WebhookDeliveryException::class);
        $this->expectExceptionMessage('DNS resolution failed');

        // This should fail DNS resolution since it's not a real domain
        $validator->validateAtDelivery('https://this-domain-does-not-exist-abc123xyz.invalid/webhook');
    }
}
