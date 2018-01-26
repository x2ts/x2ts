<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/4/5
 * Time: PM2:34
 */

namespace x2ts\route\event;


use x2ts\DeprecatedException;
use x2ts\event\Event;
use x2ts\route\Action;

/**
 * Class PreActionEvent
 *
 * @package x2ts\route\event
 * @deprecated
 */
class PreActionEvent extends Event {
    public static function name(): string {
        throw new DeprecatedException('PreActionEvent is deprecated');
    }

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
        throw new DeprecatedException('PreActionEvent is deprecated');
    }
}