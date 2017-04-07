<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/4/4
 * Time: PM7:18
 */

namespace x2ts\route\event;


use x2ts\event\Event;

class PreRouteEvent extends Event {
    /**
     * @var string
     */
    public $uri = '';

    public function __construct(
        array $props = [
            'dispatcher' => null,
            'uri'        => '',
        ]
    ) {
        $name = 'x2ts.route.PreRoute';
        parent::__construct($name, $props);
    }
}