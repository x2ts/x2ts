<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2016/7/12
 * Time: 下午9:12
 */

namespace x2ts\validator;


abstract class NumberValidator extends Validator {
    /**
     * The wrapped var MUST greater than $num
     *
     * @param int|float $num
     * @param bool      $orEqual
     *
     * @return $this
     */
    public function gt($num, $orEqual = false) {
        if (!$this->_isValid) {
            return $this;
        }

        if ($this->_unsafeVar < $num) {
            $this->_isValid = false;
        } else if ($this->_unsafeVar == $num && !$orEqual) {
            $this->_isValid = false;
        }
        return $this;
    }

    /**
     * The wrapped var MUST less than $num
     *
     * @param int|float $num
     * @param bool      $orEqual
     *
     * @return $this
     */
    public function lt($num, $orEqual = false) {
        if (!$this->_isValid) {
            return $this;
        }

        if ($this->_unsafeVar > $num) {
            $this->_isValid = false;
        } else if ($this->_unsafeVar == $num && !$orEqual) {
            $this->_isValid = false;
        }
        return $this;
    }

    /**
     * The wrapped var MUST equal $num
     *
     * @param int|float $num
     *
     * @return $this
     */
    public function eq($num) {
        if (!$this->_isValid) {
            return $this;
        }

        if ($this->_unsafeVar != $num) {
            $this->_isValid = false;
        }
        return $this;
    }

    /**
     * The wrapped var MUST NOT equal $num
     *
     * @param int|float $num
     *
     * @return $this
     */
    public function ne($num) {
        if (!$this->_isValid) {
            return $this;
        }

        if ($this->_unsafeVar == $num) {
            $this->_isValid = false;
        }
        return $this;
    }

    /** @noinspection MoreThanThreeArgumentsInspection
     * The wrapped var MUST between $min and $max
     *
     * @param int|float $min
     * @param int|float $max
     * @param bool      $includeMin
     * @param bool      $includeMax
     *
     * @return $this
     */
    public function between($min, $max, $includeMin = true, $includeMax = true) {
        if (!$this->_isValid) {
            return $this;
        }

        if ($this->_unsafeVar < $min) {
            $this->_isValid = false;
        } else if ($this->_unsafeVar > $max) {
            $this->_isValid = false;
        } else if ($this->_unsafeVar == $min && !$includeMin) {
            $this->_isValid = false;
        } else if ($this->_unsafeVar == $max && !$includeMax) {
            $this->_isValid = false;
        }
        return $this;
    }

    /**
     * The wrapped var MUST in the specified enum
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
}
