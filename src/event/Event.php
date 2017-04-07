<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/2/10
 * Time: 下午9:07
 */

namespace x2ts\event;

use x2ts\Toolkit;

class Event {
    /**
     * @var string
     */
    public $name = '';

    /**
     * @var mixed
     */
    public $dispatcher;

    /**
     * @var mixed
     */
    public $state;

    /**
     * @var bool
     */
    private $stopped = false;

    /**
     * Event constructor.
     *
     * @param string $name
     * @param array  $props
     */
    public function __construct(
        string $name,
        array $props = [
            'dispatcher' => null,
        ]
    ) {
        foreach ($props as $key => $value) {
            $this->$key = $value;
        }
        $this->name = $name;
    }

    public function __toString() {
        return $this->name;
    }

    public function stopAttaching($message) {
        Toolkit::log("Event {$this->name} stopped attaching with message $message", X_LOG_INFO);
        $this->stopped = true;
        return $this;
    }

    public function isAttachingStopped(): bool {
        return (bool) $this->stopped;
    }
}