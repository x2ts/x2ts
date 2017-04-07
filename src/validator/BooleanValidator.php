<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2016/7/12
 * Time: 下午8:19
 */

namespace x2ts\validator;

/**
 * Class BooleanValidator
 * @package x2ts\validator
 */
class BooleanValidator extends Validator {
    /**
     * BooleanValidator constructor.
     * @param mixed $var
     * @param Validator $shell
     */
    public function __construct($var, $shell = null) {
        parent::__construct($var, $shell);
        $this->_unsafeVar = filter_var($this->_unsafeVar, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }
}
