<?php

namespace Guilty\Recman;

use GuzzleHttp\Client;
use Psr\SimpleCache\CacheInterface;

/**
 * Service for querying the Recman API with caching.
 *
 * Class CachedRecmanService
 * @package Guilty\Recman
 */
class CachedRecmanApi extends RecmanApi
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var bool If false, the cache will not be checked
     */
    protected $enableCache = true;

    /**
     * @var int|\DateInterval|null
     */
    protected $cacheExpire;

    /**
     * @var string String that will be prefixed when generating the cache key.
     */
    protected $cacheKeyPrefix;

    /**
     * @param string $apiKey Your Recman API Key
     * @param Client $client Http client to use when sending requests
     * @param CacheInterface $cache PSR-16 compatible cache implementation
     * @param string $cacheKeyPrefix the cache prefix to use as the cache key, if you don't know why you would need to change it, don't.
     * @param int|\DateInterval $cacheExpire the cache expire time, can be int (seconds) or DateInterval, set to null to disable caching-
     */
    public function __construct($apiKey, Client $client, CacheInterface $cache, $cacheExpire = 7200, $cacheKeyPrefix = "recman")
    {
        parent::__construct($apiKey, $client);

        $this->cache = $cache;
        $this->cacheExpire = $cacheExpire;
        $this->cacheKeyPrefix = $cacheKeyPrefix;
    }


    /**
     * Check if the cache value needs to be refreshed or has been disabled.
     *
     * @param mixed $value The value returned from the cache
     * @return bool Whether or not the value in the cache should be refreshed by calling the API
     */
    protected function shouldRefreshCachedValue($value)
    {
        // Checking for null because when the cached value
        // has expired, the retrieve method returns null.
        if (!$this->enableCache || $value == null) {
            return true;
        }

        return false;
    }

    /**
     * Fluent method for disabling the cache, until it is re-enabled.
     *
     * @return $this
     */
    public function disableCache()
    {
        $this->enableCache = false;
        return $this;
    }

    /**
     * Fluent method for enabling the cache
     *
     * @return $this
     */
    public function enableCache()
    {
        $this->enableCache = true;
        return $this;
    }

    /**
     * Generates a cache key from a backtrace
     *
     * @param array $backtrace result from calling debug_backtrace()
     * @return string the cache key.
     */
    protected function generateCacheKey($backtrace)
    {
        $argumentHash = md5(serialize($backtrace[1]["args"]));
        $callingMethod = $backtrace[1]["function"];

        return $this->cacheKeyPrefix . "_" . $callingMethod . "_" . $argumentHash;
    }

    public function performRequest($params = [])
    {
        // Generate a cache key.
        $cacheKey = $this->generateCacheKey(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2));

        // Fetch value from cache
        $response = $this->cache->get($cacheKey);

        // If the cache value is invalid, or the cache is disabled,
        // we fetch new data from the api and store that in the cache.
        if ($this->shouldRefreshCachedValue($response)) {

            $response = parent::performRequest($params);

            $this->cache->set($cacheKey, $response, $this->cacheExpire);
        }

        return $response;
    }
}