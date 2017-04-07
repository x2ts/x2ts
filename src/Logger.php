<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/4/4
 * Time: PM4:09
 */

namespace x2ts;


use Monolog\Logger as MonoLogger;
use Throwable;

define('X_LOG_DEBUG', MonoLogger::DEBUG);
define('X_LOG_INFO', MonoLogger::INFO);
define('X_LOG_NOTICE', MonoLogger::NOTICE);
define('X_LOG_WARNING', MonoLogger::WARNING);
define('X_LOG_ERROR', MonoLogger::ERROR);
define('X_LOG_CRITICAL', MonoLogger::CRITICAL);
define('X_LOG_ALERT', MonoLogger::ALERT);
define('X_LOG_EMERGENCY', MonoLogger::EMERGENCY);

/**
 * Class Logger
 *
 * @property-read MonoLogger $logger
 * @package x2ts
 */
class Logger extends Component {
    protected static $_conf = [
        'name'     => 'app',
        'handlers' => [
            // handler class name => [construct args]
        ],
    ];

    /**
     * @var int
     */
    private $pid;

    /**
     * @var MonoLogger
     */
    private $_logger;

    public function getLogger() {
        if (!$this->_logger instanceof MonoLogger) {
            $this->_logger = new MonoLogger($this->conf['name']);
            /** @var array $handlerConfigs */
            $handlerConfigs = $this->conf['handlers'];
            foreach ($handlerConfigs as $class => $args) {
                $this->_logger->pushHandler(new $class(...$args));
            }
        }
        return $this->_logger;
    }

    public function trace($msg) {
        $this->log($msg, X_LOG_DEBUG, 2);
    }

    public function debug($msg) {
        $this->log($msg, X_LOG_DEBUG, 2);
    }

    public function info($msg) {
        $this->log($msg, X_LOG_INFO, 2);
    }

    public function notice($msg) {
        $this->log($msg, X_LOG_NOTICE, 2);
    }

    public function warning($msg) {
        $this->log($msg, X_LOG_WARNING, 2);
    }

    public function error($msg) {
        $this->log($msg, X_LOG_ERROR, 2);
    }

    public function critical($msg) {
        $this->log($msg, X_LOG_CRITICAL, 2);
    }

    public function alert($msg) {
        $this->log($msg, X_LOG_ALERT, 2);
    }

    public function emergency($msg) {
        $this->log($msg, X_LOG_EMERGENCY, 2);
    }

    /**
     * @param mixed $msg
     * @param int   $level
     * @param int   $traceIndex
     */
    public function log($msg, $level, $traceIndex = 1) {
        if ($msg instanceof Throwable) {
            $logMessage = sprintf(
                "%s is thrown at %s(%d) with message: %s\nCall stack:\n%s",
                get_class($msg),
                $msg->getFile(),
                $msg->getLine(),
                $msg->getMessage(),
                $msg->getTraceAsString()
            );
        } elseif (is_callable($msg)) {
            $logMessage = $msg();
        } elseif (!is_string($msg)) {
            ob_start();
            /** @noinspection ForgottenDebugOutputInspection */
            var_dump($msg);
            $logMessage = ob_get_contents();
            ob_end_clean();
        } else {
            $logMessage = (string) $msg;
        }
        $trace = debug_backtrace();
        if ($traceIndex < count($trace)) {
            $class = $trace[$traceIndex]['class'] ?? 'FUNC';
            $func = $trace[$traceIndex]['function'];
            $category = "$class::$func";
        } else {
            $category = 'GLOBAL';
        }

        /** @noinspection ReturnFalseInspection */
        $this->logger->addRecord($level, sprintf(
            '[%s][%d][%s]%s',
            date('c'),
            $this->pid ?? ($this->pid = posix_getpid()),
            $category,
            $logMessage
        ));
    }
}