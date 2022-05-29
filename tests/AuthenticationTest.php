<?php

namespace Elzdave\Benevolent\Tests;

use Elzdave\Benevolent\Http\Http;
use Elzdave\Benevolent\Tests\TestCase;
use Elzdave\Benevolent\UserModel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticationTest extends TestCase
{
    protected $credentials = [
        'username' => 'elzdave',
        'password' => 'password',
    ];

    protected $validLogin = [
        'status' => 'success',
        'code' => 200,
        'result' => [
            'access_token' => 'valid-access-token',
            'refresh_token' => 'valid-refresh-token',
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

    protected $invalidLogin = [
        'status' => 'error',
        'code' => 401,
        'result' => null
    ];

    public function test_login_using_invalid_data()
    {
        Http::fake([
            $this->baseUrl . '/*' => Http::response($this->invalidLogin, 401)
        ]);

        Auth::attempt($this->credentials);

        $user = (new UserModel)->findById(Auth::id());

        $this->assertNull($user);
        $this->assertGuest('web');
    }

    public function test_login_using_valid_data_without_remember()
    {
        $this->assertGuest('web');

        Http::fake([
            $this->baseUrl . '/*' => Http::response($this->validLogin, 200)
        ]);

        // attempt login without remember
        Auth::attempt($this->credentials, false);

        $user = (new UserModel)->findById(Auth::id());

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

        $user = (new UserModel)->findById(Auth::id());

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertObjectHasAttribute('token', $user);
        $this->assertEquals($this->validLogin['result']['access_token'], $user->getAccessToken());
        $this->assertNotNull($user->getRememberToken());
    }    

    public function test_logout()
    {
        $this->assertGuest('web');

        Http::fake([
            $this->baseUrl . '/*' => Http::response($this->validLogin, 200)
        ]);

        // attempt login without remember
        Auth::attempt($this->credentials, false);

        $user = (new UserModel)->findById(Auth::id());

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertObjectHasAttribute('token', $user);
        $this->assertEquals($this->validLogin['result']['access_token'], $user->getAccessToken());
        $this->assertNull($user->getRememberToken());

        // then logout
        Auth::logout();
        
        $user = (new UserModel)->findById(Auth::id());
        
        $this->assertGuest('web');
        $this->assertNull($user);
    }
}
