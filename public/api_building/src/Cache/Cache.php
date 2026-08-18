<?php
namespace Src\Cache;

define('DIR_CACHE', $_SERVER["DOCUMENT_ROOT"].'/resources/cache/');

use Src\Cache\MemCache as MemCache;



class Cache {
	private $method;
	private $expire;
	private $cache;

	public function __construct($method= 'FILE', $expire = 3600) {
		$this->method = $method;
		$this->expire = $expire;

		switch ($this->method) {
            case "MEMCACHE":
                $this->cache  = new MemCache($expire);
                break;
            default:
                break;
        }
	}

	public function get($key) {
		return $this->cache->get($key);
	}

	public function set($key, $value) {
		$this->cache->set($key, $value);
	}

	public function delete($key) {
	    $this->cache->delete($key);
	}
}