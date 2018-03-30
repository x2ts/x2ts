<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2018/1/26
 * Time: 上午11:37
 */

namespace x2ts\log;


use x2ts\Component;

class AmqpDelegatePublisher extends Component {
    protected static $_conf = [
        'sock'              => 'unix:///var/run/amqp-delegate.sock',
        'connectionTimeout' => 3,
        'writeTimeout'      => 10,
        'exchange'          => 'log',
    ];

    private $sock;

    public function getStream() {
        if (!is_resource($this->sock)) {
            $this->sock = fsockopen(
                $this->conf['sock'],
                null,
                $errno,
                $error,
                $this->conf['connectionTimeout']
            );
            if (!is_resource($this->sock)) {
                throw new \UnexpectedValueException("Failed connecting to {$this->conf['sock']} ($errno: $error)");
            }
            if (!stream_set_timeout($this->sock, $this->conf['writeTimeout'])) {
                throw new \UnexpectedValueException("Failed setting timeout with stream_set_timeout()");
            }
        }

        return $this->sock;
    }

    public function close() {
        if (is_resource($this->sock)) {
            fclose($this->sock);
            $this->sock = null;
        }
    }

    public function __destruct() {
        $this->close();
    }

    /**
     * @param string $routingKey
     * @param string $log
     *
     * @return $this
     */
    public function publish(string $routingKey, string $log) {
        $this->write(msgpack_pack([
            'Exchange'   => $this->conf['exchange'],
            'RoutingKey' => $routingKey,
            'Log'        => $log,
        ]));
        return $this;
    }

    protected function write(string $data) {
        $this->getStream();
        $data = pack('N', strlen($data)) . $data;
        $size = strlen($data);
        for ($sent = 0; $sent < $size && !feof($this->sock);) {
            if ($sent) {
                $chunk = fwrite($this->sock, substr($data, $sent));
            } else {
                $chunk = fwrite($this->sock, $data);
            }
            if ($chunk === false) {
                throw new \RuntimeException("Could not write to socket");
            }
            $sent += $chunk;
            $meta = stream_get_meta_data($this->sock);
            if ($meta['timed_out']) {
                throw new \RuntimeException("Write timed-out");
            }
        }
        if ($sent < $size && feof($this->sock)) {
            throw new \RuntimeException("End-of-file reached, probably we got disconnected (sent $sent of $size)");
        }
        return $this;
    }
}