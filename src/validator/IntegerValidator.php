<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2016/7/12
 * Time: 下午9:11
 */

namespace x2ts\validator;


class IntegerValidator extends NumberValidator {
    public function __construct($var, $shell = null) {
        parent::__construct($var, $shell);
        if ($shell instanceof Validator) {
            $this->shell = $shell;
        } else {
            $this->shell = $this;
        }
        if (is_int($var)) {
            $this->_unsafeVar = $var;
        } else if (ctype_digit($var)) {
            $this->_unsafeVar = (int) $var;
        } else if (@$var[0] === '-' && ctype_digit(substr($var, 1))) {
            $this->_unsafeVar = (int) $var;
        } else if (strpos($var, '0x') === 0 && ctype_xdigit($var = substr($var, 2))) {
            $this->_unsafeVar = intval($var, 16);
        } else {
            $this->_unsafeVar = $var;
            $this->_isValid = false;
        }
    }
}
