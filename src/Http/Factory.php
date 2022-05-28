<?php

namespace Elzdave\Benevolent\Http;

use Illuminate\Http\Client\Factory as ClientFactory;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * @method \Elzdave\Benevolent\PendingRequest useAuth(string $token = null, string $schema = null)
 * @method \Elzdave\Benevolent\PendingRequest accept(string $contentType)
 * @method \Elzdave\Benevolent\PendingRequest acceptJson()
 * @method \Elzdave\Benevolent\PendingRequest asForm()
 * @method \Elzdave\Benevolent\PendingRequest asJson()
 * @method \Elzdave\Benevolent\PendingRequest asMultipart()
 * @method \Elzdave\Benevolent\PendingRequest async()
 * @method \Elzdave\Benevolent\PendingRequest attach(string|array $name, string|resource $contents = '', string|null $filename = null, array $headers = [])
 * @method \Elzdave\Benevolent\PendingRequest baseUrl(string $url)
 * @method \Elzdave\Benevolent\PendingRequest beforeSending(callable $callback)
 * @method \Elzdave\Benevolent\PendingRequest bodyFormat(string $format)
 * @method \Elzdave\Benevolent\PendingRequest contentType(string $contentType)
 * @method \Elzdave\Benevolent\PendingRequest dd()
 * @method \Elzdave\Benevolent\PendingRequest dump()
 * @method \Elzdave\Benevolent\PendingRequest retry(int $times, int $sleep = 0, ?callable $when = null)
 * @method \Elzdave\Benevolent\PendingRequest sink(string|resource $to)
 * @method \Elzdave\Benevolent\PendingRequest stub(callable $callback)
 * @method \Elzdave\Benevolent\PendingRequest timeout(int $seconds)
 * @method \Elzdave\Benevolent\PendingRequest withBasicAuth(string $username, string $password)
 * @method \Elzdave\Benevolent\PendingRequest withBody(resource|string $content, string $contentType)
 * @method \Elzdave\Benevolent\PendingRequest withCookies(array $cookies, string $domain)
 * @method \Elzdave\Benevolent\PendingRequest withDigestAuth(string $username, string $password)
 * @method \Elzdave\Benevolent\PendingRequest withHeaders(array $headers)
 * @method \Elzdave\Benevolent\PendingRequest withMiddleware(callable $middleware)
 * @method \Elzdave\Benevolent\PendingRequest withOptions(array $options)
 * @method \Elzdave\Benevolent\PendingRequest withToken(string $token, string $type = 'Bearer')
 * @method \Elzdave\Benevolent\PendingRequest withUserAgent(string $userAgent)
 * @method \Elzdave\Benevolent\PendingRequest withoutRedirecting()
 * @method \Elzdave\Benevolent\PendingRequest withoutVerifying()
 * @method array pool(callable $callback)
 * @method \Illuminate\Http\Client\Response delete(string $url, array $data = [])
 * @method \Illuminate\Http\Client\Response get(string $url, array|string|null $query = null)
 * @method \Illuminate\Http\Client\Response head(string $url, array|string|null $query = null)
 * @method \Illuminate\Http\Client\Response patch(string $url, array $data = [])
 * @method \Illuminate\Http\Client\Response post(string $url, array $data = [])
 * @method \Illuminate\Http\Client\Response put(string $url, array $data = [])
 * @method \Illuminate\Http\Client\Response send(string $method, string $url, array $options = [])
 *
 * @see \Illuminate\Http\Client\PendingRequest
 */
class Factory extends ClientFactory
{
    /**
     * Create a new factory instance.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher|null  $dispatcher
     * @return void
     */
    public function __construct(Dispatcher $dispatcher = null)
    {
        parent::__construct($dispatcher);
    }

    /**
     * Create a new pending request instance for this factory.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function newPendingRequest()
    {
        return new PendingRequest($this);
    }
}
