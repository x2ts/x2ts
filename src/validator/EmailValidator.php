<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2016/7/12
 * Time: 下午9:13
 */

namespace x2ts\validator;


class EmailValidator extends StringValidator {
    public function __construct($var, $shell = null) {
        parent::__construct($var, $shell);
        $this->_isValid = $this->_isValid &&
            filter_var($this->_unsafeVar, FILTER_VALIDATE_EMAIL) !== false;
    }
}
