<?php

namespace x2ts\db;

use PDO;
use PDOException;
use PDOStatement;
use x2ts\Component;
use x2ts\ExtensionNotLoadedException;
use x2ts\Toolkit;

define('MYSQL_ERR_DUP_ENTRY', 1062);
define('MYSQL_ERR_FK_PREVENT_DEL', 1451);
define('MYSQL_ERR_FK_PREVENT_INS', 1452);

/**
 * Class Query
 *
 * @package xts
 * @property-read PDO    $pdo
 * @property-read int    $affectedRows
 * @property-read int    $lastInsertId
 * @property-read string dbName
 */
class MySQL extends Component implements IDataBase {
    protected static $_conf = array(
        'host'               => 'localhost',
        'port'               => 3306,
        'user'               => 'root',
        'password'           => '',
        'dbname'             => 'test',
        'charset'            => 'utf8',
        'persistent'         => false,
        'mysqlCheckDuration' => 60,
    );

    public function init() {
        if (!extension_loaded('pdo')) {
            throw new ExtensionNotLoadedException('The x2ts\db\MySQL required extension pdo has not been loaded yet');
        }
        parent::init();
    }

    /**
     * @var PDO $_pdo
     */
    protected $_pdo;

    /**
     * @var int
     */
    private $mysqlLiveCheckTime;

    /**
     * @return PDO
     */
    public function getPdo(): PDO {
        if (!$this->_pdo instanceof PDO) {
            $this->initPdo();
            return $this->_pdo;
        }
        if (!$this->pdo->inTransaction() && $this->mysqlLiveCheckTime <= time()) {
            $st = $this->_pdo->query('SELECT 1;');
            $r = [[false]];
            if ($st instanceof PDOStatement) {
                $r = $st->fetchAll(PDO::FETCH_NUM);
            }
            if (!$r[0][0]) {
                Toolkit::log('MySQL has gone away, re-init it', X_LOG_WARNING);
                $this->initPdo();
            } else {
                $this->mysqlLiveCheckTime = time() + $this->conf['mysqlCheckDuration'];
            }
        }
        return $this->_pdo;
    }

    /**
     * @return string
     */
    public function getDbName() {
        return $this->conf['dbname'];
    }

    private function serializeArray($array) {
        $r = '';
        $first = true;
        foreach ($array as $key => $value) {
            if ($first)
                $first = false;
            else
                $r .= ',';
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
        Toolkit::trace("$sql with params " . $this->serializeArray($params));
        try {
            $st = $this->pdo->prepare($sql);
            if ($st === false) {
                $e = $this->pdo->errorInfo();
                throw new DataBaseException($e[2], $e[1], null, $this);
            }
            if ($st->execute($params)) {
                $this->_affectedRows = $st->rowCount();
                $this->_lastInsertId = $this->pdo->lastInsertId();
                return $st->fetchAll(PDO::FETCH_ASSOC);
            }

            $e = $st->errorInfo();
            throw new DataBaseException($e[2], $e[1], null, $this);
        } catch (PDOException $ex) {
            Toolkit::trace($ex);
            throw new DataBaseException($ex->getMessage(), $ex->getCode(), $ex, $this);
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

    private function initPdo() {
        $conf = $this->conf;
        $this->_pdo = new PDO (
            "mysql:host={$conf['host']};port={$conf['port']};dbname={$conf['dbname']};charset={$conf['charset']};",
            $conf['user'],
            $conf['password'],
            array(
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$conf['charset']}",
                PDO::ATTR_PERSISTENT         => $conf['persistent'],
            )
        );
        $this->mysqlLiveCheckTime = time() + $conf['mysqlCheckDuration'];
    }
}