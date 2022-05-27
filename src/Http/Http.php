<?php

namespace Elzdave\Benevolent\Http;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \GuzzleHttp\Promise\PromiseInterface response($body = null, $status = 200, $headers = [])
 * @method static \Illuminate\Http\Client\Factory fake($callback = null)
 * @method static \Elzdave\Benevolent\PendingRequest useAuth(string $token, string $schema = null)
 * @method static \Elzdave\Benevolent\PendingRequest accept(string $contentType)
 * @method static \Elzdave\Benevolent\PendingRequest acceptJson()
 * @method static \Elzdave\Benevolent\PendingRequest asForm()
 * @method static \Elzdave\Benevolent\PendingRequest asJson()
 * @method static \Elzdave\Benevolent\PendingRequest asMultipart()
 * @method static \Elzdave\Benevolent\PendingRequest async()
 * @method static \Elzdave\Benevolent\PendingRequest attach(string|array $name, string $contents = '', string|null $filename = null, array $headers = [])
 * @method static \Elzdave\Benevolent\PendingRequest baseUrl(string $url)
 * @method static \Elzdave\Benevolent\PendingRequest beforeSending(callable $callback)
 * @method static \Elzdave\Benevolent\PendingRequest bodyFormat(string $format)
 * @method static \Elzdave\Benevolent\PendingRequest contentType(string $contentType)
 * @method static \Elzdave\Benevolent\PendingRequest dd()
 * @method static \Elzdave\Benevolent\PendingRequest dump()
 * @method static \Elzdave\Benevolent\PendingRequest retry(int $times, int $sleep = 0, ?callable $when = null)
 * @method static \Elzdave\Benevolent\PendingRequest sink(string|resource $to)
 * @method static \Elzdave\Benevolent\PendingRequest stub(callable $callback)
 * @method static \Elzdave\Benevolent\PendingRequest timeout(int $seconds)
 * @method static \Elzdave\Benevolent\PendingRequest withBasicAuth(string $username, string $password)
 * @method static \Elzdave\Benevolent\PendingRequest withBody(resource|string $content, string $contentType)
 * @method static \Elzdave\Benevolent\PendingRequest withCookies(array $cookies, string $domain)
 * @method static \Elzdave\Benevolent\PendingRequest withDigestAuth(string $username, string $password)
 * @method static \Elzdave\Benevolent\PendingRequest withHeaders(array $headers)
 * @method static \Elzdave\Benevolent\PendingRequest withMiddleware(callable $middleware)
 * @method static \Elzdave\Benevolent\PendingRequest withOptions(array $options)
 * @method static \Elzdave\Benevolent\PendingRequest withToken(string $token, string $type = 'Bearer')
 * @method static \Elzdave\Benevolent\PendingRequest withUserAgent(string $userAgent)
 * @method static \Elzdave\Benevolent\PendingRequest withoutRedirecting()
 * @method static \Elzdave\Benevolent\PendingRequest withoutVerifying()
 * @method static array pool(callable $callback)
 * @method static \Illuminate\Http\Client\Response delete(string $url, array $data = [])
 * @method static \Illuminate\Http\Client\Response get(string $url, array|string|null $query = null)
 * @method static \Illuminate\Http\Client\Response head(string $url, array|string|null $query = null)
 * @method static \Illuminate\Http\Client\Response patch(string $url, array $data = [])
 * @method static \Illuminate\Http\Client\Response post(string $url, array $data = [])
 * @method static \Illuminate\Http\Client\Response put(string $url, array $data = [])
 * @method static \Illuminate\Http\Client\Response send(string $method, string $url, array $options = [])
 * @method static \Illuminate\Http\Client\ResponseSequence fakeSequence(string $urlPattern = '*')
 * @method static void assertSent(callable $callback)
 * @method static void assertSentInOrder(array $callbacks)
 * @method static void assertNotSent(callable $callback)
 * @method static void assertNothingSent()
 * @method static void assertSentCount(int $count)
 * @method static void assertSequencesAreEmpty()
 *
 * @see \Elzdave\Benevolent\Factory
 */
class Http extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Factory::class;
    }
}
