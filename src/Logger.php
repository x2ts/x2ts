<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/4/4
 * Time: PM4:09
 */

namespace x2ts;


use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonoLogger;
use Throwable;
use x2ts\log\TraceFormatter;

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
            // handler [class => construct args, ...]
            // handler [['class' => class name, 'args' => construct args, 'formatter' => FormatterInterface],...]
        ],
    ];

    /**
     * @var MonoLogger
     */
    private $_logger;

    public function getLogger() {
        if (!$this->_logger instanceof MonoLogger) {
            $this->_logger = new MonoLogger($this->conf['name']);
            /** @var array $handlerConfigs */
            $handlerConfigs = $this->conf['handlers'];
            if (empty($handlerConfigs)) {
                $handlerConfigs = [
                    StreamHandler::class => ['php://stderr', MonoLogger::DEBUG],
                ];
            }
            foreach ($handlerConfigs as $key => $value) {
                if (is_int($key) && isset($value['class'])) {
                    $class = $value['class'];
                    $args = $value['args'] ?? [];
                    $formatter = $value['formatter'] ?? null;
                } else {
                    $class = $key;
                    $args = $value;
                    $formatter = new TraceFormatter();
                }
                if (!is_string($class) || !is_array($args) || !class_exists($class)) {
                    continue;
                }
                /** @var AbstractHandler $handler */
                $handler = new $class(...$args);
                if ($formatter instanceof FormatterInterface) {
                    $handler->setFormatter($formatter);
                }
                $this->_logger->pushHandler($handler);
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

    public function warn($msg) {
        $this->log($msg, X_LOG_WARNING, 2);
    }

    public function warning($msg) {
        $this->log($msg, X_LOG_WARNING, 2);
    }

    public function error($msg) {
        $this->log($msg, X_LOG_ERROR, 2);
    }

    public function crit($msg) {
        $this->log($msg, X_LOG_CRITICAL, 2);
    }

    public function critical($msg) {
        $this->log($msg, X_LOG_CRITICAL, 2);
    }

    public function alert($msg) {
        $this->log($msg, X_LOG_ALERT, 2);
    }

    public function emerg($msg) {
        $this->log($msg, X_LOG_EMERGENCY, 2);
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
        $logMessage = is_string($msg) ? $msg : new class($msg) {
            private $msg;

            public function __construct($msg) {
                $this->msg = $msg;
            }

            public function __toString() {
                $msg =& $this->msg;
                if ($msg instanceof ILogString) {
                    $logMessage = $msg->toLogString();
                } else if ($msg instanceof Throwable) {
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
                } else {
                    ob_start();
                    /** @noinspection ForgottenDebugOutputInspection */
                    var_dump($msg);
                    $logMessage = ob_get_contents();
                    ob_end_clean();
                }
                return $logMessage;
            }
        };
        /** @noinspection ReturnFalseInspection */
        $this->logger->addRecord($level, $logMessage, ['traceIndex' => $traceIndex]);
    }
}