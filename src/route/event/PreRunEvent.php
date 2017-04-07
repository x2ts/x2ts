<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/4/5
 * Time: PM2:39
 */

namespace x2ts\route\event;


use x2ts\event\Event;
use x2ts\route\Action;

class PreRunEvent extends Event {
    /**
     * @var Action
     */
    public $action;

    public function __construct(
        array $props = [
            'dispatcher' => null,
            'action'     => null,
        ]
    ) {
        parent::__construct('x2ts.route.PreRun', $props);
    }
}