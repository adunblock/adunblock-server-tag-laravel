<?php

namespace AdUnblock\ServerTagLaravel\Tests;

use AdUnblock\ServerTagLaravel\ServerTagServiceProvider;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;

class ServerTagTest extends TestCase
{
    private const SOURCES = 'https://sources.example/api/vendor_scripts';
    private const SCRIPT_1 = 'https://one.example/a.js';
    private const SCRIPT_2 = 'https://two.example/b.js';

    protected function getPackageProviders($app)
    {
        return [ServerTagServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cache.default', 'array');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake([
            'sources.example/api/vendor_scripts/empty*' => Http::response([]),
            '*' => Http::response([self::SCRIPT_1, self::SCRIPT_2]),
        ]);
    }

    private function visit(string $url): void
    {
        $this->app->instance('request', Request::create($url));
    }

    /** @return array<int, string|null> the domain each sources request named */
    private function requestedDomains(): array
    {
        return Http::recorded()->map(function ($pair) {
            /** @var ClientRequest $request */
            $request = $pair[0];
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            return $query['domain'] ?? null;
        })->all();
    }

    public function testDefaultTagOrderAndFirstUrlOnly(): void
    {
        $this->assertSame(
            '<script data-cfasync="false" nowprocket src="' . self::SCRIPT_1 . '" async></script>',
            server_tag(self::SOURCES)
        );
    }

    public function testScriptAttributesComeAfterSrc(): void
    {
        $this->assertSame(
            '<script data-cfasync="false" nowprocket src="' . self::SCRIPT_1 . '" async data-code="12345678"></script>',
            server_tag(self::SOURCES, 300, null, ['data-code' => '12345678'])
        );
    }

    public function testCallerDataCfasyncStaysAheadOnceAndDefaultsCanBeDropped(): void
    {
        $this->assertSame(
            '<script data-cfasync="false" nowprocket src="' . self::SCRIPT_1 . '" async data-code="1"></script>',
            server_tag(self::SOURCES, 300, null, ['data-cfasync' => 'false', 'data-code' => '1'])
        );
        $this->assertSame(
            '<script src="' . self::SCRIPT_1 . '"></script>',
            server_tag(self::SOURCES, 300, null, ['data-cfasync' => false, 'nowprocket' => false, 'async' => false])
        );
    }

    public function testRenderScriptStillGetsEveryUrl(): void
    {
        $seen = null;
        $html = server_tag(self::SOURCES, 300, function ($scripts) use (&$seen) {
            $seen = $scripts;
            return 'custom';
        });
        $this->assertSame([self::SCRIPT_1, self::SCRIPT_2], $seen);
        $this->assertSame('custom', $html);
    }

    public function testEmptyListRendersNothing(): void
    {
        $this->assertSame('', server_tag(self::SOURCES . '/empty'));
    }

    public function testDomainDefaultsToTheRequestHost(): void
    {
        $this->visit('http://www.publisher.com:8080/page');
        server_tag(self::SOURCES);
        $this->assertSame(['www.publisher.com'], $this->requestedDomains());
    }

    public function testDomainArgumentWins(): void
    {
        $this->visit('http://other.com/');
        server_tag(self::SOURCES, 300, null, null, 'chosen.com');
        $this->assertSame(['chosen.com'], $this->requestedDomains());
    }

    public function testDomainAlreadyInUrlIsKept(): void
    {
        $this->visit('http://other.com/');
        server_tag(self::SOURCES . '?domain=fixed.com');
        $this->assertSame(['fixed.com'], $this->requestedDomains());
    }

    public function testCacheIsPerDomain(): void
    {
        server_tag(self::SOURCES, 300, null, null, 'a.com');
        server_tag(self::SOURCES, 300, null, null, 'a.com');
        server_tag(self::SOURCES, 300, null, null, 'b.com');
        $this->assertSame(['a.com', 'b.com'], $this->requestedDomains());
    }

    public function testWithDomainEncodesAndKeepsTheQuery(): void
    {
        $this->assertSame(
            self::SOURCES . '/v1?x=1&domain=my%20site.com',
            server_tag_with_domain(self::SOURCES . '/v1?x=1', 'my site.com')
        );
        $this->assertSame(self::SOURCES, server_tag_with_domain(self::SOURCES, null));
        $this->assertSame(self::SOURCES, server_tag_with_domain(self::SOURCES, ''));
    }
}
