<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2018/1/26
 * Time: 下午2:30
 */

namespace x2ts\route\event;


use x2ts\event\Event;

class PostResponseEvent extends Event {
    /**
     * @var \x2ts\route\Action
     */
    public $action;

    public static function name(): string {
        return 'x2ts.route.PostResponse';
    }

    public function __construct(
        array $props = [
            'dispatcher' => null,
            'action'     => null,
        ]
    ) {
        parent::__construct($props);
    }
}