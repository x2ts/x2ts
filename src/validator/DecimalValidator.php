<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2016/7/12
 * Time: 下午9:11
 */

namespace x2ts\validator;

/**
 * Class DecimalValidator
 *
 * @package x2ts\validator
 */
class DecimalValidator extends IntegerValidator {
    /**
     * DecimalValidator constructor.
     *
     * @param mixed $var
     * @param null  $shell
     */
    public function __construct($var, $shell = null) {
        parent::__construct($var, $shell);

        if (is_int($var) || ctype_digit($var) ||
            (@$var[0] === '-' && ctype_digit(substr($var, 1)))
        ) {
            $this->_unsafeVar = (int) $var;
            $this->_isValid = true;
        } else {
            $this->_unsafeVar = $var;
            $this->_isValid = false;
        }
    }
}
