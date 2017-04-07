<?php

namespace x2ts\db\orm;

use x2ts\Component;
use x2ts\ComponentFactory as X;
use x2ts\db\IDataBase;
use x2ts\db\MySQL;


/**
 * Class Table
 *
 * @package xts
 * @property-read Column[]           $columns
 * @property-read string[]           $columnNames
 * @property-read array              $keys
 * @property-read Relation[]         $relations
 * @property-read string             $name
 * @property-read \x2ts\cache\ICache $cache
 */
abstract class TableSchema extends Component {
    protected static $_conf = array(
        'schemaCacheId'       => 'cc',
        'useSchemaCache'      => false,
        'schemaCacheDuration' => 0,
    );

    /**
     * @var boolean
     */
    public $useCache = false;

    /**
     * @var array
     */
    protected static $tables = array();

    /**
     * @var MySQL
     */
    protected $db;

    /**
     * @var string
     */
    protected $name = '';

    public function getName() {
        return $this->name;
    }

    /**
     * @return Column[]
     */
    public function getColumns() {
        return static::$tables[$this->db->dbName][$this->name]['columns'];
    }

    /**
     * @return array
     */
    public function getColumnNames() {
        return array_keys(static::$tables[$this->db->dbName][$this->name]['columns']);
    }

    /**
     * @return array
     */
    public function getKeys() {
        return static::$tables[$this->db->dbName][$this->name]['keys'];
    }

    /**
     * @return Relation[]
     */
    public function getRelations() {
        return static::$tables[$this->db->dbName][$this->name]['relations'];
    }

    public function getCache() {
        return X::getComponent($this->conf['schemaCacheId']);
    }

    public abstract function load();

    public function getHash() {
        return get_class($this->db) . '/' . $this->db->getDbName() . '/' . $this->name;
    }

    public function init() {
        if (!isset(static::$tables[$this->db->dbName][$this->name])) {
            if ($this->conf['useSchemaCache']) {
                $key = $this->getHash();
                $tableInfo = $this->cache->get($key);
                if (!empty($tableInfo)) {
                    static::$tables[$this->db->dbName][$this->name] = $tableInfo;
                    return;
                }
            }
            $this->load();
        }
    }

    /**
     * @param string    $name
     * @param IDataBase $db
     */
    public function __construct($name, IDataBase $db) {
        $this->name = $name;
        $this->db = $db;
    }
}
