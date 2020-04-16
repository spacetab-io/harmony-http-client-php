<?php declare(strict_types=1);

namespace HarmonyIO\HttpClientTest\Unit\Client;

use Amp\ByteStream\InputStream;
use Amp\ByteStream\Payload;
use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\HttpClient as AmpHttpClient;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response as AmpResponse;
use Amp\Loop;
use Amp\PHPUnit\AsyncTestCase;
use Amp\Success;
use HarmonyIO\Cache\Cache;
use HarmonyIO\Cache\Provider\InMemory;
use HarmonyIO\HttpClient\Client\Client;
use HarmonyIO\HttpClient\Client\HttpClient;
use HarmonyIO\HttpClient\Message\CachingRequest;
use HarmonyIO\HttpClient\Message\Response;
use PHPUnit\Framework\MockObject\MockObject;

class HttpClientTest extends AsyncTestCase
{
    /** @var MockObject|AmpHttpClient */
    private $httpClient;

    /** @var MockObject|Cache */
    private $cache;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp(): void
    {
        parent::setUp();

        $this->httpClient = $this->createMock(Client::class);
        $this->cache      = $this->createMock(Cache::class);
    }

    public function testConstructorReceiveAnHttpClientArgumentFirst(): void
    {
        $client = new HttpClient($this->createMock(DelegateHttpClient::class), new InMemory());

        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testImplementsDelegatedHttpClientInterface(): void
    {
        $httpClient = new HttpClient($this->httpClient, $this->cache);

        $this->assertInstanceOf(DelegateHttpClient::class, $httpClient);
    }

    public function testRequestDoesNotCache(): void
    {
        $this->cache
            ->expects($this->never())
            ->method('exists')
        ;

        $this->cache
            ->expects($this->never())
            ->method('store')
        ;

        $this->cache
            ->expects($this->never())
            ->method('get')
        ;

        $this->httpClient
            ->method('request')
            //phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
            ->willReturnCallback(function () {
                /** @var MockObject|InputStream $inputStream */
                $inputStream = $this->createMock(InputStream::class);
                $inputStream
                    ->method('read')
                    ->willReturnOnConsecutiveCalls(new Success('foobar'), new Success(null));

                $payload = new Payload($inputStream);
                $response = $this->createMock(Response::class);
                $response
                    ->method('getBody')
                    ->willReturn($payload);

                return new Success($response);
            });

        $httpClient = new HttpClient($this->httpClient, $this->cache);

        $httpClient->request(new Request('https://example.com'));
    }

    public function testRequestCachesCachingRequest(): void
    {
        $this->cache
            ->method('exists')
            ->willReturnOnConsecutiveCalls(new Success(false))
        ;

        $this->cache
            ->expects($this->once())
            ->method('store')
            ->willReturn(new Success())
        ;

        $this->cache
            ->method('get')
            ->willReturn(new Success(require TEST_FIXTURE_DIR . '/Message/serialized-response.php'))
        ;

        $this->httpClient
            ->method('request')
            //phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
            ->willReturnCallback(function () {
                /** @var MockObject|InputStream $inputStream */
                $inputStream = $this->createMock(InputStream::class);

                $inputStream
                    ->method('read')
                    ->willReturnOnConsecutiveCalls(new Success('foobar'), new Success(null));

                return new Success(
                    new AmpResponse('1.0', 200, null, [], $inputStream, new Request(''))
                );
            })
        ;

        $client = new HttpClient($this->httpClient, $this->cache);

        Loop::run(static function () use ($client) {
            yield $client->request(new CachingRequest('Key', 'https://example.com'));
        });
    }

    public function testRequestDoesNotCacheErroringCachingRequest(): void
    {
        $this->cache
            ->expects($this->exactly(2))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(new Success(false), new Success(false))
        ;

        $this->cache
            ->expects($this->never())
            ->method('store')
            ->willReturn(new Success())
        ;

        $this->cache
            ->expects($this->never())
            ->method('get')
            ->willReturn(new Success(require TEST_FIXTURE_DIR . '/Message/serialized-response.php'))
        ;

        $this->httpClient
            ->method('request')
            //phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
            ->willReturnCallback(function () {
                /** @var MockObject|InputStream $inputStream */
                $inputStream = $this->createMock(InputStream::class);

                $inputStream
                    ->method('read')
                    ->willReturnOnConsecutiveCalls(new Success('foobar'), new Success(null));

                return new Success(
                    new AmpResponse('1.0', 404, null, [], $inputStream, new Request(''))
                );
            });

        $client = new HttpClient($this->httpClient, $this->cache);

        Loop::run(static function () use ($client) {
            yield $client->request(new CachingRequest('Key', 'https://google.com'));
            yield $client->request(new CachingRequest('Key', 'https://google.com'));
        });
    }

    public function testRequestUsesCacheForConsecutiveCachingRequests(): void
    {
        $this->cache
            ->expects($this->exactly(2))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(new Success(false), new Success(true))
        ;

        $this->cache
            ->expects($this->once())
            ->method('store')
            ->willReturn(new Success())
        ;

        $this->cache
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturn(new Success(require TEST_FIXTURE_DIR . '/Message/serialized-response.php'))
        ;

        $this->httpClient
            ->method('request')
            //phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
            ->willReturnCallback(function () {
                /** @var MockObject|InputStream $inputStream */
                $inputStream = $this->createMock(InputStream::class);

                $inputStream
                    ->method('read')
                    ->willReturnOnConsecutiveCalls(new Success('foobar'), new Success(null));

                return new Success(
                    new AmpResponse('1.0', 200, null, [], $inputStream, new Request(''))
                );
            })
        ;

        $client = new HttpClient($this->httpClient, $this->cache);

        Loop::run(static function () use ($client) {
            yield $client->request(new CachingRequest('Key', 'https://example.com'));
            yield $client->request(new CachingRequest('Key', 'https://example.com'));
        });
    }
}
