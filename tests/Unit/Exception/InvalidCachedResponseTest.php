<?php declare(strict_types=1);

namespace HarmonyIO\HttpClientTest\Unit\Exception;

use Amp\PHPUnit\AsyncTestCase;
use HarmonyIO\HttpClient\Exception\InvalidCachedResponse;

class InvalidCachedResponseTest extends AsyncTestCase
{
    public function testMessage(): void
    {
        $this->expectException(InvalidCachedResponse::class);
        $this->expectExceptionMessage('The cached response is in an unexpected format.');

        throw new InvalidCachedResponse();
    }
}
