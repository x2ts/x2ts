<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2018/1/22
 * Time: 下午2:08
 */

namespace x2ts\log;


use Monolog\Handler\SocketHandler;
use Monolog\Logger;
use x2ts\Toolkit;

class AmqpDelegateHandler extends SocketHandler {
    protected $conf = [
        'sock'              => 'unix:///var/run/amqp-delegate.sock',
        'connectionTimeout' => 3,
        'writeTimeout'      => 10,
        'exchange'          => 'log',
        'routingKey'        => '{channel}.{level}',
    ];

    public function __construct(array $settings, int $level = Logger::DEBUG, bool $bubble = true) {
        Toolkit::override($this->conf, $settings);
        parent::__construct($this->conf['sock'], $level, $bubble);
        $this->setConnectionTimeout($this->conf['connectionTimeout']);
        $this->setTimeout($this->conf['writeTimeout']);
    }

    protected function generateDataStream($record) {
        $packedMessage = msgpack_pack([
            'Exchange'   => $this->conf['exchange'],
            'RoutingKey' => str_replace(
                ['{channel}', '{level}'],
                [$record['channel'], strtolower($record['level_name'])],
                $this->conf['routingKey']
            ),
            'Log'        => $record['formatted'],
        ]);
        return pack('N', strlen($packedMessage)) . $packedMessage;
    }
}