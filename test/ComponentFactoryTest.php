<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/4/8
 * Time: PM7:34
 */

namespace x2ts;

require_once __DIR__ . '/xts.php';

use PHPUnit\Framework\TestCase;

class ComponentFactoryTest extends TestCase {
    public function testConfFetch() {
        $someOtherConf = T::conf('someOtherConf');
        static::assertEquals('array', gettype($someOtherConf));
        static::assertArrayHasKey('sth', $someOtherConf);
        static::assertEquals('abc', $someOtherConf['sth']);
    }

    public function testConfObjectVisit() {
        $someOtherConf = T::conf()->someOtherConf;
        static::assertEquals('object', gettype($someOtherConf));
        static::assertObjectHasAttribute('sth', $someOtherConf);
        static::assertEquals('abc', $someOtherConf->sth);
    }

    public function testConfOveride() {
        static::assertEquals('bar', T::conf('someOtherConf')['foo']);
        T::conf(['someOtherConf' => ['foo' => 'def']]);
        static::assertEquals('def', T::conf('someOtherConf')['foo']);
        static::assertEquals('abc', T::conf('someOtherConf')['sth']);
    }
}
