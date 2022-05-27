<?php

namespace Elzdave\Benevolent\Tests;

use Elzdave\Benevolent\HttpClient as Http;
use Elzdave\Benevolent\Tests\TestCase;
use Illuminate\Support\Facades\Auth;

class OutboundHttpTest extends TestCase
{
    public $accessToken = 'valid-access-t0ken';

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
                'refresh_token' => 'valid-refresh-token',
                'token_schema' => 'Bearer',
                'userdata' => [
                    'id' => 1,
                    'username' => 'elzdave',
                    'email' => 'park@example.com',
                    'email_verified_at' => '1970-05-12T04:28:30.000000Z',
                    'first_name' => 'Shanon',
                    'last_name' => 'Feeney',
                    'access_level' => 1,
                    'created_at' => '1970-05-12T04:28:31.000000Z',
                    'updated_at' => '1970-05-12T04:28:31.000000Z'
                ]
            ]
        ];

        Http::fake([
            'benevolent.test/auth/signin' => Http::response($validLogin, 200)
        ]);

        Auth::attempt($credentials);
    }

    public $successfulResponse = [
        'status' => 'success',
        'code' => 200,
        'result' => [
            'data' => 'something'
        ]
    ];

    public $unauthenticatedResponse = [
        'message' => 'Unauthenticated.'
    ];

    public function test_unauthenticated_request_to_public_endpoint()
    {
        Http::fake([
            'benevolent.test/public' => Http::response($this->successfulResponse, 200)
        ]);

        $response = Http::get('benevolent.test/public');

        $this->assertTrue($response->ok());
    }
    
    public function test_authenticated_request_to_public_endpoint()
    {
        Http::fake([
            'benevolent.test/public' => Http::response($this->successfulResponse, 200)
        ]);

        $this->authenticate();

        $response = Http::useAuth()->get('benevolent.test/public');

        $this->assertAuthenticated('web');
        $this->assertTrue($response->ok());
    }

    public function test_unauthenticated_request_to_private_endpoint()
    {
        Http::fake([
            'benevolent.test/private' => Http::response($this->unauthenticatedResponse, 401)
        ]);

        $response = Http::get('benevolent.test/private');
        
        $this->assertTrue($response->unauthorized());
    }

    public function test_authenticated_request_to_private_endpoint()
    {
        $responseWithToken = [
            'status' => 'success',
            'code' => 200,
            'result' => [
                'token' => $this->accessToken
            ]
        ];

        Http::fake([
            'benevolent.test/private' => Http::response($responseWithToken, 200)
        ]);

        $this->authenticate();

        $response = Http::useAuth()->get('benevolent.test/private');

        $this->assertAuthenticated('web');
        $this->assertTrue($response->ok());
        $this->assertArrayHasKey('token', $response->json()['result']);
        $this->assertEquals($this->accessToken, $response->json()['result']['token']);
    }

    public function test_refresh_access_token()
    {
        $newAccessToken = 'new-access-tok3n';

        $refreshResponse = [
            'status' => 'success',
            'code' => 200,
            'result' => [
                config('benevolent.keys.access_token') => $newAccessToken,
                config('benevolent.keys.refresh_token') => null
            ]
        ];

        $responseWithToken = [
            'status' => 'success',
            'code' => 200,
            'result' => [
                'token' => $newAccessToken
            ]
        ];

        $this->authenticate();

        Http::fake([
            'benevolent.test/private' => Http::sequence()->push($this->unauthenticatedResponse, 401)
                                                         ->push($responseWithToken, 200),
            'benevolent.test/auth/refresh' => Http::response($refreshResponse, 200)
        ]);

        $response = Http::useAuth()->get('benevolent.test/private');
        $this->assertTrue($response->unauthorized());

        $response = Http::useAuth()->get('benevolent.test/private');

        $this->assertAuthenticated('web');
        $this->assertTrue($response->ok());
        $this->assertArrayHasKey('token', $response->json()['result']);
        $this->assertEquals($newAccessToken, $response->json()['result']['token']);
    }
}
