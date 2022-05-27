<?php

namespace Elzdave\Benevolent;

use Elzdave\Benevolent\HttpClient;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class UserModel implements AuthenticatableContract
{
    /**
     * The user repository.
     *
     * @var \App\Extensions\CacheRepository
     */
    protected $repository;

    /**
     * Indicates whether the user model has been initialized.
     */
    protected $initialized = false;

    /**
     * The column name of the 'remember me' token.
     *
     * @var string
     */
    protected $rememberTokenName = 'remember_token';

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
     * The token schema key name.
     *
     * @var string
     */
    protected $tokenSchemaName = 'token_schema';

    /**
     * The user data key name.
     *
     * @var string
     */
    protected $userDataKeyName = 'userdata';

    /**
     * The session user ID key prefix.
     *
     * @var string
     */
    protected $sessionUserIdKeyPrefix = 'session_user_';

    /**
     * The login path.
     *
     * @var string
     */
    protected $loginPath = '';

    public function __construct()
    {
        $this->initCacheRepository();

        if (! $this->initialized) {
            $this->initUser();
        }

        $this->loadConfig();
    }

    protected function loadConfig()
    {
        $this->enableRefreshToken = config('benevolent.features.enable_refresh_token');
        $this->withoutWrapper = config('benevolent.features.without_wrapper');
        $this->wrapperKey = config('benevolent.keys.wrapper');
        $this->accessTokenName = config('benevolent.keys.access_token');
        $this->refreshTokenName = config('benevolent.keys.refresh_token');
        $this->tokenSchemaName = config('benevolent.keys.token_schema');
        $this->userDataKeyName = config('benevolent.keys.user_data');
        $this->loginPath = config('benevolent.paths.login');
    }

    /**
     * Initialize the user cache repository.
     *
     * @return void
     */
    public function initCacheRepository()
    {
        if (! $this->repository) {
            $this->repository = new CacheRepository;
        }
    }

    /**
     * Initialize the user field with data from repository.
     *
     * @return void
     */
    public function initUser()
    {
        $userData = $this->repository->getUser($this->getAuthIdentifier());

        if ($userData) {
            foreach ($userData as $key => $value) {
                $this->{$key} = $value;
            }
        }

        $this->initialized = true;
    }

    /**
     * Render the data as array for serialization.
     *
     * @return array
     */
    public function toArray()
    {
        if (! $this->initialized) {
            $this->initUser();
        }

        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->first_name . ' ' . $this->last_name,
            'access_level' => $this->access_level,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Convert the model to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toArray();
    }

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return Session::get($this->sessionUserIdKeyPrefix . $this->getAuthIdentifierName());
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        $currentUser = $this->repository->getUser($this->getAuthIdentifier());

        return $currentUser ? $currentUser['password'] : null;
    }

    /**
     * Get the token value for the 'remember me' session.
     *
     * @return string|null
     */
    public function getRememberToken()
    {
        $currentUser = $this->repository->getUser($this->getAuthIdentifier());

        return $currentUser ? $currentUser[$this->getRememberTokenName()] : null;
    }

    /**
     * Set the token value for the 'remember me' session.
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        $currentUser = $this->repository->getUser($this->getAuthIdentifier());

        if ($currentUser) {
            $currentUser[$this->getRememberTokenName()] = $value;

            // then store the modified data to the repository
            $this->repository->storeUser($this->getAuthIdentifier(), $currentUser);
        }
    }

    /**
     * Get the key name for the 'remember me' token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return $this->rememberTokenName;
    }

    /**
     * Find the user by its unique identifier.
     */
    public function findById($identifier)
    {
        $user = $this->repository->getUser($identifier);

        // store the identifier into the session
        Session::put(($this->sessionUserIdKeyPrefix . $this->getAuthIdentifierName()), $identifier);

        return $user ? $this : null;
    }

    /**
     * Fetch user by the given credentials.
     *
     * @param array $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public function fetchUserByCredentials(array $credentials)
    {
        // make attempt to login to backend
        $response = HttpClient::post($this->loginPath, $credentials);

        if ($response->ok()) {
            // populate object attributes
            $body = $response->json();

            if ($this->withoutWrapper) {
                $result = $body;
            } else {
                $result = $body[$this->wrapperKey];
            }

            // transform user data into array
            $user = array_merge_recursive($result[$this->userDataKeyName], [
                'password' => Hash::make($credentials['password']),
                'token' => [
                    'access_token' => $result[$this->accessTokenName],
                    'refresh_token' => $this->enableRefreshToken ? $result[$this->refreshTokenName] : null,
                    'token_schema' => $result[$this->tokenSchemaName],
                ],
                'remember_token' => null,   // will default to null
            ]);

            // store the user data into the user cache repository
            $this->repository->storeUser($user[$this->getAuthIdentifierName()], $user);

            // and store the current user's identifier into the session
            Session::put(($this->sessionUserIdKeyPrefix . $this->getAuthIdentifierName()), $user[$this->getAuthIdentifierName()]);

            break;
        }

        return $this;
    }

    /**
     * Destroy user data from cache repository.
     *
     * @return bool
     */
    public function deleteUserData()
    {
        $identifier = $this->getAuthIdentifier();

        // Delete the 'session_user_id' from session storage
        Session::forget($this->sessionUserIdKeyPrefix . $this->getAuthIdentifierName());

        // And delete the entire cached user data
        return $this->repository->deleteUser($identifier);
    }

    /**
     * Get current user's access token.
     *
     * @return string|null
     */
    public function getAccessToken()
    {
        $currentUser = $this->repository->getUser($this->getAuthIdentifier());

        return $currentUser ? $currentUser['token']['access_token'] : null;
    }

    /**
     * Set current user's access token.
     *
     * @return void
     */
    public function setAccessToken($accessToken)
    {
        $currentUser = $this->repository->getUser($this->getAuthIdentifier());

        if ($currentUser) {
            $currentUser['token']['access_token'] = $accessToken;

            $this->repository->storeUser($this->getAuthIdentifier(), $currentUser);
        }
    }

    /**
     * Get current user's refresh token.
     *
     * @return string|null
     */
    public function getRefreshToken()
    {
        $currentUser = $this->repository->getUser($this->getAuthIdentifier());

        return $currentUser ? $currentUser['token']['refresh_token'] : null;
    }

    /**
     * Set current user's refresh token.
     *
     * @return void
     */
    public function setRefreshToken($refreshToken)
    {
        $currentUser = $this->repository->getUser($this->getAuthIdentifier());

        if ($currentUser) {
            $currentUser['token']['refresh_token'] = $refreshToken;

            $this->repository->storeUser($this->getAuthIdentifier(), $currentUser);
        }
    }

    /**
     * Get current user's token scheme.
     *
     * @return string|null
     */
    public function getTokenSchema()
    {
        $currentUser = $this->repository->getUser($this->getAuthIdentifier());

        return $currentUser ? $currentUser['token']['token_schema'] : null;
    }
}
