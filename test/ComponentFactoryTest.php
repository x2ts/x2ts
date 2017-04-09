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

    public function testConfOverride() {
        static::assertEquals('bar', T::conf('someOtherConf')['foo']);
        T::conf(['someOtherConf' => ['foo' => 'def']]);
        static::assertEquals('def', T::conf('someOtherConf')['foo']);
        static::assertEquals('abc', T::conf('someOtherConf')['sth']);
    }

    private function logFile() {
        return X_RUNTIME_ROOT . '/app.log';
    }

    public function testLog() {
        T::log('cft', X_LOG_CRITICAL);
        system('tail -n 1 ' . $this->logFile());
        $this->getActualOutput();
        $this->expectOutputRegex("/\[critical\]\[\d+\]\[x2ts\\\\ComponentFactoryTest::testLog\]cft/");
    }

    public function testTrace() {
        T::trace('cft');
        system('tail -n 1 ' . $this->logFile());
        $this->getActualOutput();
        $this->expectOutputRegex("/\[debug\]\[\d+\]\[x2ts\\\\ComponentFactoryTest::testTrace\]cft/");
    }

    public function testGetSingleton() {
        self::assertInstanceOf(event\Bus::class, T::bus());
    }

    public function testGetInstance() {
        self::assertInstanceOf(validator\Validator::class, T::validator([]));
    }

    /**
     * @expectedException \x2ts\ComponentNotFoundException
     */
    public function testNotExistsComponent() {
        T::getComponent('notExists');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNotIComponent() {
        T::getInstance('stdClass', [], [], '');
    }
}
