<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2016/7/12
 * Time: 下午8:18
 */

namespace x2ts\validator;

class ArrayValidator extends Validator {
    public function __construct($var, $shell = null) {
        parent::__construct($var, $shell);
        if (!is_array($var)) {
            $this->_isValid = false;
        }
    }

    /**
     * The length of wrapped array MUST between $min and $max
     *
     * @param int $min
     * @param int $max [optional]
     * @return $this
     */
    public function length($min, $max = null) {
        if (!$this->_isValid) {
            return $this;
        }
        $c = count($this->_unsafeVar);
        if ($c < $min || (null !== $max && $c > $max)) {
            $this->_isValid = false;
        }
        return $this;
    }
}
