<?php

namespace x2ts\db;

use Exception;
use Throwable;

class DataBaseException extends Exception {
    /**
     * @var IDataBase
     */
    private $db;

    /**
     * DataBaseException constructor.
     *
     * @param string    $message
     * @param int       $code
     * @param Throwable $previous
     * @param IDataBase $db
     */
    public function __construct($message, $code, $previous = null, IDataBase $db) {
        parent::__construct($message, $code, $previous);
        $this->db = $db;
    }

    public function getDb(): IDataBase {
        return $this->db;
    }
}
