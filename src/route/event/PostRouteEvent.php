<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/4/5
 * Time: PM12:45
 */

namespace x2ts\route\event;


use x2ts\event\Event;
use x2ts\route\Action;

class PostRouteEvent extends Event {
    public static function name(): string {
        return 'x2ts.route.PostRoute';
    }

    /**
     * @var string
     */
    public $uri;

    /**
     * @var Action
     */
    public $action;

    /**
     * @var array
     */
    public $args;

    public function __construct(
        array $props = [
            'dispatcher' => null,
            'uri'        => '',
            'action'     => null,
            'args'       => [],
        ]
    ) {
        parent::__construct($props);
    }
}