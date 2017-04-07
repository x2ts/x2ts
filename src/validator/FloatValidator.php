<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2016/7/12
 * Time: 下午8:31
 */

namespace x2ts\validator;


class FloatValidator extends NumberValidator {
    public function __construct($var, $shell = null) {
        parent::__construct($var, $shell);
        $this->_unsafeVar = $var;
        if ($shell instanceof Validator) {
            $this->shell = $shell;
        } else {
            $this->shell = $this;
        }
        if (!preg_match('/^-?\d*\.?\d+$/', $var) && !is_float($var)) {
            $this->_isValid = false;
        } else {
            $this->_unsafeVar = (float) $var;
        }
    }
}
