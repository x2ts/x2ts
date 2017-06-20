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
            foreach ($handlerConfigs as $class => $args) {
                if (!is_array($args)) {
                    continue;
                }
                /** @var AbstractHandler $handler */
                $handler = new $class(...$args);
                /** @noinspection PhpParamsInspection */
                $handler->setFormatter(new class implements FormatterInterface {
                    public function format(array $record) {
                        $traceIndex = $record['context']['traceIndex'];
                        $traces = debug_backtrace();
                        foreach ($traces as $trace) {
                            if ($trace['class'] === Logger::class && $trace['function'] === 'log') {
                                break;
                            }
                            $traceIndex++;
                        }

                        $source = count($traces) ?
                            (($traces[$traceIndex]['class'] ?? 'FUNC') . '::' .
                                $traces[$traceIndex]['function']) :
                            'GLOBAL';
                        /** @var \DateTime $datetime */
                        $datetime = $record['datetime'];
                        return sprintf('[%s][%s][%d][%s]%s',
                                $datetime->format('c'),
                                strtolower($record['level_name']),
                                getmypid(),
                                $source,
                                $record['message']
                            ) . "\n";
                    }

                    public function formatBatch(array $records) {
                        return implode('', array_map([$this, 'format'], $records));
                    }
                });
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
        /** @noinspection ReturnFalseInspection */
        $this->logger->addRecord($level, $logMessage, ['traceIndex' => $traceIndex]);
    }
}