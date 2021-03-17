<?php

namespace App\Cache;


use Predis\Client;

class CacheClient implements CacheInterface
{

    /**
     * @var string
     */
    private $cacheKey;
    /**
     * @var int
     */
    private $ttl;

    /** @var int */
    private $prefix;

    protected $redis;

    CONST DEFAULT_TTL = 86400; //24hours

    /**
     * CacheClient constructor.
     * @param int $ttl
     */
    public function __construct(int $ttl)
    {
        $this->redis = new Client([
            'scheme' => 'tcp',
            'host'   => 'redis',
            'port'   => 6379,
        ]);

        $this->ttl = !isset($ttl) ? self::DEFAULT_TTL : $ttl;
    }

    public function setKeyPrefix($prefix) {
        $this->prefix = $prefix;
    }

    /**
     * @return mixed
     */
    public function setCache($key, $value)
    {
        if (!$this->redis->exists($key)) {
            $this->redis->set($key, $value);
        }
        return $this;
    }


    /**
     * @param int $ttl
     * @return $this
     */
    public function setTtl($ttl)
    {
        $this->ttl = $ttl;
        return $this;
    }


    /**
     * @return mixed
     */
    public function getByKey($key) {

        if ($this->redis->exists($key))
        {
           return $this->redis->get($key);
        }
        return null;
    }

    /**
     * @return mixed
     */
    public function getKeyTTL($key) {
        $this->cacheKey = $this->prefix . $key;
        if ($this->redis->exists($this->cacheKey))
        {
            return $this->redis->ttl($this->cacheKey);
        }
        return null;
    }

    /**
     * Clear out the cache key and remove from object
     */
    public function clearKey($key)
    {
       $this->cacheKey = $this->prefix . $key;
       if($this->redis->exists($this->cacheKey))
       {
           $this->redis->del($this->cacheKey);
       }

       return $this;
    }

}