<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/4/10
 * Time: AM12:02
 */

namespace x2ts;
require_once __DIR__ . '/xts.php';

use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase {
    private $logFile = X_RUNTIME_ROOT . '/app.log';

    /**
     * @param $func
     * @param $level
     *
     * @dataProvider levelData
     */
    public function testSeparateLogLevel($func, $level) {
        T::logger()->$func('test');
        system('tail -n 1 ' . $this->logFile);
        $this->getActualOutput();
        $this->expectOutputRegex("/\[$level\]\[\d+\]\[x2ts\\\\LoggerTest::testSeparateLogLevel\]test/");
    }

    public function levelData() {
        return [
            ['trace', 'debug'],
            ['debug', 'debug'],
            ['info', 'info'],
            ['notice', 'notice'],
            ['warn', 'warning'],
            ['warning', 'warning'],
            ['error', 'error'],
            ['crit', 'critical'],
            ['critical', 'critical'],
            ['alert', 'alert'],
            ['emerg', 'emergency'],
            ['emergency', 'emergency'],
        ];
    }

    public function testILogString() {
        T::logger()->warn(new class implements ILogString {
            public function toLogString(): string {
                return 'ILogString';
            }
        });
        system('tail -n 1 ' . $this->logFile);
        $this->getActualOutput();
        $this->expectOutputRegex("/\[warning\]\[\d+\]\[x2ts\\\\LoggerTest::testILogString\]ILogString/");
    }
}
