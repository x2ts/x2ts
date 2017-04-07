<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2016/7/12
 * Time: 下午9:13
 */

namespace x2ts\validator;


class StringValidator extends Validator {
    /**
     * @param mixed $var
     * @param Validator $shell
     */
    public function __construct($var, $shell = null) {
        parent::__construct($var, $shell);
        if (!is_string($var)) {
            $this->_isValid = false;
        } else if ($this->conf['autoTrim']) {
            $this->_unsafeVar = trim($this->_unsafeVar);
        }
    }

    /**
     * check the length of the string in range of $min and $max
     * won't check the maximum length if $max is null
     *
     * @param int $min
     * @param null|int $max
     * @return $this
     */
    public function length($min, $max = null) {
        if (!$this->_isValid) {
            return $this;
        }
        $length = mb_strlen($this->_unsafeVar, $this->conf['encoding']);
        if ($length < $min || (!is_null($max) && $length > $max)) {
            $this->_isValid = false;
        }
        return $this;
    }

    /**
     * The wrapped var MUST contains $needle.
     *
     * @param string $needle
     * @return $this
     */
    public function contain($needle) {
        if (!$this->_isValid) {
            return $this;
        }
        if (mb_strpos($this->_unsafeVar, $needle, null, $this->conf['encoding']) === false) {
            $this->_isValid = false;
        }
        return $this;
    }

    /**
     * The wrapped var MUST contains $needle, case-insensitive.
     *
     * @param string $needle
     * @return $this
     */
    public function containCI($needle) {
        if (!$this->_isValid) {
            return $this;
        }
        if (mb_stripos($this->_unsafeVar, $needle, null, $this->conf['encoding']) === false) {
            $this->_isValid = false;
        }
        return $this;
    }

    /**
     * The wrapped var MUST start with $needle.
     *
     * @param string $needle
     * @return $this
     */
    public function startWith($needle) {
        if (!$this->_isValid) {
            return $this;
        }
        if (mb_strpos($this->_unsafeVar, $needle, null, $this->conf['encoding']) !== 0) {
            $this->_isValid = false;
        }
        return $this;
    }

    /**
     * The wrapped var MUST start with $needle, case-insensitive.
     *
     * @param string $needle
     * @return $this
     */
    public function startWithCI($needle) {
        if (!$this->_isValid) {
            return $this;
        }
        if (mb_stripos($this->_unsafeVar, $needle, null, $this->conf['encoding']) !== 0) {
            $this->_isValid = false;
        }
        return $this;
    }

    /**
     * The wrapped var MUST end with $needle.
     *
     * @param string $needle
     * @return $this
     */
    public function endWith($needle) {
        if (!$this->_isValid) {
            return $this;
        }
        $rPos = mb_strrpos($this->_unsafeVar, $needle, null, $this->conf['encoding']);
        if ($rPos === false) {
            $this->_isValid = false;
        } else {
            $varLength = mb_strlen($this->_unsafeVar, $this->conf['encoding']);
            $needleLength = mb_strlen($needle, $this->conf['encoding']);
            if ($varLength != $rPos + $needleLength) {
                $this->_isValid = false;
            }
        }
        return $this;
    }

    /**
     * The wrapped var MUST end with $needle, case-insensitive.
     *
     * @param string $needle
     * @return $this
     */
    public function endWithCI($needle) {
        if (!$this->_isValid) {
            return $this;
        }
        $rPos = mb_strripos($this->_unsafeVar, $needle, null, $this->conf['encoding']);
        if ($rPos === false) {
            $this->_isValid = false;
        } else {
            $varLength = mb_strlen($this->_unsafeVar, $this->conf['encoding']);
            $needleLength = mb_strlen($needle, $this->conf['encoding']);
            if ($varLength != $rPos + $needleLength) {
                $this->_isValid = false;
            }
        }
        return $this;
    }

    /**
     * The wrapped var MUST in the specified enum.
     *
     * @param array $enum
     * @param bool  $strict
     *
     * @return $this
     */
    public function inEnum($enum, $strict = true) {
        if (!$this->_isValid) {
            return $this;
        }
        if (!in_array($this->_unsafeVar, $enum, $strict)) {
            $this->_isValid = false;
        }
        return $this;
    }

    /**
     * The wrapped var MUST match the specified regular expression
     *
     * @param string $pattern
     * @return $this
     */
    public function match($pattern) {
        if (!$this->_isValid) {
            return $this;
        }
        if (!preg_match($pattern, $this->_unsafeVar)) {
            $this->_isValid = false;
        }
        return $this;
    }

    /**
     * The wrapped var MUST NOT match the specified regular expression
     *
     * @param string $pattern
     * @return $this
     */
    public function notMatch($pattern) {
        if (!$this->_isValid) {
            return $this;
        }
        if (preg_match($pattern, $this->_unsafeVar)) {
            $this->_isValid = false;
        }
        return $this;
    }

    /**
     * Pass the wrapped var to the callback $func and assign $isValid as its return value
     *
     * @param callable $func
     * @return $this
     */
    public function callback($func) {
        if (!$this->_isValid) {
            return $this;
        }
        $this->_isValid = (bool) $func($this->_unsafeVar);
        return $this;
    }
}
