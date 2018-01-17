<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2018/1/16
 * Time: 下午2:14
 */

namespace x2ts\log;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use x2ts\Toolkit;

class AmqpHandler extends AbstractProcessingHandler {
    private $conf = [
        'amqp'          => [
            'host'            => 'localhost',
            'port'            => 5672,
            'login'           => 'guest',
            'password'        => 'guest',
            'vhost'           => '/',
            'read_timeout'    => 30,
            'write_timeout'   => 30,
            'connect_timeout' => 30,
        ],
        'exchange_name' => 'log',
        'routing_key'   => '{channel}.{level}',
    ];

    private $connection;

    private $channel;

    private $exchange;

    /**
     * @return \AMQPExchange
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPExchangeException
     */
    public function getAmqpExchange() {
        if (!$this->exchange instanceof \AMQPExchange) {
            if (!$this->channel instanceof \AMQPChannel) {
                if (!$this->connection instanceof \AMQPConnection) {
                    $this->connection = new \AMQPConnection($this->conf['amqp']);
                    $this->connection->connect();
                }
                $this->channel = new \AMQPChannel($this->connection);
            }
            $this->exchange = new \AMQPExchange($this->channel);
            $this->exchange->setType(AMQP_EX_TYPE_TOPIC);
            $this->exchange->setName($this->conf['exchange_name']);
            $this->exchange->declareExchange();
        }
        return $this->exchange;
    }

    public function __construct(array $conf = [], int $level = Logger::DEBUG, bool $bubble = true) {
        $this->conf = Toolkit::override($this->conf, $conf);
        parent::__construct($level, $bubble);
    }

    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  array $record
     *
     * @return void
     */
    protected function write(array $record) {
        try {
            $message = $record['formatted'];
            $routingKey = str_replace(
                ['{channel}', '{level}'],
                [$record['channel'], strtolower($record['level_name'])],
                $this->conf['routing_key']
            );
            $this->getAmqpExchange()->publish($message, $routingKey, AMQP_NOPARAM, ['delivery_mode' => 2]);
        } catch (\AMQPException $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
        }
    }
}