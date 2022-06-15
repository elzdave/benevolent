<?php

namespace Elzdave\Benevolent\Tests;

use Elzdave\Benevolent\Http\Http;
use Elzdave\Benevolent\Tests\TestCase;
use Elzdave\Benevolent\UserModel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class AuthenticationTest extends TestCase
{
    protected $credentials = [
        'miata' => [
            'id' => 1,
            'password' => 'mi474_rx5',
            'access_token' => 'bWlhdGE6bWk0NzRfcng1', // base64_encode('miata:mi474_rx5'),
            'refresh_token' => 'cmVmcmVzaDptaWF0YTptaTQ3NF9yeDU=', // base64_encode('refresh:miata:mi474_rx5'),
            'email' => 'miata@benevolent.test',
            'first_name' => 'Mazda',
            'last_name' => 'Miata',
            'access_level' => 1,
        ],
        'continental.gt' => [
            'id' => 2,
            'password' => 'b3ntl3yr0cks',
            'access_token' => 'Y29udGluZW50YWwuZ3Q6YjNudGwzeXIwY2tz', // base64_encode('continental.gt:b3ntl3yr0cks'),
            'refresh_token' => 'cmVmcmVzaDpjb250aW5lbnRhbC5ndDpiM250bDN5cjBja3M=', // base64_encode('refresh:continental.gt:b3ntl3yr0cks'),
            'email' => 'continental@benevolent.test',
            'first_name' => 'Bentley',
            'last_name' => 'Continental',
            'access_level' => 2,
        ],
        'zonda99' => [
            'id' => 3,
            'password' => 'p4g4n1z0nd4',
            'access_token' => 'em9uZGE5OTpwNGc0bjF6MG5kNA==', // base64_encode('zonda99:p4g4n1z0nd4'),
            'refresh_token' => 'InJlZnJlc2g6em9uZGE5OTpwNGc0bjF6MG5kNA==', // base64_encode('refresh:zonda99:p4g4n1z0nd4'),
            'email' => 'zonda99@benevolent.test',
            'first_name' => 'Pagani',
            'last_name' => 'Zonda',
            'access_level' => 3,
        ],
    ];

    protected function authenticateUser(Request $request)
    {

        $username = Arr::has($request->data(), 'username') ? ($request->data())['username'] : '__null__';
        $password = Arr::has($request->data(), 'password') ? ($request->data())['password'] : '__null__';

        $credential = Arr::has($this->credentials, $username) ? $this->credentials[$username] : false;

        if ($credential && $password === $credential['password']) {
            return [
                'status' => 'success',
                'code' => 200,
                config('benevolent.keys.wrapper') => [
                    config('benevolent.keys.access_token') => $credential['access_token'],
                    config('benevolent.keys.refresh_token') => $credential['refresh_token'],
                    config('benevolent.keys.token_schema') => 'Bearer',
                    config('benevolent.keys.user_data') => [
                        'id' => $credential['id'],
                        'username' => $username,
                        'email' => $credential['email'],
                        'email_verified_at' => '1970-05-12T04:28:30.000000Z',
                        'first_name' => $credential['first_name'],
                        'last_name' => $credential['last_name'],
                        'access_level' => $credential['access_level'],
                        'created_at' => '1970-05-12T04:28:31.000000Z',
                        'updated_at' => '1970-05-12T04:28:31.000000Z'
                    ]
                ]
            ];
        }

        return [
            'status' => 'error',
            'code' => 401,
            config('benevolent.keys.wrapper') => null
        ];
    }

    protected function setupFakeApiServer()
    {
        Http::fake(function(Request $request) {
            switch ($request->url()) {
                case $this->baseUrl . '/auth/signin':
                    $response = $this->authenticateUser($request);
                    return Http::response($response, $response['code']);
                    break;

                default:
                    // code ...
                    break;
            }
        });
    }

    public function test_login_using_invalid_data_key()
    {
        $invalidCredentials = [
            '__username' => 'miata',
            '__password' => 'mi474_rx5'
        ];

        $this->setupFakeApiServer();

        Auth::attempt($invalidCredentials);

        $user = (new UserModel)->findById(Auth::id());

        $this->assertNull($user);
        $this->assertGuest('web');
    }

    public function test_login_using_missing_data_key()
    {
        $credentials = [
            [
                // missing username
                'password' => 'mi474_rx5'
            ],
            [
                'username' => 'miata',
                // missing password
            ],
        ];

        $invalidCredentials = Arr::random($credentials);

        $this->setupFakeApiServer();

        Auth::attempt($invalidCredentials);

        $user = (new UserModel)->findById(Auth::id());

        $this->assertNull($user);
        $this->assertGuest('web');
    }

    public function test_login_using_invalid_and_missing_data_key()
    {
        $credentials = [
            [
                // missing username
                '__password' => 'mi474_rx5'
            ],
            [
                '__username' => 'miata',
                // missing password
            ],
        ];

        $invalidCredentials = Arr::random($credentials);

        $this->setupFakeApiServer();

        Auth::attempt($invalidCredentials);

        $user = (new UserModel)->findById(Auth::id());

        $this->assertNull($user);
        $this->assertGuest('web');
    }

    public function test_login_using_wrong_data()
    {
        $credentials = [
            [
                'username' => 'miata',
                'password' => 'p4g4n1z0nd4'
            ],
            [
                'username' => 'continental.gt',
                'password' => 'mi474_rx5'
            ],
            [
                'username' => 'zonda99',
                'password' => 'b3ntl3yr0cks'
            ],
        ];

        $invalidCredentials = Arr::random($credentials);

        $this->setupFakeApiServer();

        Auth::attempt($invalidCredentials);
        $user = (new UserModel)->findById(Auth::id());

        $this->assertNull($user);
        $this->assertGuest('web');
    }

    public function test_login_using_valid_data_without_remember()
    {
        $credentials = [
            [
                'username' => 'miata',
                'password' => 'mi474_rx5'
            ],
            [
                'username' => 'continental.gt',
                'password' => 'b3ntl3yr0cks'
            ],
            [
                'username' => 'zonda99',
                'password' => 'p4g4n1z0nd4'
            ],
        ];

        $attemptedCredential = Arr::random($credentials);
        $validCredential = $this->credentials[$attemptedCredential['username']];

        $this->assertGuest('web');
        
        $this->setupFakeApiServer();

        // attempt login without remember
        Auth::attempt($attemptedCredential, false);

        $user = (new UserModel)->findById(Auth::id());

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertObjectHasAttribute('token', $user);
        $this->assertEquals($validCredential['access_token'], $user->getAccessToken());
        $this->assertNull($user->getRememberToken());
    }

    public function test_login_using_valid_data_with_remember()
    {
        $credentials = [
            [
                'username' => 'miata',
                'password' => 'mi474_rx5'
            ],
            [
                'username' => 'continental.gt',
                'password' => 'b3ntl3yr0cks'
            ],
            [
                'username' => 'zonda99',
                'password' => 'p4g4n1z0nd4'
            ],
        ];

        $attemptedCredential = Arr::random($credentials);
        $validCredential = $this->credentials[$attemptedCredential['username']];

        $this->assertGuest('web');
        
        $this->setupFakeApiServer();

        // attempt login with remember
        Auth::attempt($attemptedCredential, true);

        $user = (new UserModel)->findById(Auth::id());

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertObjectHasAttribute('token', $user);
        $this->assertEquals($validCredential['access_token'], $user->getAccessToken());
        $this->assertNotNull($user->getRememberToken());
    }    

    public function test_logout()
    {
        $credentials = [
            [
                'username' => 'miata',
                'password' => 'mi474_rx5'
            ],
            [
                'username' => 'continental.gt',
                'password' => 'b3ntl3yr0cks'
            ],
            [
                'username' => 'zonda99',
                'password' => 'p4g4n1z0nd4'
            ],
        ];

        $attemptedCredential = Arr::random($credentials);
        $validCredential = $this->credentials[$attemptedCredential['username']];

        $this->assertGuest('web');
        
        $this->setupFakeApiServer();

        // attempt login without remember
        Auth::attempt($attemptedCredential, false);

        $user = (new UserModel)->findById(Auth::id());

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertObjectHasAttribute('token', $user);
        $this->assertEquals($validCredential['access_token'], $user->getAccessToken());
        $this->assertNull($user->getRememberToken());

        // then logout
        Auth::logout();
        
        $user = (new UserModel)->findById(Auth::id());
        
        $this->assertGuest('web');
        $this->assertNull($user);
    }
}
