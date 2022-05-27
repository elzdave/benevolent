<?php

namespace Elzdave\Benevolent\Http;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Client\PendingRequest as BasePendingRequest;

class PendingRequest extends BasePendingRequest
{
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

    public function __construct(Factory $factory = null)
    {
        parent::__construct($factory);

        $this->loadConfig();
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

    /**
     * Enable authenticated URL endpoint access support.
     *
     * @param  string   $token      The access token to override the current user's access token. Default is null.
     * @param  string   $schema     The token schema. Default is 'Bearer'
     * @return \Elzdave\Benevolent\HttpClient
     */
    public function useAuth($token = null, $schema = 'Bearer')
    {
        $user = request()->user();

        if ($user) {
            $userToken = $token ?? (method_exists($user, 'getAccessToken') ? $user->getAccessToken() : null);
            $userSchema = method_exists($user, 'getTokenSchema') ? $user->getTokenSchema() : $schema;

            $this->isAuthenticatedRequest = true;
            $this->withToken($userToken, $userSchema);
        } else {
            // The current session is unauthenticated.
            $this->isAuthenticatedRequest = false;
        }

        return $this;
    }

    /**
     * Send the request to the given URL.
     *
     * @param  string  $method
     * @param  string  $url
     * @param  array  $options
     * @return \Illuminate\Http\Client\Response
     *
     * @throws \Exception
     */
    public function send(string $method, string $url, array $options = [])
    {
        $response = parent::send($method, $url, $options);

        if ($response->unauthorized() && $this->isAuthenticatedRequest) {
            $isTokenRefreshed = $this->enableRefreshToken ? $this->refreshToken() : false;

            // Token didn't refreshed. Will terminate the session...
            if (! $isTokenRefreshed) {
                $this->terminateSession();

                return response()->redirectToRoute('login');
            } else {
                // Retrying request...
                return parent::send($method, $url, $options);
            }
        }

        return $response;
    }

    /**
     * Get the request body for refresh token request.
     *
     * @param  Illuminate\Contracts\Auth\Authenticatable|\Elzdave\Benevolent\ApiUserModel   $user
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

        $response = parent::post($this->refreshTokenUrl, $body);

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

            // Renew the token in the object memory
            $this->withToken($user->getAccessToken(), $user->getTokenSchema());

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
}
