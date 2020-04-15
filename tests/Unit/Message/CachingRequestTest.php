<?php declare(strict_types=1);

namespace HarmonyIO\HttpClientTest\Unit\Message;

use Amp\PHPUnit\AsyncTestCase;
use HarmonyIO\Cache\CacheableRequest;
use HarmonyIO\Cache\Key;
use HarmonyIO\Cache\Ttl;
use HarmonyIO\HttpClient\Message\CachingRequest;

class CachingRequestTest extends AsyncTestCase
{
    public function testImplementsCachingInterface(): void
    {
        $this->assertInstanceOf(
            CacheableRequest::class,
            (new CachingRequest('TestKey', 'https://example.com'))
        );
    }

    public function testGetCachingKey(): void
    {
        $key = (new CachingRequest('TestKey', 'https://example.com'))->getCachingKey();

        $this->assertInstanceOf(Key::class, $key);
    }

    public function testGetTtl(): void
    {
        $this->assertSame(
            10,
            (new CachingRequest('TestKey', 'https://example.com', 'GET', new Ttl(10)))->getTtl()->getTtlInSeconds()
        );
    }
}
