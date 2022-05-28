<?php

namespace Elzdave\Benevolent\Tests;

use Elzdave\Benevolent\Http\Http;
use Elzdave\Benevolent\Tests\TestCase;
use Elzdave\Benevolent\UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AuthenticationTest extends TestCase
{
    public $credentials = [
        'username' => 'elzdave',
        'password' => 'password',
    ];

    public $validLogin = [
        'status' => 'success',
        'code' => 200,
        'result' => [
            'access_token' => 'valid-access-t0ken',
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

    public $invalidLogin = [
        'status' => 'error',
        'code' => 401,
        'result' => null
    ];

    public function test_login_using_valid_data_without_remember()
    {
        Cache::flush();

        $this->assertGuest('web');

        Http::fake([
            $this->baseUrl . '/*' => Http::response($this->validLogin, 200)
        ]);

        // attempt login without remember
        Auth::attempt($this->credentials, false);

        $model = new UserModel;
        $user = $model->findById(Auth::id());

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertObjectHasAttribute('token', $user);
        $this->assertEquals($this->validLogin['result']['access_token'], $user->getAccessToken());
        $this->assertNull($user->getRememberToken());
    }

    public function test_login_using_valid_data_with_remember()
    {
        $this->assertGuest('web');

        Http::fake([
            $this->baseUrl . '/*' => Http::response($this->validLogin, 200)
        ]);

        // attempt login with remember
        Auth::attempt($this->credentials, true);

        $model = new UserModel;
        $user = $model->findById(Auth::id());

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertObjectHasAttribute('token', $user);
        $this->assertEquals($this->validLogin['result']['access_token'], $user->getAccessToken());
        $this->assertNotNull($user->getRememberToken());
    }

    public function test_login_using_invalid_data()
    {
        Cache::flush();

        Http::fake([
            $this->baseUrl . '/*' => Http::response($this->invalidLogin, 401)
        ]);

        Auth::attempt($this->credentials);

        $model = new UserModel;
        $user = $model->findById(Auth::id());

        $this->assertNull($user);
        $this->assertGuest('web');
    }
}
