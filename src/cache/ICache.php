<?php

namespace x2ts\cache;

/**
 * Class ICache
 * @package xts
 */
interface ICache {
    /**
     * @param string $key
     * @return mixed
     */
    public function get($key);

    /**
     * @param string $key
     * @param mixed $value
     * @param int $duration
     * @return void
     */
    public function set($key, $value, $duration);

    /**
     * @param string $key
     * @return boolean
     */
    public function remove($key);

    /**
     * @return void
     */
    public function flush();
}
