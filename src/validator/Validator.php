<?php
/**
 * xts
 * File: apple.php
 * User: TingSong-Syu <rek@rek.me>
 * Date: 2014-08-05
 * Time: 01:42
 */

namespace x2ts\validator;

use x2ts\Component;
use x2ts\IAssignable;

/**
 * Class Validator
 *
 * @package xts
 *
 * @property-read bool  $isValid
 * @property-read bool  $isEmpty
 * @property-read array $messages
 * @property-read mixed $safeVar
 */
class Validator extends Component {
    protected static $_conf = array(
        'encoding' => 'UTF-8',
        'autoTrim' => true,
    );

    protected $validated = false;

    /**
     * @var bool
     */
    protected $_isValid = true;

    /**
     * @var bool
     */
    protected $_isEmpty = false;

    /**
     * @var bool
     */
    protected $_isUndefined = false;

    /**
     * the wrapped var
     *
     * @var mixed
     */
    protected $_unsafeVar = null;

    /**
     * contains the validated var
     *
     * @var mixed
     */
    protected $_safeVar = null;

    /**
     * @var bool
     */
    protected $onErrorSet = false;

    /**
     * @var mixed
     */
    protected $onErrorSetValue = null;

    /**
     * @var bool
     */
    protected $onEmptySet = false;

    /**
     * @var mixed
     */
    protected $onEmptySetValue = null;

    /**
     * @var bool
     */
    protected $onEmptyIgnore = false;

    /**
     * @var bool
     */
    protected $onUndefinedIgnore = false;

    /**
     * $shell always ref to the most outside Valley
     *
     * @var Validator
     */
    protected $shell = null;

    /**
     * Contains the sub valleys for each key.
     *
     * @var array
     */
    private $subValidators = array();

    /**
     * @var string
     */
    private $errorMessage = '';

    /**
     * @var string
     */
    private $emptyMessage = '';

    /**
     * @var string
     */
    private $message = '';

    /**
     * Contains the message reported by sub valleys
     *
     * @var array
     */
    private $_messages = array();

    /**
     * @param array     $var
     * @param Validator $shell
     */
    public function __construct($var, $shell = null) {
        $this->shell = $shell instanceof Validator ? $shell : $this;
        $this->_unsafeVar = $var;
    }

    /**
     * @param $key
     *
     * @return StringValidator
     */
    public function str($key) {
        $v = new StringValidator($this->shell->_unsafeVar[$key] ?? null, $this->shell);
        $v->_isUndefined = !array_key_exists($key, $this->shell->_unsafeVar);
        $this->shell->subValidators[$key] = $v;
        return $v;
    }

    /**
     * @param $key
     *
     * @return DateValidator
     */
    public function date($key) {
        $v = new DateValidator($this->shell->_unsafeVar[$key] ?? null, $this->shell);
        $v->_isUndefined = !array_key_exists($key, $this->shell->_unsafeVar);
        $this->shell->subValidators[$key] = $v;
        return $v;
    }

    /**
     * @param string $key
     *
     * @return EmailValidator
     */
    public function email($key) {
        $v = new EmailValidator($this->shell->_unsafeVar[$key] ?? null, $this->shell);
        $v->_isUndefined = !array_key_exists($key, $this->shell->_unsafeVar);
        $this->shell->subValidators[$key] = $v;
        return $v;
    }

    /**
     * @param string $key
     *
     * @return UrlValidator
     */
    public function url($key) {
        $v = new UrlValidator($this->shell->_unsafeVar[$key] ?? null, $this->shell);
        $v->_isUndefined = !array_key_exists($key, $this->shell->_unsafeVar);
        $this->shell->subValidators[$key] = $v;
        return $v;
    }

    /**
     * @param $key
     *
     * @return TelValidator
     */
    public function tel($key) {
        $v = new TelValidator($this->shell->_unsafeVar[$key] ?? null, $this->shell);
        $v->_isUndefined = !array_key_exists($key, $this->shell->_unsafeVar);
        $this->shell->subValidators[$key] = $v;
        return $v;
    }

    /**
     * @param $key
     *
     * @return FloatValidator
     */
    public function float($key) {
        $v = new FloatValidator($this->shell->_unsafeVar[$key] ?? null, $this->shell);
        $v->_isUndefined = !array_key_exists($key, $this->shell->_unsafeVar);
        $this->shell->subValidators[$key] = $v;
        return $v;
    }

    /**
     * @param $key
     *
     * @return IntegerValidator
     */
    public function int($key) {
        $v = new IntegerValidator($this->shell->_unsafeVar[$key] ?? null, $this->shell);
        $v->_isUndefined = !array_key_exists($key, $this->shell->_unsafeVar);
        $this->shell->subValidators[$key] = $v;
        return $v;
    }

    /**
     * @param $key
     *
     * @return DecimalValidator
     */
    public function dec($key) {
        $v = new DecimalValidator($this->shell->_unsafeVar[$key] ?? null, $this->shell);
        $v->_isUndefined = !array_key_exists($key, $this->shell->_unsafeVar);
        $this->shell->subValidators[$key] = $v;
        return $v;
    }

    /**
     * @param $key
     *
     * @return HexadecimalValidator
     */
    public function hex($key) {
        $v = new HexadecimalValidator($this->shell->_unsafeVar[$key] ?? null, $this->shell);
        $v->_isUndefined = !array_key_exists($key, $this->shell->_unsafeVar);
        $this->shell->subValidators[$key] = $v;
        return $v;
    }

    /**
     * @param string $key
     *
     * @return BooleanValidator
     */
    public function bool($key) {
        $v = new BooleanValidator($this->shell->_unsafeVar[$key] ?? null, $this->shell);
        $v->_isUndefined = !array_key_exists($key, $this->shell->_unsafeVar);
        $this->shell->subValidators[$key] = $v;
        return $v;
    }

    /**
     * @param string $key
     *
     * @return ArrayValidator
     */
    public function arr($key) {
        $v = new ArrayValidator($this->shell->_unsafeVar[$key] ?? null, $this->shell);
        $v->_isUndefined = !array_key_exists($key, $this->shell->_unsafeVar);
        $this->shell->subValidators[$key] = $v;
        return $v;
    }

    /**
     * Report $message if the wrapped var is invalid.
     *
     * @param string $message
     *
     * @return $this
     */
    public function onErrorReport($message) {
        $this->errorMessage = $message;
        return $this;
    }

    /**
     * Tell Valley to use $value instead of wrapped var if it's invalid.
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function onErrorSet($value) {
        $this->onErrorSet = true;
        $this->onErrorSetValue = $value;
        return $this;
    }

    /**
     * Report $message if the wrapped var is empty.
     *
     * @param string $message
     *
     * @return $this
     */
    public function onEmptyReport($message) {
        $this->emptyMessage = $message;
        return $this;
    }

    /**
     * Use $value when wrapped var is empty.
     * This method won't change the isValid property.
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function onEmptySet($value) {
        $this->onEmptySet = true;
        $this->onEmptySetValue = $value;
        return $this;
    }

    /**
     * Ignore the key if value is empty
     *
     * @return $this
     */
    public function onEmptyIgnore() {
        $this->onEmptyIgnore = true;
        return $this;
    }

    /**
     * Ignore the key if it's not exist in input data
     *
     * @return $this
     */
    public function onUndefinedIgnore() {
        $this->onUndefinedIgnore = true;
        return $this;
    }

    public function getIsValid() {
        return $this->_isValid;
    }

    public function getIsEmpty() {
        return $this->_isEmpty;
    }

    public function getMessages() {
        return $this->_messages;
    }

    public function getSafeVar() {
        return $this->_safeVar;
    }

    private function isEmpty($var) {
        return null === $var || '' === $var ||
        (is_array($var) && 0 === count($var));
    }

    /**
     * Start validate the valley itself
     *
     * @return void
     */
    protected function selfValidate() {
        if ($this->_isUndefined && $this->onUndefinedIgnore) {
            $this->_safeVar = null;
            $this->_isValid = true;
            goto finish;
        }
        if ($this->isEmpty($this->_unsafeVar)) {
            if ($this->onEmptyIgnore) {
                $this->_safeVar = null;
                $this->_isValid = true;
                $this->_isEmpty = true;
                goto finish;
            } else if ($this->onEmptySet) {
                $this->_safeVar = $this->onEmptySetValue;
                $this->_isValid = true;
                goto finish;
            } else if ($this->emptyMessage) {
                $this->_isValid = false;
                $this->message = $this->emptyMessage;
                $this->_isEmpty = true;
            } else {
                $this->_isEmpty = true;
            }
        }

        if (!$this->_isValid && $this->isEmpty($this->message)) {
            if ($this->onErrorSet) {
                $this->_safeVar = $this->onErrorSetValue;
                $this->_isValid = true;
            } else if ($this->errorMessage !== '') {
                $this->message = $this->errorMessage;
            }
        } else {
            $this->_safeVar = $this->_unsafeVar;
        }

        finish:
        $this->validated = true;
    }

    /**
     * Start validate the whole chain
     * include the sub valleys
     *
     * @param callable $onDataInvalid
     *
     * @return Validator
     * @throws \x2ts\validator\ValidatorException
     */
    final public function validate(callable $onDataInvalid = null):Validator {
        $shell = $this->shell;
        if (count($shell->subValidators)) {
            $shell->_safeVar = [];
            /**
             * @var Validator $validator
             * @var string    $key
             */
            foreach ($shell->subValidators as $key => $validator) {
                $validator->selfValidate();
                if ($validator->_isUndefined && $validator->onUndefinedIgnore) {
                    continue;
                }
                if ($validator->_isEmpty && $validator->onEmptyIgnore) {
                    continue;
                }
                if ($validator->_isValid) {
                    $shell->_safeVar[$key] = $validator->safeVar;
                } else if ($validator->message !== '') {
                    $shell->_messages[$key] = $validator->message;
                    $shell->_isValid = false;
                } else {
                    $shell->_messages[$key] = "$key is invalid";
                    $shell->_isValid = false;
                }
            }
            $shell->validated = true;
        } else {
            $this->selfValidate();
            if (!$this->_isValid) {
                $this->_messages[] = $this->message;
            }
        }
        if (!$shell->_isValid) {
            if (is_callable($onDataInvalid)) {
                $onDataInvalid($shell->messages, $shell);
            } else {
                throw new ValidatorException($shell->messages);
            }
        }

        return $shell;
    }

    /**
     * @param IAssignable $target
     * @param callable    $onDataInvalid
     *
     * @return IAssignable $target
     */
    final public function assignTo(
        IAssignable $target,
        callable $onDataInvalid = null
    ):IAssignable {
        $shell = $this->validated ? $this->shell : $this->validate($onDataInvalid);

        if ($shell->_isValid && is_array($shell->_safeVar)) {
            $target->assign($shell->_safeVar);
        }
        return $target;
    }
}
