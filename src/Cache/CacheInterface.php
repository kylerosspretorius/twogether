<?php

namespace App\Cache;

interface CacheInterface
{
    /**
     * CacheInterface constructor.

     * @param int $ttl
     */
    public function __construct(int $ttl);

    /**
     * @return mixed
     */
    public function setCache($key, $value);

    /**
     * @return mixed
     */
    public function setTtl($ttl);

    /**
     * @return mixed
     */
    public function getByKey($key);

}