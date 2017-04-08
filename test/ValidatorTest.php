<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2016/8/16
 * Time: 下午12:25
 */

namespace x2ts;

use PHPUnit\Framework\TestCase;
use x2ts\validator\DecimalValidator;
use x2ts\validator\HexadecimalValidator;
use x2ts\validator\IntegerValidator;
use x2ts\validator\StringValidator;
use x2ts\validator\ValidatorException;

require_once __DIR__ . '/xts.php';

class ValidatorTest extends TestCase {
    public $testData;

    public function setUp() {
        parent::setUp();
        $this->testData = [
            'emptyArr'       => [],
            'emptyStr'       => '',
            'null'           => null,
            'zeroInt'        => 0,
            'zeroFloat'      => 0.0,
            'zeroIntStr'     => '0',
            'positiveIntStr' => '8',
            'negativeIntStr' => '-6',
            'on'             => 'on',
            'off'            => 'off',
            'indexedArr'     => [
                'abc',
                'def',
            ],
            'telNumber'      => '+86-010-67798810-3752',
            'mobile'         => '13800138000',
            'email'          => 'superman@example.com',
            'string'         => 'Hello, world!',
        ];
    }

    /**
     * @expectedException \x2ts\validator\ValidatorException
     */
    public function testEmptyArrayReport() {
        T::validator($this->testData)
            ->arr('emptyArr')
            ->onEmptyReport('report')
            ->validate();
    }

    /**
     * @expectedException \x2ts\validator\ValidatorException
     */
    public function testEmptyStringReport() {
        T::validator($this->testData)
            ->str('emptyStr')
            ->onEmptyReport('report')
            ->validate();
    }

    /**
     * @expectedException \x2ts\validator\ValidatorException
     */
    public function testNullStringReport() {
        T::validator($this->testData)
            ->str('null')
            ->onEmptyReport('report')
            ->validate();
    }

    public function testEmptyArraySet() {
        $safeVar = T::validator($this->testData)
            ->arr('emptyArr')
            ->onEmptySet([1, 2, 3])
            ->validate()
            ->safeVar;
        static::assertEquals(1, $safeVar['emptyArr'][0]);
        static::assertEquals(2, $safeVar['emptyArr'][1]);
        static::assertEquals(3, $safeVar['emptyArr'][2]);
    }

    public function testEmptyStringSet() {
        $r = T::validator($this->testData)
            ->str('emptyStr')
            ->onEmptySet('abc')
            ->validate()
            ->safeVar;
        static::assertEquals('abc', $r['emptyStr']);
    }

    public function testNullSet() {
        $r = T::validator($this->testData)
            ->str('null')
            ->onEmptySet('good')
            ->validate()
            ->safeVar;
        static::assertEquals('good', $r['null']);
    }

    public function testArrayLength() {
        $r = T::validator($this->testData)
            ->arr('indexedArr')
            ->length(1)
            ->validate()
            ->safeVar;
        static::assertEquals('abc', $r['indexedArr'][0]);
        static::assertEquals('def', $r['indexedArr'][1]);
    }

    /**
     * @expectedException \x2ts\validator\ValidatorException
     */
    public function testArrayLengthTooShort() {
        T::validator($this->testData)
            ->arr('indexedArr')
            ->length(5)
            ->onErrorReport('error')
            ->validate();
    }

    /**
     * @expectedException \x2ts\validator\ValidatorException
     */
    public function testArrayLengthTooLong() {
        T::validator($this->testData)
            ->arr('indexedArr')
            ->length(0, 1)
            ->onErrorReport('error')
            ->validate();
    }

    /**
     * @expectedException \x2ts\validator\ValidatorException
     */
    public function testInvalidArray() {
        T::validator($this->testData)
            ->arr('zeroIntStr')
            ->length(1)
            ->onErrorReport('error')
            ->validate();
    }

    public function testTelString() {
        $r = T::validator($this->testData)
            ->tel('telNumber')
            ->onErrorReport('error')
            ->validate()
            ->safeVar;
        static::assertEquals('+86-010-67798810-3752', $r['telNumber']);
    }

    /**
     * @expectedException \x2ts\validator\ValidatorException
     */
    public function testInvalidTel() {
        T::validator($this->testData)
            ->tel('email')
            ->onErrorReport('error')
            ->validate();
    }

    public function testEmail() {
        $r = T::validator($this->testData)
            ->email('email')
            ->onErrorReport('error')
            ->validate()
            ->safeVar;
        static::assertEquals('superman@example.com', $r['email']);
    }

    /**
     * @expectedException \x2ts\validator\ValidatorException
     */
    public function testInvalidEmail() {
        T::validator($this->testData)
            ->email('mobile')
            ->onErrorReport('error')
            ->validate();
    }

    public function testStringLength() {
        $r = T::validator($this->testData)
            ->str('mobile')
            ->length(5, 15)
            ->onErrorReport('error')
            ->validate()
            ->safeVar;
        static::assertEquals('13800138000', $r['mobile']);
    }

    /**
     * @expectedException \x2ts\validator\ValidatorException
     */
    public function testStringLengthTooShort() {
        T::validator($this->testData)
            ->str('mobile')
            ->length(15)
            ->onErrorReport('error')
            ->validate();
    }

    /**
     * @expectedException \x2ts\validator\ValidatorException
     */
    public function testStringLengthTooLong() {
        T::validator($this->testData)
            ->str('mobile')
            ->length(0, 7)
            ->onErrorReport('error')
            ->validate();
    }

    public function testStringContain() {
        $r = T::validator($this->testData)
            ->str('string')
            ->contain('world')
            ->validate()
            ->safeVar;
        static::assertEquals($this->testData['string'], $r['string']);
    }

    /**
     * @expectedException \x2ts\validator\ValidatorException
     */
    public function testStringInvalidContain() {
        T::validator($this->testData)
            ->str('string')
            ->contain('World')
            ->onErrorReport('error')
            ->validate();
    }

    public function testStringContainCI() {
        $r = T::validator($this->testData)
            ->str('string')
            ->containCI('World')
            ->onErrorReport('error')
            ->validate()
            ->safeVar;
        static::assertEquals($this->testData['string'], $r['string']);
    }

    /**
     * @expectedException \x2ts\validator\ValidatorException
     */
    public function testStringInvalidContainCI() {
        T::validator($this->testData)
            ->str('string')
            ->containCI('word')
            ->onErrorReport('errors')
            ->validate();
    }

    /**
     * @expectedException \x2ts\validator\ValidatorException
     */
    public function testStringNotMatch() {
        T::validator($this->testData)
            ->str('mobile')
            ->notMatch('#^\d+$#')
            ->onErrorReport('error')
            ->validate();
    }

    public function testStringStartWith() {
        $vars = T::validator($this->testData)
            ->str('mobile')
            ->startWith('138')
            ->validate()
            ->safeVar;
        static::assertEquals($this->testData['mobile'], $vars['mobile']);
    }

    /**
     * @expectedException \x2ts\validator\ValidatorException
     */
    public function testStringNotStartWith() {
        T::validator($this->testData)
            ->str('mobile')
            ->startWith('130')
            ->validate();
    }

    public function testStringCallback() {
        T::validator($this->testData)
            ->str('string')
            ->callback(function ($var) {
                static::assertEquals($this->testData['string'], $var);
                return true;
            })
            ->validate();
    }

    public function testStringStartWithCI() {
        $vars = T::validator($this->testData)
            ->str('string')
            ->startWithCI('HELLO')
            ->validate()
            ->safeVar;
        static::assertEquals($this->testData['string'], $vars['string']);
    }

    /**
     * @expectedException \x2ts\validator\ValidatorException
     */
    public function testStringStartWithCIFailed() {
        T::validator($this->testData)
            ->str('string')
            ->startWithCI('world')
            ->validate();
    }

    /**
     * @param bool   $isValid
     * @param string $str
     * @param string $end
     *
     * @dataProvider dataForStringEndWith
     */
    public function testStringEndWith(bool $isValid, string $str, string $end) {
        try {
            (new StringValidator($str))
                ->endWith($end)
                ->validate();
            static::assertTrue($isValid);
        } catch (ValidatorException $e) {
            static::assertFalse($isValid);
        }
    }

    public function dataForStringEndWith() {
        return [
            [true, 'hello, world!', 'rld!'],
            [false, 'hello, world!', 'Rld!'],
        ];
    }

    /**
     * @param bool   $isValid
     * @param string $str
     * @param string $end
     *
     * @dataProvider dataForStringEndWithCIMore
     */
    public function testStringEndWithCIMore(bool $isValid, string $str, string $end) {
        try {
            (new StringValidator($str))
                ->endWithCI($end)
                ->validate();
            static::assertTrue($isValid);
        } catch (ValidatorException $e) {
            static::assertFalse($isValid);
        }
    }

    public function dataForStringEndWithCIMore() {
        return [
            [true, 'Good morning', 'MorNing'],
            [false, 'Good morning', 'good'],
        ];
    }

    public function testStringEndWithCI() {
        $vars = T::validator($this->testData)
            ->str('string')
            ->endWithCI('WORld!')
            ->validate()
            ->safeVar;
        static::assertEquals($this->testData['string'], $vars['string']);
    }

    /**
     * @expectedException \x2ts\validator\ValidatorException
     */
    public function testStringCallbackFailed() {
        T::validator($this->testData)
            ->str('string')
            ->callback(function () {
                return false;
            })->validate();
    }

    /** @noinspection MoreThanThreeArgumentsInspection
     * @param bool   $isValid
     * @param string $str
     * @param array  $enum
     * @param bool   $strict
     *
     * @dataProvider dataForStringInEnum
     */
    public function testStringInEnum(bool $isValid, string $str, array $enum, bool $strict) {
        try {
            (new StringValidator($str))
                ->inEnum($enum, $strict)
                ->validate();
            self::assertTrue($isValid);
        } catch (ValidatorException $e) {
            self::assertFalse($isValid);
        }
    }

    public function dataForStringInEnum() {
        return [
            [true, 'abc', ['abc', 'def'], true],
            [false, 'abc', ['ABC', 'def'], true],
            [true, '123', [123, 321], false],
            [false, '123', [123, 321], true],
        ];
    }

    /**
     * @expectedException \x2ts\validator\ValidatorException
     */
    public function testInvalidStringAlready() {
        T::validator($this->testData)
            ->str('zeroInt')
            ->contain('abc')
            ->containCI('ABC')
            ->match('/^$/')
            ->notMatch('/#\@#/')
            ->startWith('great')
            ->startWithCI('Great')
            ->endWith('Die')
            ->endWithCI('Die')
            ->callback(function () { return false; })
            ->inEnum(['abc', 'def'])
            ->length(18)
            ->validate();
    }

    public function testTwoErrorMessages() {
        try {
            T::validator($this->testData)
                ->str('mobile')
                ->contain('hello')
                ->onErrorReport('errorMobile')
                ->str('string')
                ->length(100)
                ->validate();
        } catch (ValidatorException $ex) {
            $messages = $ex->getMessages();
            static::assertEquals('errorMobile', $messages['mobile']);
            static::assertEquals('string is invalid', $messages['string']);
        }
    }

    /**
     * @param        $report
     * @param        $input
     * @param string $expect
     *
     * @dataProvider dataForInt
     */
    public function testInt($report, $input, $expect = null) {
        try {
            $var = (new IntegerValidator($input))
                ->onEmptyReport('empty')
                ->onErrorReport('error')
                ->validate()
                ->safeVar;
            static::assertFalse($report);
            static::assertSame($expect, $var);
        } catch (ValidatorException $ex) {
            static::assertEquals($report, $ex->getMessage());
        }
    }

    public function dataForInt() {
        return [
            [false, '123', 123],
            [false, 123, 123],
            [false, -123, -123],
            [false, 0xff, 255],
            [false, '-123', -123],
            [false, '0xff', 255],
            [false, '0xFF', 255],
            [false, '0', 0],
            [false, '0x0', 0],
            [false, '-0', 0],
            ['error', '0.0'],
            ['error', 'def'],
            ['error', '0xhh'],
            ['empty', ''],
            ['empty', null],
        ];
    }

    /**
     * @param      $report
     * @param      $input
     * @param null $expect
     *
     * @dataProvider dataForDec
     */
    public function testDec($report, $input, $expect = null) {
        try {
            $var = (new DecimalValidator($input))
                ->onEmptyReport('empty')
                ->onErrorReport('error')
                ->validate()
                ->safeVar;
            static::assertFalse($report, "The input: $input");
            static::assertSame($expect, $var, "The input: $input");
        } catch (ValidatorException $ex) {
            static::assertEquals($report, $ex->getMessage(), "The input: $input");
        }
    }

    public function dataForDec() {
        return [
            [false, 123, 123],
            [false, '123', 123],
            [false, -123, -123],
            [false, '-123', -123],
            [false, 0x12, 0x12],
            [false, 0, 0],
            [false, '0', 0],
            [false, '-0', 0],
            ['empty', ''],
            ['empty', null],
            ['error', 'abc'],
            ['error', '0x12'],
            ['error', '123abc'],
            ['error', '123great'],
            ['error', '0xhh'],
        ];
    }

    /**
     * @param      $report
     * @param      $input
     * @param null $expect
     *
     * @dataProvider dataForHex
     */
    public function testHex($report, $input, $expect = null) {
        try {
            $var = (new HexadecimalValidator($input))
                ->onEmptyReport('empty')
                ->onErrorReport('error')
                ->validate()
                ->safeVar;
            static::assertFalse($report);
            static::assertSame($expect, $var);
        } catch (ValidatorException $ex) {
            static::assertEquals($report, $ex->getMessage());
        }
    }

    public function dataForHex() {
        return [
            [false, 123, 123],
            [false, '123', 0x123],
            [false, -123, -123],
            [false, '-123', -0x123],
            [false, 0x12, 0x12],
            [false, 0, 0],
            [false, '0', 0],
            [false, '-0', 0],
            [false, 'abc', 0xabc],
            ['empty', ''],
            ['empty', null],
            [false, '123abc', 0x123abc],
            ['error', '123great'],
            ['error', '0xhh'],
            ['error', '-0x123'],
        ];
    }

    public function testOnUndefinedIgnore() {
        $var = T::validator($this->testData)
            ->str('notExistStr')
            ->onUndefinedIgnore()
            ->onEmptySet('welcome')
            ->str('null')
            ->onUndefinedIgnore()
            ->onEmptySet('')
            ->validate()
            ->safeVar;
        static::assertArrayNotHasKey('notExistStr', $var);
        static::assertArrayHasKey('null', $var);
    }

    public function testOnEmptyIgnore() {
        $var = T::validator($this->testData)
            ->str('null')
            ->onEmptyIgnore()
            ->str('emptyStr')
            ->onEmptyIgnore()
            ->arr('emptyArr')
            ->onEmptyIgnore()
            ->validate()
            ->safeVar;
        static::assertArrayNotHasKey('null', $var);
        static::assertArrayNotHasKey('emptyStr', $var);
        static::assertArrayNotHasKey('emptyArr', $var);
    }
}
