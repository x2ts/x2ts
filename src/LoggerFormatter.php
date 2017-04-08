<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/4/8
 * Time: PM5:20
 */

namespace x2ts;


use Monolog\Formatter\FormatterInterface;

class LoggerFormatter implements FormatterInterface {
    private $pid;

    /**
     * Formats a log record.
     *
     * @param  array $record A record to format
     *
     * @return mixed The formatted record
     */
    public function format(array $record) {
        $source = count($record['context']) ?
            (($record['context'][0]['class'] ?? 'FUNC') . '::' .
                $record['context'][0]['function']) :
            'GLOBAL';
        $pid = $this->pid ?? ($this->pid = posix_getpid());
        /** @var \DateTime $datetime */
        $datetime = $record['datetime'];
        return sprintf('[%s][%s][%d][%s]%s',
                $datetime->format('c'),
                strtolower($record['level_name']),
                $pid,
                $source,
                $record['message']
            ) . "\n";
    }

    /**
     * Formats a set of log records.
     *
     * @param  array $records A set of records to format
     *
     * @return mixed The formatted set of records
     */
    public function formatBatch(array $records) {
        return implode('', array_map([$this, 'format'], $records));
    }
}