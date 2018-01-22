<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2018/1/22
 * Time: 下午1:09
 */

namespace x2ts\log;


class AmqpDelegateFormatter extends TraceFormatter {
    private $exchange;

    private $routingKey;

    public function __construct(string $exchange, string $routingKey) {
        $this->exchange = $exchange;
        $this->routingKey = $routingKey;
    }

    public function format(array $record) {
        $s = parent::format($record);
        return json_encode([
            'exchange'    => $this->exchange,
            'routing_key' => str_replace(
                ['{channel}', '{level}'],
                [$record['channel'], strtolower($record['level_name'])],
                $this->routingKey
            ),
            'log'         => $s,
        ]) . "\n";
    }
}