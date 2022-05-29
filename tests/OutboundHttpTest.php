<?php

namespace Elzdave\Benevolent\Tests;

use Elzdave\Benevolent\Http\Http;
use Elzdave\Benevolent\Tests\TestCase;
use Elzdave\Benevolent\UserModel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;

class OutboundHttpTest extends TestCase
{
    protected $accessToken = 'valid-access-token';
    protected $refreshToken = 'valid-refresh-token';
    protected $newAccessToken = 'refreshed-valid-access-token';
    protected $newRefreshToken = 'refreshed-valid-refresh-token';

    protected $unauthenticatedResponse = [
        'message' => 'Unauthenticated.'
    ];

    protected function authenticate()
    {
        $credentials = [
            'username' => 'elzdave',
            'password' => 'password',
        ];

        $validLogin = [
            'status' => 'success',
            'code' => 200,
            'result' => [
                'access_token' => $this->accessToken,
                'refresh_token' => $this->refreshToken,
                'token_schema' => 'Bearer',
                'userdata' => [
                    'id' => 1,
                    'username' => 'elzdave',
                    'email' => 'park@example.com',
                    'email_verified_at' => '1970-05-12T04:28:30.000000Z',
                    'first_name' => 'David',
                    'last_name' => 'Eleazar',
                    'access_level' => 1,
                    'created_at' => '1970-05-12T04:28:31.000000Z',
                    'updated_at' => '1970-05-12T04:28:31.000000Z'
                ]
            ]
        ];

        Http::fake([
            $this->baseUrl . '/auth/signin' => Http::response($validLogin, 200)
        ]);

        Auth::attempt($credentials);
    }

    protected function isAuthenticatedRequest(Request $request)
    {
        return $request->hasHeader('Authorization');
    }

    protected function getTokenFromAuthorizationHeader(Request $request)
    {
        $authorization = $request->header('Authorization');

        return $authorization[0];
    }

    protected function setupFakeApiServer()
    {
        Http::fake(function(Request $request) {
            $preparedResponse = [
                'status' => 'success',
                'code' => 200,
                'result' => [
                    'authenticated' => $this->isAuthenticatedRequest($request),
                    'token' => $this->isAuthenticatedRequest($request) ?
                               $this->getTokenFromAuthorizationHeader($request) :
                               null,
                ]
            ];

            switch ($request->url()) {
                case $this->baseUrl . '/public':
                    return Http::response($preparedResponse, 200);
                    break;

                case $this->baseUrl . '/private':
                    if ($this->isAuthenticatedRequest($request) &&
                        $this->getTokenFromAuthorizationHeader($request) === 'Bearer ' . $this->accessToken) {
                            return Http::response($preparedResponse, 200);
                    }
                    return Http::response($this->unauthenticatedResponse, 401);
                    break;
                
                case $this->baseUrl . '/private/needs-refresh':
                    if ($this->isAuthenticatedRequest($request) &&
                        $this->getTokenFromAuthorizationHeader($request) === 'Bearer ' . $this->newAccessToken) {
                            return Http::response($preparedResponse, 200);
                    }
                    return Http::response($this->unauthenticatedResponse, 401);
                    break;

                default:
                    // code ...
                    break;
            }
        });
    }

    protected function setupFakeRefreshServer($refreshAccessTokenOnly = true)
    {
        Http::fake(function(Request $request) use($refreshAccessTokenOnly) {
            $refreshAccessTokenResponse = [
                'status' => 'success',
                'code' => 200,
                'result' => [
                    config('benevolent.keys.access_token') => $this->newAccessToken,
                    config('benevolent.keys.refresh_token') => null,
                ]
            ];

            $refreshBothTokenResponse = [
                'status' => 'success',
                'code' => 200,
                'result' => [
                    config('benevolent.keys.access_token') => $this->newAccessToken,
                    config('benevolent.keys.refresh_token') => $this->newRefreshToken,
                ]
            ];
            

            switch ($request->url()) {
                case $this->baseUrl . '/auth/refresh':
                    return Http::response(
                        $refreshAccessTokenOnly ?
                        $refreshAccessTokenResponse :
                        $refreshBothTokenResponse, 200);
                    break;

                default:
                    // code ...
                    break;
            }
        });
    }

    public function test_unauthenticated_request_to_public_endpoint()
    {
        $this->setupFakeApiServer();

        $response = Http::get('/public');

        $this->assertGuest('web');
        $this->assertTrue($response->ok());

        $this->assertFalse($response->json('result.authenticated'));
        $this->assertNull($response->json('result.token'));
    }
    
    public function test_authenticated_request_to_public_endpoint()
    {
        $this->setupFakeApiServer();
        $this->authenticate();

        $response = Http::useAuth()->get('/public');

        $this->assertAuthenticated('web');
        $this->assertTrue($response->ok());

        $this->assertTrue($response->json('result.authenticated'));
        $this->assertEquals('Bearer ' . $this->accessToken, $response->json('result.token'));
    }

    public function test_unauthenticated_request_to_private_endpoint()
    {
        $this->setupFakeApiServer();

        $response = Http::get('/private');
        
        $this->assertGuest('web');
        $this->assertTrue($response->unauthorized());
    }

    public function test_authenticated_request_to_private_endpoint()
    {
        $this->setupFakeApiServer();
        $this->authenticate();

        $response = Http::useAuth()->get('/private');

        $this->assertAuthenticated('web');
        $this->assertTrue($response->ok());
        
        $this->assertTrue($response->json('result.authenticated'));
        $this->assertEquals('Bearer ' . $this->accessToken, $response->json('result.token'));
    }

    public function test_refresh_access_token_only()
    {
        $this->setupFakeApiServer();
        $this->setupFakeRefreshServer(true);

        $this->authenticate();

        $response = Http::useAuth()->get('/private/needs-refresh');
        $user = (new UserModel)->findById(Auth::id());

        $this->assertAuthenticated('web');
        $this->assertTrue($response->ok());
        
        $this->assertTrue($response->json('result.authenticated'));
        $this->assertEquals('Bearer ' . $this->newAccessToken, $response->json('result.token'));

        $this->assertEquals($this->newAccessToken, $user->getAccessToken());
        $this->assertEquals($this->refreshToken, $user->getRefreshToken());
    }

    public function test_refresh_access_token_and_refresh_token()
    {
        $this->setupFakeApiServer();
        $this->setupFakeRefreshServer(false);

        $this->authenticate();

        $response = Http::useAuth()->get('/private/needs-refresh');
        $user = (new UserModel)->findById(Auth::id());

        $this->assertAuthenticated('web');
        $this->assertTrue($response->ok());
        
        $this->assertTrue($response->json('result.authenticated'));
        $this->assertEquals('Bearer ' . $this->newAccessToken, $response->json('result.token'));

        $this->assertEquals($this->newAccessToken, $user->getAccessToken());
        $this->assertEquals($this->newRefreshToken, $user->getRefreshToken());
    }
}
