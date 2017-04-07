<?php

namespace x2ts\cache;

use Memcache;
use x2ts\Component;
use x2ts\Toolkit;

/**
 * Class MCache
 *
 * @package x2ts
 * @property-read Memcache $cache
 */
class MCache extends Component implements ICache {
    protected static $_conf = array(
        'host'       => 'localhost',
        'port'       => 11211,
        'persistent' => false,
        'keyPrefix'  => '',
    );

    /**
     * @var Memcache
     */
    protected $_cache;

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get($key) {
        $key = $this->conf['keyPrefix'] . $key;
        $s = $this->cache->get($key);
        if ($s === false) {
            Toolkit::trace("MCache miss $key");
            return false;
        }

        Toolkit::trace("MCache hit $key");
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
    public function set($key, $value, $duration) {
        $key = $this->conf['keyPrefix'] . $key;
        Toolkit::trace("MCache set $key");
        $s = is_numeric($value) ? $value : serialize($value);
        $this->cache->set($key, $s, 0, $duration);
    }

    /**
     * @param string $key
     *
     * @return boolean
     */
    public function remove($key) {
        $key = $this->conf['keyPrefix'] . $key;
        Toolkit::trace("MCache remove $key");
        return $this->cache->delete($key);
    }

    /**
     * @return void
     */
    public function flush() {
        Toolkit::trace('MCache flush');
        $this->cache->flush();
    }

    /**
     * @param string $key
     * @param int    $step
     *
     * @return int
     */
    public function inc($key, $step = 1) {
        $key = $this->conf['keyPrefix'] . $key;
        Toolkit::trace("MCache inc $key by $step");
        return $this->cache->increment($key, $step);
    }

    /**
     * @param string $key
     * @param int    $step
     *
     * @return int
     */
    public function dec($key, $step = 1) {
        $key = $this->conf['keyPrefix'] . $key;
        Toolkit::trace("MCache dec $key by $step");
        return $this->cache->decrement($key, $step);
    }

    /**
     * @return Memcache
     */
    public function getCache() {
        if (!$this->_cache instanceof Memcache) {
            Toolkit::trace('MCache init');
            $this->_cache = new Memcache();
            $conf = $this->conf;
            if (static::$_conf['persistent']) {
                $this->_cache->pconnect($conf['host'], $conf['port']);
            } else {
                $this->_cache->connect($conf['host'], $conf['port']);
            }
        }
        return $this->_cache;
    }
}
