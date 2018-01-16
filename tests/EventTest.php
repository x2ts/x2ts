<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2018/1/16
 * Time: 下午5:43
 */

namespace x2ts;

use PHPUnit\Framework\TestCase;
use x2ts\route\event\PostRunEvent;

class EventTest extends TestCase {
    public function testDispatch() {
        T::bus()->on(PostRunEvent::name(), function ($ev) {
            $this->callbackTimes++;
            self::assertInstanceOf(PostRunEvent::class, $ev);
        });
        T::bus()->dispatch(new PostRunEvent(['dispatcher' => $this]));
    }

    public $callbackTimes = 0;

    /**
     * @runInSeparateProcess
     */
    public function testMultiCallback() {
        T::bus()->on(PostRunEvent::name(), function ($ev) {
            $this->callbackTimes++;
        });
        T::bus()->on(PostRunEvent::name(), function ($ev) {
            $this->callbackTimes++;
        });
        T::bus()->dispatch(new PostRunEvent(['dispatcher' => $this]));
        self::assertEquals(2, $this->callbackTimes);
    }

    /**
     * @runInSeparateProcess
     */
    public function testStopAttaching() {
        T::bus()->on(PostRunEvent::name(), function (PostRunEvent $ev) {
            $this->callbackTimes++;
            $ev->stopAttaching('stop attaching');
        });
        T::bus()->on(PostRunEvent::name(), function ($ev) {
            $this->callbackTimes++;
        });
        T::bus()->dispatch(new PostRunEvent(['dispatcher' => $this]));
        self::assertEquals(1, $this->callbackTimes);
    }
}
