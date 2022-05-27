<?php

namespace Elzdave\Benevolent;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * @method static \App\Extensions\HttpClient useAuth(string $token = null, string $schema = null)
 * @method static \GuzzleHttp\Promise\PromiseInterface response($body = null, $status = 200, $headers = [])
 * @method static \Illuminate\Http\Client\Factory fake($callback = null)
 * @method static \Illuminate\Http\Client\PendingRequest accept(string $contentType)
 * @method static \Illuminate\Http\Client\PendingRequest acceptJson()
 * @method static \Illuminate\Http\Client\PendingRequest asForm()
 * @method static \Illuminate\Http\Client\PendingRequest asJson()
 * @method static \Illuminate\Http\Client\PendingRequest asMultipart()
 * @method static \Illuminate\Http\Client\PendingRequest attach(string $name, string $contents, string|null $filename = null, array $headers = [])
 * @method static \Illuminate\Http\Client\PendingRequest baseUrl(string $url)
 * @method static \Illuminate\Http\Client\PendingRequest beforeSending(callable $callback)
 * @method static \Illuminate\Http\Client\PendingRequest bodyFormat(string $format)
 * @method static \Illuminate\Http\Client\PendingRequest contentType(string $contentType)
 * @method static \Illuminate\Http\Client\PendingRequest retry(int $times, int $sleep = 0)
 * @method static \Illuminate\Http\Client\PendingRequest stub(callable $callback)
 * @method static \Illuminate\Http\Client\PendingRequest timeout(int $seconds)
 * @method static \Illuminate\Http\Client\PendingRequest withBasicAuth(string $username, string $password)
 * @method static \Illuminate\Http\Client\PendingRequest withBody(resource|string $content, string $contentType)
 * @method static \Illuminate\Http\Client\PendingRequest withCookies(array $cookies, string $domain)
 * @method static \Illuminate\Http\Client\PendingRequest withDigestAuth(string $username, string $password)
 * @method static \Illuminate\Http\Client\PendingRequest withHeaders(array $headers)
 * @method static \Illuminate\Http\Client\PendingRequest withOptions(array $options)
 * @method static \Illuminate\Http\Client\PendingRequest withToken(string $token, string $type = 'Bearer')
 * @method static \Illuminate\Http\Client\PendingRequest withoutRedirecting()
 * @method static \Illuminate\Http\Client\PendingRequest withoutVerifying()
 * @method static \Illuminate\Http\Client\Response delete(string $url, array $data = [])
 * @method static \Illuminate\Http\Client\Response get(string $url, array $query = [])
 * @method static \Illuminate\Http\Client\Response head(string $url, array $query = [])
 * @method static \Illuminate\Http\Client\Response patch(string $url, array $data = [])
 * @method static \Illuminate\Http\Client\Response post(string $url, array $data = [])
 * @method static \Illuminate\Http\Client\Response put(string $url, array $data = [])
 * @method static \Illuminate\Http\Client\Response send(string $method, string $url, array $options = [])
 * @method static \Illuminate\Http\Client\ResponseSequence fakeSequence(string $urlPattern = '*')
 *
 * @see \Illuminate\Http\Client\Factory
 */
class HttpClient
{
    /**
     * The HTTP client instance.
     *
     * @var \Illuminate\Http\Client\PendingRequest
     */
    protected $httpClient = null;

    /**
     * External service base URL.
     *
     * @var string
     */
    protected $baseUrl = '';

    /**
     * Access token refresh URL, relative to base URL.
     *
     * @var string
     */
    protected $refreshTokenUrl = '';

    /**
     * Determine whether current request needs access token.
     *
     * @var bool
     */
    protected $isAuthenticatedRequest = false;

    /**
     * Determine whether to enable refresh token feature.
     *
     * @var bool
     */
    protected $enableRefreshToken = true;

    /**
     * The result data wrapper flag.
     *
     * @var bool
     */
    protected $withoutWrapper = false;

    /**
     * The data wrapper key name.
     *
     * @var string
     */
    protected $wrapperKey = 'data';

    /**
     * The access token key name.
     *
     * @var string
     */
    protected $accessTokenName = 'access_token';

    /**
     * The refresh token key name.
     *
     * @var string
     */
    protected $refreshTokenName = 'refresh_token';


    /**
     * The default session lifetime in minutes.
     *
     * @var int
     */
    protected $sessionLifetime = 120;

    /**
     * Create a new HTTP client instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->loadConfig();

        $this->initialize();
    }

    protected function loadConfig()
    {
        $this->baseUrl = config('benevolent.base_url');
        $this->refreshTokenUrl = config('benevolent.paths.refresh_token');
        $this->enableRefreshToken = config('benevolent.features.enable_refresh_token');
        $this->withoutWrapper = config('benevolent.features.without_wrapper');
        $this->wrapperKey = config('benevolent.keys.wrapper');
        $this->accessTokenName = config('benevolent.keys.access_token');
        $this->refreshTokenName = config('benevolent.keys.refresh_token');

        // this config is part of default Laravel configs
        $this->sessionLifetime = config('session.lifetime', 120);
    }

    public function __call($method, $args)
    {
        if ($this->isHttpMethod($method)) {
            return $this->doTheRequest($method, $args);
        } else {
            if (method_exists($this, $method)) {
                return $this->$method(...$args);
            } else {
                // another undefined object method
                // and not in a list of standard HTTP methods
                // are passed to the built-in HTTP client
                return $this->getInstance()->$method(...$args);
            }
        }
    }

    public static function __callStatic($method, $args)
    {
        $instance = new HttpClient;
        return $instance->$method(...$args);
    }

    /**
     * Initialize HTTP client wrapper
     *
     * @return \App\Extensions\HttpClient
     */
    protected function initialize()
    {
        $this->httpClient = Http::baseUrl($this->baseUrl)->withHeaders([
            'User-Agent' => request()->userAgent(),
            'Cache-Control' => 'no-cache'
        ]);

        return $this;
    }

    /**
     * Enable authenticated URL endpoint access support.
     *
     * @param  string   $token      The access token to override the current user's access token. Default is null.
     * @param  string   $schema     The token schema. Default is 'Bearer'
     * @return \App\Extensions\HttpClient
     */
    protected function useAuth($token = null, $schema = 'Bearer')
    {
        $user = request()->user();

        if ($user) {
            $userToken = $token ?? (method_exists($user, 'getAccessToken') ? $user->getAccessToken() : null);
            $userSchema = method_exists($user, 'getTokenSchema') ? $user->getTokenSchema() : $schema;

            $this->isAuthenticatedRequest = true;
            $this->getInstance()->withToken($userToken, $userSchema);
        } else {
            // The current session is unauthenticated.
            $this->isAuthenticatedRequest = false;
        }

        return $this;
    }

    /**
     * Perform the actual HTTP request.
     *
     * @param  mixed    $method     The HTTP method
     * @param  mixed    $args       The command's arguments
     * @return \Illuminate\Http\Client\Response
     */
    protected function doTheRequest($method, $args)
    {
        $response = $this->getInstance()->$method(...$args);

        if ($response->unauthorized() && $this->isAuthenticatedRequest) {
            $isTokenRefreshed = $this->enableRefreshToken ? $this->refreshToken() : false;

            // Token didn't refreshed. Will terminate the session...
            if (! $isTokenRefreshed) {
                $this->terminateSession();
            } else {
                // Retrying request...
                $response = $this->getInstance()->$method(...$args);
            }
        }

        return $response;
    }

    /**
     * Obtain the HTTP client instance.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function getInstance()
    {
        if (is_null($this->httpClient)) {
            $this->initialize();
        }

        return $this->httpClient;
    }

    /**
     * Get the request body for refresh token request.
     *
     * @param  Illuminate\Contracts\Auth\Authenticatable|\App\Extensions\ApiUserModel   $user
     * @return array
     */
    protected function getRefreshTokenBodyRequest($user)
    {
        $oldAccessToken = method_exists($user, 'getAccessToken') ? $user->getAccessToken() : null;
        $refreshToken = method_exists($user, 'getRefreshToken') ? $user->getRefreshToken() : null;

        return [
            'access_token' => $oldAccessToken,
            'refresh_token' => $refreshToken
        ];
    }

    /**
     * Refresh currently expired token with current user's
     * access token and refresh token.
     *
     * @return bool
     */
    protected function refreshToken()
    {
        $user = request()->user();

        if ($user) {
            $body = $this->getRefreshTokenBodyRequest($user);
        } else {
            // No user found. Terminate the session.
            return false;
        }

        $response = $this->getInstance()->post($this->refreshTokenUrl, $body);

        if ($response->failed()) {
            // Token did not refreshed, current session will be terminated
            return false;
        } else {
            $body = $response->json();

            if ($this->withoutWrapper) {
                $result = $body;
            } else {
                $result = $body[$this->wrapperKey];
            }

            // Store the new access token to the user repository
            $user->setAccessToken($result[$this->accessTokenName]);

            // If the result contain new refresh token, save it as well
            if (! is_null($result[$this->refreshTokenName])) {
                $user->setRefreshToken($result[$this->refreshTokenName]);
            }

            return true;
        }
    }

    /**
     * Terminate current session.
     *
     * @return void
     */
    protected function terminateSession()
    {
        $this->clearCachedUserData();
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    /**
     * Clear cached user data from storage.
     *
     * @return void
     */
    protected function clearCachedUserData()
    {
        $user = request()->user();

        if ($user) {
            $user->deleteUserData();
        }
    }

    /**
     * Check whether method is standard HTTP method.
     *
     * @param   string    $method   HTTP method
     * @return  bool
     */
    protected function isHttpMethod($method){
        $httpMethods = collect(['get', 'head', 'post', 'put', 'patch', 'delete']);

        return $httpMethods->contains(Str::lower($method));
    }
}
