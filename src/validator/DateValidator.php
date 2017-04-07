<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2016/7/12
 * Time: 下午9:13
 */

namespace x2ts\validator;


class DateValidator extends StringValidator {
    public function __construct($var, $shell = null) {
        parent::__construct($var, $shell);
        if ($this->_isValid) {
            $parsed = date_parse($this->_unsafeVar);
            if ($parsed === false) {
                $this->_isValid = false;
            } else if ($parsed['warning_count'] || $parsed['error_count']) {
                $this->_isValid = false;
            }
        }
    }
}
