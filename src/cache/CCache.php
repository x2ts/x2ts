<?php

namespace x2ts\cache;

use x2ts\Component;
use x2ts\ComponentFactory as X;
use x2ts\IOException;
use x2ts\MethodNotImplementException;
use x2ts\Toolkit;

/**
 * Class CCache
 *
 * @package x2ts\cache
 */
class CCache extends Component implements ICache {
    protected static $_conf = array(
        'cacheDir' => '.',
    );

    public function init() {
        X::logger()->trace('CCache init');
        if (
            !@mkdir($this->conf['cacheDir'], 0777, true) &&
            !is_dir($this->conf['cacheDir'])
        ) {
            throw new IOException('Cannot create directory for CCache');
        }
    }

    private function key2file($key) {
        return $this->conf['cacheDir'] . DIRECTORY_SEPARATOR . rawurlencode($key) . '.php';
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get($key) {
        $file = $this->key2file($key);
        if (is_file($file)) {
            /** @noinspection PhpIncludeInspection */
            $r = require $file;
            if (!empty($r) && $key === $r['key'] && (0 === $r['expiration'] || time() <= $r['expiration'])) {
                X::logger()->trace("CCache hit $key");
                return $r['data'];
            }
        }
        X::logger()->trace("CCache miss $key");
        return false;
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @param int    $duration
     *
     * @return void
     * @throws \x2ts\UncompilableException
     */
    public function set($key, $value, $duration) {
        X::logger()->trace("CCache set $key expire in $duration");
        $file = $this->key2file($key);
        $content = array(
            'key'        => $key,
            'expiration' => $duration > 0 ? time() + $duration : 0,
            'data'       => $value,
        );
        $phpCode = '<?php return ' . Toolkit::compile($content) . ';';
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($file, true);
        }
        file_put_contents($file, $phpCode, LOCK_EX);
    }

    /**
     * @param string $key
     *
     * @return boolean
     */
    public function remove($key) {
        X::logger()->trace("CCache remove $key");
        $file = $this->key2file($key);
        if (is_file($file)) {
            unlink($file);
            return true;
        }
        return false;
    }

    /**
     * @throws MethodNotImplementException
     * @return void
     */
    public function flush() {
        $files = scandir($this->conf['cacheDir']);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $f = realpath($this->conf['cacheDir'] . DIRECTORY_SEPARATOR . $file);
            if (is_file($f)) {
                unlink($f);
            }
        }
    }
}
