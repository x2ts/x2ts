<?php

namespace x2ts;

use InvalidArgumentException;
use Monolog\Logger as MonoLogger;
use ReflectionClass;
use stdClass;

define('X_LOG_DEBUG', MonoLogger::DEBUG);
define('X_LOG_INFO', MonoLogger::INFO);
define('X_LOG_NOTICE', MonoLogger::NOTICE);
define('X_LOG_WARNING', MonoLogger::WARNING);
define('X_LOG_ERROR', MonoLogger::ERROR);
define('X_LOG_CRITICAL', MonoLogger::CRITICAL);
define('X_LOG_ALERT', MonoLogger::ALERT);
define('X_LOG_EMERGENCY', MonoLogger::EMERGENCY);

defined('X_DEBUG') or define('X_DEBUG', true);
defined('X_LOG_LEVEL') or define('X_LOG_LEVEL', X_DEBUG ? X_LOG_DEBUG : X_LOG_NOTICE);
defined('X_RUNTIME_ROOT') or define('X_RUNTIME_ROOT', sys_get_temp_dir());

/**
 * Class ComponentFactory
 *
 * @method static event\Bus bus()
 * @method static Logger logger()
 * @method static cache\CCache cc()
 * @method static cache\ICache cache()
 * @method static db\IDataBase db()
 * @method static view\IView view()
 * @method static route\Router router()
 * @package x2ts
 */
abstract class ComponentFactory extends Component {
    /**
     * @var array
     */
    protected static $_conf = [
        'component' => [
            'bus'    => [
                'class'     => event\Bus::class,
                'singleton' => true,
                'conf'      => [],
            ],
            'logger' => [
                'class'     => Logger::class,
                'singleton' => true,
                'conf'      => [],
            ],
            'cc'     => [
                'class'     => cache\CCache::class,
                'singleton' => true,
                'conf'      => [],
            ],
            'cache'  => [
                'class'     => cache\MCache::class,
                'singleton' => true,
                'conf'      => [],
            ],
            'db'     => [
                'class'     => db\SQLite::class,
                'singleton' => true,
                'conf'      => [],
            ],
            'view'   => [
                'class'     => view\Simple::class,
                'singleton' => true,
                'conf'      => [],
            ],
        ],
    ];

    /**
     * @var stdClass
     */
    protected static $_confObject;

    /**
     * @var array
     */
    private static $_singletons = [];

    /**
     * @param array|string|null $conf
     *
     * @return array|stdClass
     */
    public static function conf($conf = null) {
        if (null !== $conf) {
            if (is_array($conf)) {
                Toolkit::override(static::$_conf, $conf);
                return static::$_conf;
            }

            if (is_string($conf)) {
                return static::$_conf[$conf] ?? null;
            }
        }
        if (!static::$_confObject instanceof stdClass) {
            /** @noinspection ReturnNullInspection */
            static::$_confObject = json_decode(json_encode(static::$_conf));
        }
        return static::$_confObject;
    }

    /**
     * @param $componentId
     *
     * @return bool|IComponent
     * @throws \InvalidArgumentException
     * @throws \x2ts\ComponentNotFoundException
     */
    public static function getComponent($componentId) {
        /** @noinspection ImplicitMagicMethodCallInspection */
        return self::__callStatic($componentId, array());
    }

    /**
     * @param string $name
     * @param array  $args
     *
     * @return \x2ts\IComponent
     * @throws \InvalidArgumentException
     * @throws \x2ts\ComponentNotFoundException
     */
    public static function __callStatic($name, $args) {
        if (isset(self::$_conf['component'][$name])) {
            $desc = self::$_conf['component'][$name];
            if ($desc['singleton'] === false) {
                return self::getInstance($desc['class'], $args, $desc['conf'], $name);
            }
            if (!isset(self::$_singletons[$name])) {
                self::$_singletons[$name] = self::getInstance($desc['class'], $args, $desc['conf'], $name);
            } elseif (method_exists(self::$_singletons[$name], '__reconstruct')) {
                self::$_singletons[$name]->__reconstruct(...$args);
            }

            return self::$_singletons[$name];
        }
        throw new ComponentNotFoundException("The specified component $name cannot be found in configurations");
    }

    /** @noinspection MoreThanThreeArgumentsInspection
     * @param string $class
     * @param array  $args
     * @param array  $conf
     * @param string $confHash
     *
     * @return IComponent
     */
    public static function getInstance($class, array $args, array $conf, string $confHash) {
        $rfClass = new ReflectionClass($class);
        if (!$rfClass->implementsInterface(IComponent::class)) {
            throw new InvalidArgumentException("class $class is not an instance of x2ts\\IComponent");
        }
        if ($rfClass->hasMethod('getInstance')) {
            return $rfClass->getMethod('getInstance')
                ->invoke(null, $args, $conf, $confHash);
        }
        /** @var IComponent $component */
        $component = $rfClass->newInstanceArgs($args);
        $rfClass->getMethod('saveConf')->invoke($component, $conf, $confHash);
        if ($rfClass->hasMethod('init')) {
            $rfClass->getMethod('init')->invoke($component);
        }
        return $component;
    }

    public static function log($msg, $level = X_LOG_DEBUG) {
        static::logger()->log($msg, $level, 2);
    }

    public static function trace($msg) {
        static::logger()->log($msg, X_LOG_DEBUG, 2);
    }
}
