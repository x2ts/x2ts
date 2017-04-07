<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2016/7/12
 * Time: 下午9:12
 */

namespace x2ts\validator;


class UrlValidator extends StringValidator {
    public function __construct($var, $shell = null) {
        parent::__construct($var, $shell);
        if ($this->_isValid) {
            if (filter_var($this->_unsafeVar, FILTER_VALIDATE_URL) === false) {
                $this->_isValid = false;
            }
        }
    }
}
