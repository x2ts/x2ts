<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2016/7/13
 * Time: 下午12:00
 */

namespace x2ts\validator;


use Exception;

/**
 * Class ValidatorException
 *
 * @package x2ts\validator
 */
class ValidatorException extends Exception {
    /**
     * @var array
     */
    private $messages;

    public function __construct(array $messages) {
        $this->messages = $messages;
        $message = reset($messages);
        parent::__construct($message);
    }

    public function getMessages() {
        return $this->messages;
    }
}
