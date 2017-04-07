<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/2/12
 * Time: 上午9:27
 */

namespace x2ts\event;

use x2ts\Component;

class Bus extends Component {
    protected $_events = [];

    /**
     * @param string   $eventName
     * @param callable $callback
     * @param mixed    $state
     *
     * @return $this
     */
    public function on(string $eventName, callable $callback, $state = null) {
        $this->_events[$eventName][] = array(
            'callback' => $callback,
            'state'    => $state,
        );
        return $this;
    }

    /**
     * @param Event $event
     *
     * @return int the number of handler calls
     */
    public function dispatch(Event $event): int {
        /** @var array $listeners */
        $listeners = $this->_events[$event->name] ?? [];
        $i = 0;
        foreach ($listeners as $listener) {
            $callback = $listener['callback'];
            if (is_callable($callback)) {
                $event->state = $listener['state'];
                $callback($event);
                $i++;
                if ($event->isAttachingStopped()) {
                    return $i;
                }
            }
        }

        return $i;
    }
}