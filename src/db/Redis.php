<?php
/**
 * xts
 * File: redis.php
 * User: TingSong-Syu <rek@rek.me>
 * Date: 2014-06-21
 * Time: 18:51
 */

namespace x2ts\db;

use Redis as PhpRedis;
use RedisException;
use x2ts\ExtensionNotLoadedException;
use x2ts\IComponent;
use x2ts\TConfig;
use x2ts\TGetterSetter;
use x2ts\Toolkit;


/**
 * Class Redis
 *
 * @property-read array $conf
 * @package x2ts\db
 */
class Redis extends PhpRedis implements IComponent {
    use TGetterSetter;
    use TConfig;

    protected static $_conf = [
        'host'           => 'localhost',
        'port'           => 6379,
        'timeout'        => 0,
        'persistent'     => false,
        'persistentHash' => 'redis',
        'database'       => 0,
        'auth'           => null,
        'keyPrefix'      => '',
    ];

    public function init() {
        if (!extension_loaded('redis')) {
            throw new ExtensionNotLoadedException('The x2ts\db\Redis required extension phpredis has not been loaded yet');
        }

        $conf = $this->conf;
        if ($conf['persistent']) {
            /** @noinspection PhpMethodParametersCountMismatchInspection */
            if (!$this->pconnect(
                $conf['host'],
                $conf['port'],
                $conf['timeout'],
                $conf['persistentHash']
            )
            ) {
                throw new RedisException("Cannot connect to Redis at {$conf['host']}:{$conf['port']}");
            }
        } else {
            if (!$this->connect($conf['host'], $conf['port'], $conf['timeout']))
                throw new RedisException("Cannot connect to Redis at {$conf['host']}:{$conf['port']}");
        }

        // Authenticate when needed
        if ($conf['auth'] && !$this->auth($conf['auth'])) {
            throw new RedisException("Redis auth failed at {$conf['host']}:{$conf['port']}");
        }
        if ($conf['database'] && !$this->select($conf['database'])) {
            throw new RedisException("Select Failed in Redis at {$conf['host']}:{$conf['port']}");
        }
        if ($conf['keyPrefix']) {
            $this->setOption(PhpRedis::OPT_PREFIX, $conf['keyPrefix']);
        }
    }

    /**
     * @return \Redis
     */
    public function getRedis() {
        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return void
     */
    public function __set($name, $value) {
        $setter = Toolkit::toCamelCase("set $name");
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } else {
            $this->$name = $value;
        }
    }

    protected $delayDelKeys = [];

    public function delayDel($keys, ...$keyn) {
        if (is_array($keys)) {
            foreach ($keys as $key) {
                $this->delayDelKeys[] = $key;
            }
        } else {
            $this->delayDelKeys[] = $keys;
        }
        foreach ($keyn as $key) {
            $this->delayDelKeys[] = $key;
        }
    }

    public function __destruct() {
        if (count($this->delayDelKeys)) {
            $this->del($this->delayDelKeys);
        }
    }
}
