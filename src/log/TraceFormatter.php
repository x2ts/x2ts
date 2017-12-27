<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/12/27
 * Time: 下午3:02
 */

namespace x2ts\log;


use Monolog\Formatter\FormatterInterface;
use x2ts\Logger;

class TraceFormatter implements FormatterInterface {
    public function format(array $record) {
        $traceIndex = $record['context']['traceIndex'];
        $traces = debug_backtrace();
        foreach ($traces as $trace) {
            if ($trace['class'] === Logger::class && $trace['function'] === 'log') {
                break;
            }
            $traceIndex++;
        }

        $source = $traceIndex < count($traces) ?
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
}
