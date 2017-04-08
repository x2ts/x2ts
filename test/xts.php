<?php

namespace x2ts;

define('X_PROJECT_ROOT', __DIR__);
define('X_RUNTIME_ROOT', __DIR__ . '/runtime');
define('X_DEBUG', true);
define('X2ROOT', dirname(__DIR__));

ini_set('display_errors', X_DEBUG ? 'On' : 'Off');

require_once X2ROOT . '/vendor/autoload.php';

use Monolog\Handler\StreamHandler;

/**
 * Class T
 *
 * @method static validator\Validator validator($data)
 * @package x2ts
 */
class T extends ComponentFactory {
}

T::conf([
    'component' => [
        'bus'       => [
            'class'     => event\Bus::class,
            'singleton' => true,
            'conf'      => [],
        ],
        'logger'    => [
            'class'     => Logger::class,
            'singleton' => true,
            'conf'      => [
                'name'     => 'app',
                'handlers' => [
                    StreamHandler::class => [
                        X_RUNTIME_ROOT . '/app.log',
                        X_LOG_DEBUG,
                    ],
                ],
            ],
        ],
        'cc'        => [
            'class'     => cache\CCache::class,
            'singleton' => true,
            'conf'      => [
                'cacheDir' => X_RUNTIME_ROOT . '/cache',
            ],
        ],
        'cache'     => [
            'class'     => cache\RCache::class,
            'singleton' => true,
            'conf'      => [
                'host'           => 'localhost',
                'port'           => 6379, //int, 6379 by default
                'timeout'        => 0, //float, value in seconds, default is 0 meaning unlimited
                'persistent'     => false, //bool, false by default
                'persistentHash' => 'rcache',//identity for the requested persistent connection
                'database'       => 0, //number, 0 by default
                'auth'           => null, //string, null by default
                'keyPrefix'      => '',
            ],
        ],
        'db'        => [
            'class'     => db\MySQL::class,
            'singleton' => true,
            'conf'      => [
                'host'               => 'localhost',
                'port'               => 3306,
                'user'               => 'root',
                'password'           => 'root',
                'dbname'             => 'test',
                'charset'            => 'utf8',
                'persistent'         => false,
                'mysqlCheckDuration' => 60,
            ],
        ],
        'view'      => [
            'class'     => view\Simple::class,
            'singleton' => true,
            'conf'      => [
                'tpl_dir'       => X_PROJECT_ROOT . '/tpl',
                'tpl_ext'       => 'html',
                'compile_dir'   => X_RUNTIME_ROOT . '/compiled_template',
                'cacheId'       => 'cache', // string to cache component id or false to disable cache
                'cacheDuration' => 60, // second
            ],
        ],
        'validator' => [
            'class'     => validator\Validator::class,
            'singleton' => false,
            'conf'      => [
                'encoding' => 'UTF-8',
                'autoTrim' => true,
            ],
        ],
    ],
]);
