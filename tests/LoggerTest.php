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

    const log2File = X_RUNTIME_ROOT . '/logger2.log';

    public function testLoggerLevel() {
        T::logger2()->warn('hehe');
        T::logger2()->info('def');
        T::logger2()->trace('abc');
        system('tail -n 1 ' . self::log2File);
        $this->getActualOutput();
        $this->expectOutputRegex("/\[warning\]\[\d+\]\[x2ts\\\\LoggerTest::testLoggerLevel\]hehe/");
    }

    public function testAmqpLogger() {
        $monitor = proc_open('php ' . __DIR__ . '/monitor.php', [
            ['file', '/dev/zero', 'r'],
            ['pipe', 'w'],
            ['file', '/dev/null', 'w'],
        ], $pipes, __DIR__);
        sleep(1);
        T::logger2()->notice('finger');
        $output = fgets($pipes[1]);
        self::assertRegExp("/\[notice\]\[\d+\]\[x2ts\\\\LoggerTest::testAmqpLogger\]finger/", $output);
        T::logger2()->warn('www');
        $output = fgets($pipes[1]);
        self::assertRegExp("/\[warning\]\[\d+\]\[x2ts\\\\LoggerTest::testAmqpLogger\]www/", $output);
        fclose($pipes[1]);
        proc_terminate($monitor);
        proc_close($monitor);
    }
}
