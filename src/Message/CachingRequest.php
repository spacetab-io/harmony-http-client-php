<?php declare(strict_types=1);

namespace HarmonyIO\HttpClient\Message;

use Amp\Http\Client\Request;
use HarmonyIO\Cache\CacheableRequest;
use HarmonyIO\Cache\Key;
use HarmonyIO\Cache\Ttl;

class CachingRequest implements CacheableRequest
{
    private const CACHE_TYPE = 'HttpRequest';

    /** @var string */
    private $key;

    /** @var Ttl */
    private $ttl;

    /** @var Request */
    private $request;

    /**
     * CachingRequest constructor.
     *
     * @param string $key
     * @param string $uri
     * @param string $method
     * @param Ttl|null $ttl
     */
    public function __construct(string $key, string $uri, string $method = 'GET', ?Ttl $ttl = null)
    {
        $this->key    = $key;
        $this->ttl    = $ttl;

        $this->request = new Request($uri, $method);
    }

    public function getCachingKey(): Key
    {
        $hashKey = sprintf(
            '%s-%d-%s-%s',
            $this->key,
            $this->getTtl()->getTtlInSeconds(),
            $this->getRequest()->getUri(),
            $this->getRequest()->getMethod()
        );

        return new Key(self::CACHE_TYPE, $this->key, md5(serialize($hashKey)));
    }

    public function getTtl(): Ttl
    {
        if (is_null($this->ttl)) {
            return new Ttl(Ttl::ONE_HOUR);
        }

        return $this->ttl;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
