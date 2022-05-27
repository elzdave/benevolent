<?php

namespace Elzdave\Benevolent;

use Illuminate\Support\Facades\Cache;

class CacheRepository
{
    protected $prefix;
    protected $ttl;

    public function __construct()
    {
        $this->prefix = config('benevolent.cache.prefix');
        $this->ttl = config('benevolent.cache.expiration_time');
    }

    public function storeUser($identifier, $data)
    {
        $key = $this->getKey($identifier);

        return Cache::put($key, $data, $this->ttl);
    }

    public function getUser($identifier)
    {
        $key = $this->getKey($identifier);

        return Cache::get($key);
    }

    public function deleteUser($identifier)
    {
        $key = $this->getKey($identifier);

        return Cache::forget($key);
    }

    protected function getKey($identifier)
    {
        return $this->prefix . ':' . $identifier;
    }
}
