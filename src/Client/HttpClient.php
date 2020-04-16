<?php declare(strict_types=1);

namespace HarmonyIO\HttpClient\Client;

use Amp\CancellationToken;
use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response as AmpResponse;
use Amp\NullCancellationToken;
use Amp\Promise;
use HarmonyIO\Cache\Cache;
use HarmonyIO\Cache\Item;
use HarmonyIO\HttpClient\Message\CachingRequest;
use HarmonyIO\HttpClient\Message\Response;
use function Amp\call;

class HttpClient implements Client
{
    /** @var DelegateHttpClient */
    private $httpClient;

    /** @var Cache */
    private $cache;

    public function __construct(Client $httpClient, Cache $cache)
    {
        $this->httpClient = $httpClient;
        $this->cache      = $cache;
    }

    /**
     * @param Request|CachingRequest $request
     * @param CancellationToken|null $cancellationToken
     * @return Promise
     */
    public function request($request, ?CancellationToken $cancellationToken = null): Promise
    {
        if ($request instanceof CachingRequest) {
            return $this->makeCachingRequest($request, $cancellationToken);
        }

        return $this->makeRequest($request, $cancellationToken);
    }

    private function makeCachingRequest(CachingRequest $request, ?CancellationToken $cancellationToken = null): Promise
    {
        return call(function () use ($request, $cancellationToken) {
            if (!yield $this->cache->exists($request->getCachingKey())) {
                /** @var Response $response */
                $response = yield $this->makeRequest($request->getRequest(), $cancellationToken);

                if ($response->getNumericalStatusCode() >= 400) {
                    return $response;
                }

                yield $this->cache->store(new Item(
                    $request->getCachingKey(),
                    serialize($response),
                    $request->getTtl()
                ));
            }

            return unserialize(yield $this->cache->get($request->getCachingKey()));
        });
    }

    private function makeRequest(Request $request, ?CancellationToken $cancellationToken = null): Promise
    {
        if (is_null($cancellationToken)) {
            $cancellationToken = new NullCancellationToken();
        }

        return call(function () use ($request, $cancellationToken) {
            /** @var AmpResponse $response */
            $response = yield $this->httpClient->request($request, $cancellationToken);
            $body     = yield $response->getBody()->buffer();

            return new Response($response, $body);
        });
    }
}
