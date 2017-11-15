<?php

namespace x2ts;

require_once __DIR__ . '/xts.php';

use Exception;
use PHPUnit\Framework\TestCase;
use stdClass;
use Throwable;

/**
 * Class ToolkitTest
 */
class ToolkitTest extends TestCase {
    /**
     * @dataProvider dataForToCamelCase
     *
     * @param $name
     * @param $pascal
     * @param $result
     */
    public function testToCamelCase($name, $pascal, $result) {
        static::assertEquals($result, Toolkit::toCamelCase($name, $pascal));
    }

    public function dataForToCamelCase() {
        return array(
            array('hello', false, 'hello'),
            array('hello', true, 'Hello'),

            array('hello world', false, 'helloWorld'),
            array('hello_world', false, 'helloWorld'),
            array('helloWorld', false, 'helloWorld'),
            array('HELLO_WORLD', false, 'helloWorld'),
            array('HelLO_WoRLD', false, 'helloWorld'),
            array('HelloWoRld', false, 'helloWoRld'),
            array('__hello_world', false, 'helloWorld'),
            array('_ hello_world', false, 'helloWorld'),

            array('hello world', true, 'HelloWorld'),
            array('hello_world', true, 'HelloWorld'),
            array('helloWorld', true, 'HelloWorld'),
            array('HelloWoRld', true, 'HelloWoRld'),
            array('HelLO_WoRLD', true, 'HelloWorld'),
        );
    }

    /**
     * @param $name
     * @param $ufl
     * @param $result
     *
     * @dataProvider dataForToSnakeCase
     */
    public function testToSnakeCase($name, $ufl, $result) {
        static::assertEquals($result, Toolkit::to_snake_case($name, $ufl));
    }

    public function dataForToSnakeCase() {
        return array(
            array('hello', false, 'hello'),
            array('hello', true, 'Hello'),

            array('hello world', false, 'hello_world'),
            array('hello_world', false, 'hello_world'),
            array('helloWorld', false, 'hello_world'),
            array('HELLO_WORLD', false, 'hello_world'),
            array('HelLO_WoRLD', false, 'hello_world'),
            array('HelloWoRld', false, 'hello_wo_rld'),
            array('__hello_world', false, 'hello_world'),
            array('_ hello_world', false, 'hello_world'),

            array('hello world', true, 'Hello_World'),
            array('hello_world', true, 'Hello_World'),
            array('helloWorld', true, 'Hello_World'),
            array('HelloWoRld', true, 'Hello_Wo_Rld'),
            array('HelLO_WoRLD', true, 'Hello_World'),
        );
    }

    /**
     * @param $dst
     * @param $src
     * @param $result
     *
     * @dataProvider dataForOverride
     */
    public function testOverride($dst, $src, $result) {
        $r = Toolkit::override($dst, $src);
        static::assertEquals($result, $r);
    }

    /**
     * @param $dst
     * @param $src
     * @param $result
     *
     * @dataProvider dataForOverride
     */
    public function testOverrideReference($dst, $src, $result) {
        Toolkit::override($dst, $src);
        static::assertEquals($result, $dst);
    }

    public function dataForOverride() {
        return [
            [
                [1, 2, 3],
                [4, 5, 6],
                [1, 2, 3, 4, 5, 6],
            ],
            [
                ['a' => [1, 2, 3]],
                ['a' => [4, 5, 6]],
                ['a' => [4, 5, 6]],
            ],
            [
                ['a' => 1, 'b' => 2, 'c' => 3],
                ['a' => 2, 'b' => 5, 5, 6],
                ['a' => 2, 'b' => 5, 'c' => 3, 5, 6],
            ],
            [
                ['a' => [], 'b' => 2, 'c' => 3, 4],
                ['a' => ['s' => 1], 5, 'c' => 7],
                ['a' => ['s' => 1], 'b' => 2, 'c' => 7, 4, 5],
            ],
            [
                ['a' => ['s' => 4], 'b' => 2, 'c' => 3, 4],
                ['a' => ['s' => 1], 5, 'c' => 7],
                ['a' => ['s' => 1], 'b' => 2, 'c' => 7, 4, 5],
            ],
            [
                ['a' => ['S' => 4], 'b' => 2, 'c' => 3, 4],
                ['a' => ['s' => 1], 5, 'c' => 7],
                ['a' => ['S' => 4, 's' => 1], 'b' => 2, 'c' => 7, 4, 5],
            ],
        ];
    }

    /**
     * @param $data
     *
     * @internal     param $result
     *
     * @dataProvider dataForCompile
     */
    public function testCompileNormalData($data) {
        $dataRebuild = eval('return ' . Toolkit::compile($data) . ';');
        static::assertEquals($data, $dataRebuild);
    }

    public function dataForCompile() {
        return array(
            array(null, 'NULL'),
            array('string', '\'string\''),
            array("'string'", "'\\'string\\''"),
            array('"string"', '\'"string"\''),
            array('"string with \"', '\'"string with \\\\"\''),
            array('\\\\\\\\\\', '\'\\\\\\\\\\\\\\\\\\\\\''),
            array(10, '10'),
            array(2.49, '2.49'),
            array('There are 8', '\'There are 8\''),
            array("\"", '\'"\''),
            array("\n", "'\n'"),
            array('\n', '\'\\\\n\''),
            array('\'', '\'\\\'\''),
            array(array('value', 'value2'), "array (\n  0 => 'value',\n  1 => 'value2',\n)"),
            array(
                array('key' => array('value', 'value2'), 10, 2.49, "\n", true),
                "array (\n  'key' => \n  array (\n    0 => 'value',\n    1 => 'value2',\n  ),\n  0 => 10,\n  1 => 2.4900000000000002,\n  2 => '\n',\n  3 => true,\n)",
            ),
            array(
                array('key' => 'k', 2 => 'vv', 3, 4, 5),
                "array (\n  'key' => 'k',\n  2 => 'vv',\n  3 => 3,\n  4 => 4,\n  5 => 5,\n)",
            ),
            array('f风时朋sp', '\'f风时朋sp\''),
        );
    }

    /**
     * @expectedException \x2ts\UncompilableException
     */
    public function testUnCompilableResource() {
        $f = fopen('/dev/null', 'wb');
        Toolkit::compile($f);
    }

    /**
     * @expectedException \x2ts\UncompilableException
     */
    public function testUnCompilableObject() {
        $o = new stdClass();
        Toolkit::compile($o);
    }

    private function logFile() {
        return X_RUNTIME_ROOT . '/app.log';
    }

    /**
     * @param $msg
     * @param $level
     *
     * @dataProvider logDataProvider
     */
    public function testLog($msg, $level) {
        Toolkit::log($msg, $level);
        system('tail -n 1 ' . $this->logFile());
        $levelStrDict = array(
            X_LOG_DEBUG   => 'debug',
            X_LOG_NOTICE  => 'notice',
            X_LOG_WARNING => 'warning',
            X_LOG_ERROR   => 'error',
        );
        $this->getActualOutput();
        $this->expectOutputRegex("/\[{$levelStrDict[$level]}\]\[\d+\]\[x2ts\\\\ToolkitTest::testLog\]$msg/");
    }

    public function logDataProvider() {
        return array(
            array('hello', X_LOG_DEBUG, 'app'),
            array('hello', X_LOG_NOTICE, 'app'),
            array('hello', X_LOG_WARNING, 'app'),
            array('hello', X_LOG_ERROR, 'app'),
            array('hello', X_LOG_DEBUG, 'test'),
        );
    }

    public function testTrace() {
        Toolkit::trace('hey');
        $logFile = X_RUNTIME_ROOT . '/app.log';
        system("tail -n 1 $logFile");
        $this->expectOutputRegex('/\[debug\]\[\d+\]\[x2ts\\\\ToolkitTest::testTrace\]hey/');
    }

    /**
     * @dataProvider pluralizeDP
     *
     * @param $singular
     * @param $plural
     */
    public function testPluralize($singular, $plural) {
        static::assertEquals($plural, Toolkit::pluralize($singular));
    }

    public function pluralizeDP() {
        return array(
            ['one', 'ones'],
            ['stock', 'stocks'],
            ['wolf', 'wolves'],
            ['ox', 'oxen'],
            ['person', 'people'],
            ['photo', 'photos'],
            ['leaf', 'leaves'],
            ['money', 'money_list'],
            ['fox', 'foxes'],
            ['knowledge', 'knowledge_list'],
            ['tea', 'tea_list'],
        );
    }

    public function testPluralizeFail() {
        static::assertFalse(Toolkit::pluralize(''));
    }

    public function testCallableLog() {
        Toolkit::log(function () {
            return "welcome";
        });
        system('tail -n 1 ' . $this->logFile());
        $this->expectOutputRegex('/\[debug\]\[\d+\]\[x2ts\\\\ToolkitTest::testCallableLog\]welcome/');
    }

    public function testArrayLog() {
        $arr = $this->logDataProvider();
        ob_start();
        var_dump($arr);
        $r = ob_get_contents();
        ob_end_clean();
        Toolkit::log($arr);
        $lineCount = substr_count($r, "\n") + 1;
        system("tail -n $lineCount " . $this->logFile());
        $this->expectOutputRegex('/\[debug\]\[\d+\]\[x2ts\\\\ToolkitTest::testArrayLog\]/');
    }

    public function testThrowableLog() {
        try {
            throw new Exception('Some say good while some say bad.', 227);
        } catch (Throwable $t) {
            Toolkit::log($t, X_LOG_ERROR);
            $s = "\n\n" . $t->getTraceAsString();
            $lineCount = substr_count($s, "\n") + 1;
            system("tail -n $lineCount " . $this->logFile());
            $this->expectOutputRegex('/\[error\]\[\d+\]\[x2ts\\\\ToolkitTest::testThrowableLog\].*Some say good while some say bad/');
        }
    }

    public function testRandomChars() {
        $chars = Toolkit::randomChars(16);
        static::assertEquals(16, strlen($chars));
    }
}
