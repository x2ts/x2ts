<?php

namespace x2ts\db;

use PDO;
use PDOException;
use x2ts\Component;
use x2ts\ComponentFactory as X;

/**
 * Class Query
 *
 * @package x2ts\db
 * @property-read PDO $pdo
 * @property-read int $affectedRows
 * @property-read int $lastInsertId
 */
class SQLite extends Component implements IDataBase {
    protected static $_conf = array(
        'filename' => ':memory:',
    );

    /**
     * @var PDO $_pdo
     */
    protected $_pdo;

    /**
     * @return PDO
     */
    public function getPdo(): PDO {
        if (!$this->_pdo instanceof PDO) {
            $conf = $this->conf;
            $this->_pdo = new PDO ("sqlite:{$conf['filename']}");
        }
        return $this->_pdo;
    }

    private function serializeArray($array) {
        $r = '';
        $first = true;
        foreach ($array as $key => $value) {
            if ($first) {
                $first = false;
            } else {
                $r .= ',';
            }
            $r .= "$key=$value";
        }
        return $r;
    }

    /**
     * run sql and return the result
     *
     * @param string $sql
     * @param array  $params
     *
     * @throws DataBaseException
     * @return array
     */
    public function query(string $sql, array $params = []) {
        X::logger()->trace("$sql with params " . $this->serializeArray($params));
        try {
            $st = $this->pdo->prepare($sql);
            if ($st === false) {
                $e = $this->pdo->errorInfo();
                throw new DataBaseException($e[2], $e[1]);
            }
            if ($st->execute($params)) {
                $this->_affectedRows = $st->rowCount();
                $this->_lastInsertId = $this->pdo->lastInsertId();
                return $st->fetchAll(PDO::FETCH_ASSOC);
            }

            $e = $st->errorInfo();
            throw new DataBaseException($e[2], $e[1]);
        } catch (PDOException $ex) {
            X::logger()->trace($ex);
            throw new DataBaseException($ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * run sql and return the affect row num
     *
     * @param string $sql
     *
     * @return int
     */
    public function execute(string $sql) {
        return $this->pdo->exec($sql);
    }

    /**
     * @var int
     */
    protected $_lastInsertId = 0;

    /**
     * @return int
     */
    public function getLastInsertId() {
        return $this->_lastInsertId;
    }

    /**
     * @var int
     */
    protected $_affectedRows = 0;

    /**
     * @return int
     */
    public function getAffectedRows() {
        return $this->_affectedRows;
    }

    /**
     * @return boolean
     */
    public function startTransaction() {
        return $this->pdo->beginTransaction();
    }

    /**
     * @return boolean
     */
    public function commit() {
        return $this->pdo->commit();
    }

    /**
     * @return boolean
     */
    public function rollback() {
        return $this->pdo->rollBack();
    }

    /**
     * @return bool
     */
    public function inTransaction(): bool {
        return (bool) ($this->_pdo instanceof PDO && $this->_pdo->inTransaction());
    }
}