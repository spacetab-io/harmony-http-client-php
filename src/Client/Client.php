<?php declare(strict_types=1);

namespace HarmonyIO\HttpClient\Client;

use Amp\CancellationToken;
use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\Request;
use Amp\Promise;
use HarmonyIO\HttpClient\Message\CachingRequest;

interface Client extends DelegateHttpClient
{
    /**
     * @param Request|CachingRequest $request
     * @param CancellationToken|null $cancellation
     * @return Promise
     */
    public function request($request, ?CancellationToken $cancellation = null): Promise;
}
