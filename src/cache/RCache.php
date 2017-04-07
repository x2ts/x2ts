<?php

namespace x2ts\cache;

use Redis;
use x2ts\Component;
use x2ts\Toolkit;

/**
 * Class RCache
 *
 * @package x2ts
 * @property-read Redis $cache
 */
class RCache extends Component implements ICache {
    protected static $_conf = array(
        'host'           => 'localhost',
        'port'           => 6379, //int, 6379 by default
        'timeout'        => 0, //float, value in seconds, default is 0 meaning unlimited
        'persistent'     => false, //bool, false by default
        'persistentHash' => 'rcache',//identity for the requested persistent connection
        'database'       => 0, //number, 0 by default
        'auth'           => null, //string, null by default
        'keyPrefix'      => '',
    );

    /**
     * @var Redis $_cache
     */
    protected $_cache;

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get($key) {
        $s = $this->cache->get($key);
        if ($s === false) {
            Toolkit::trace("RCache Miss '$key'");
            return false;
        }

        Toolkit::trace("RCache Hit '$key'");
        /** @noinspection UnserializeExploitsInspection */
        return is_numeric($s) ? $s : unserialize($s);
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @param int    $duration
     *
     * @return void
     */
    public function set($key, $value, $duration = 0) {
        Toolkit::trace("RCache Set $key");
        $s = is_numeric($value) ? $value : serialize($value);
        if ($duration) {
            $this->cache->set($key, $s, $duration);
        } else {
            $this->cache->set($key, $s);
        }
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function remove($key) {
        Toolkit::trace("RCache remove '$key'");
        $this->cache->delete($key);
    }

    /**
     * @return void
     */
    public function flush() {
        Toolkit::trace('RCache flush');
        $this->cache->flushDB();
    }

    /**
     * @param string $key
     * @param int    $step
     *
     * @return int
     */
    public function inc($key, $step = 1) {
        Toolkit::trace("RCache inc '$key' by $step");
        if ($step > 1) {
            return $this->cache->incrBy($key, $step);
        }

        return $this->cache->incr($key);
    }

    /**
     * @param string $key
     * @param int    $step
     *
     * @return int
     */
    public function dec($key, $step = 1) {
        Toolkit::trace("RCache dec '$key' by $step");
        if ($step > 1) {
            return $this->cache->decrBy($key, $step);
        }

        return $this->cache->decr($key);
    }

    /**
     * @return Redis
     */
    public function getCache() {
        if (!$this->_cache instanceof Redis) {
            Toolkit::trace('RCache init');
            $this->_cache = new Redis();
            $conf = $this->conf;
            if ($conf['persistent']) {
                /** @noinspection PhpMethodParametersCountMismatchInspection */
                $this->_cache->pconnect(
                    $conf['host'],
                    $conf['port'],
                    $conf['timeout'],
                    $conf['persistentHash']
                );
            } else {
                $this->_cache->connect($conf['host'], $conf['port'], $conf['timeout']);
            }
            if ($conf['auth']) {
                $this->_cache->auth($conf['auth']);
            }
            if ($conf['database']) {
                $this->_cache->select($conf['database']);
            }
            if ($conf['keyPrefix']) {
                $this->_cache->setOption(Redis::OPT_PREFIX, $conf['keyPrefix']);
            }
        }
        return $this->_cache;
    }
}
