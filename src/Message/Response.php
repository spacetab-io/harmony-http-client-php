<?php declare(strict_types=1);

namespace HarmonyIO\HttpClient\Message;

use Amp\Http\Client\Response as AmpResponse;
use HarmonyIO\Cache\CacheableResponse;
use HarmonyIO\HttpClient\Exception\InvalidCachedResponse;

class Response implements CacheableResponse
{
    /** @var string */
    private $protocolVersion;

    /** @var int */
    private $numericalStatusCode;

    /** @var string */
    private $textualStatusCode;

    /** @var array<string, string[]> */
    private $headers = [];

    /** @var string */
    private $body;

    public function __construct(AmpResponse $response, string $body)
    {
        $this->protocolVersion     = $response->getProtocolVersion();
        $this->numericalStatusCode = $response->getStatus();
        $this->textualStatusCode   = $response->getReason();
        $this->headers             = $response->getHeaders();
        $this->body                = $body;
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function getNumericalStatusCode(): int
    {
        return $this->numericalStatusCode;
    }

    public function getTextualStatusCode(): string
    {
        return $this->textualStatusCode;
    }

    public function hasHeader(string $key): bool
    {
        return array_key_exists($key, $this->headers);
    }

    public function getHeader(string $key): ?string
    {
        if (!array_key_exists($key, $this->headers)) {
            return null;
        }

        return $this->headers[$key][0];
    }

    /**
     * @param string $key
     * @return string[]
     */
    public function getHeaderArray(string $key): array
    {
        if (!array_key_exists($key, $this->headers)) {
            return [];
        }

        return $this->headers[$key];
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function serialize(): string
    {
        // json_encode fails with google.com contents.
        // "Malformed UTF-8 characters, possibly incorrectly encoded"
        return serialize([
            'protocolVersion'     => $this->protocolVersion,
            'numericalStatusCode' => $this->numericalStatusCode,
            'textualStatusCode'   => $this->textualStatusCode,
            'headers'             => $this->headers,
            'body'                => $this->body,
        ]);
    }

    /**
     * @param string $serialized
     * @throws InvalidCachedResponse
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
    public function unserialize($serialized): void
    {
        $cachedData = unserialize($serialized);

        if ($cachedData === false) {
            throw new InvalidCachedResponse();
        }

        $this->protocolVersion     = $cachedData['protocolVersion'];
        $this->numericalStatusCode = $cachedData['numericalStatusCode'];
        $this->textualStatusCode   = $cachedData['textualStatusCode'];
        $this->headers             = $cachedData['headers'];
        $this->body                = $cachedData['body'];
    }
}
