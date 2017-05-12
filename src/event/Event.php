<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/2/10
 * Time: 下午9:07
 */

namespace x2ts\event;

use x2ts\ComponentFactory as X;

abstract class Event {
    abstract public static function name(): string;

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
     * @param array  $props
     */
    public function __construct(
        array $props = [
            'dispatcher' => null,
        ]
    ) {
        foreach ($props as $key => $value) {
            $this->$key = $value;
        }
    }

    public function __toString() {
        return static::name();
    }

    public function stopAttaching($message) {
        X::logger()->notice("Event $this stopped attaching with message $message");
        $this->stopped = true;
        return $this;
    }

    public function isAttachingStopped(): bool {
        return (bool) $this->stopped;
    }
}