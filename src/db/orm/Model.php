<?php

namespace x2ts\db\orm;

use ArrayAccess;
use BadMethodCallException;
use IteratorAggregate;
use JsonSerializable;
use x2ts\Component;
use x2ts\ComponentFactory as X;
use x2ts\db\IDataBase;
use x2ts\db\MySQL;
use x2ts\db\SqlBuilder;
use x2ts\IAssignable;
use x2ts\MethodNotImplementException;
use x2ts\Toolkit;
use TypeError;


/**
 * Class Model
 *
 * @package x2ts
 * @property array              $modified
 * @property-read array         $properties
 * @property-read TableSchema   $tableSchema
 * @property-read MySQL         $db
 * @property-read string        $modelName
 * @property-read bool          $isNewRecord
 * @property-read mixed         $oldPK
 * @property-read mixed         $pk
 * @property-read string        $pkName
 * @property-read string        $tableName
 * @property-read Relation[]    $relations
 * @property-read SqlBuilder    $builder
 * @property-read IModelManager $modelManager
 */
class Model extends Component implements
    ArrayAccess,
    IteratorAggregate,
    JsonSerializable,
    IAssignable {
    const INSERT_NORMAL = 0;
    const INSERT_IGNORE = 1;
    const INSERT_UPDATE = 2;

    /**
     * @var mixed
     */
    protected $_oldPK;

    /**
     * @var array
     */
    protected $_properties = [];

    /**
     * @var array
     */
    protected $_modified = [];

    /**
     * @var array
     */
    protected $_relationObjects = [];

    /**
     * @var TableSchema
     */
    protected $_tableSchema;

    /**
     * @var string
     */
    protected $_modelName;

    /**
     * @var string
     */
    protected $_tableName;

    /**
     * @var array
     */
    public static $_conf = array(
        'namespace'   => 'model',
        'tablePrefix' => '',
        'dbId'        => 'db',
        'schemaConf'  => [
            'schemaCacheId'       => 'cc',
            'useSchemaCache'      => false,
            'schemaCacheDuration' => 0,
        ],
        'manager'     => [
            'class' => DirectModelManager::class,
            'conf'  => [],
        ],
    );

    /**
     * @var SqlBuilder
     */
    private $_builder;

    public function getSqlBuilder() {
        return $this->_builder;
    }

    public function __sleep() {
        return ['_modelName', '_properties', '_modified', '_oldPK', '_confHash'];
    }

    public function __wakeup() {
        $this->_builder = new SqlBuilder($this->db);
    }

    /**
     * @param array  $args
     * @param array  $conf
     * @param string $confHash
     *
     * @return Model
     */
    public static function getInstance(array $args, array $conf, string $confHash) {
        @list($modelName) = $args;
        $namespace = $conf['namespace'] ?? static::$_conf['namespace'];
        $className = "\\{$namespace}\\" . Toolkit::toCamelCase($modelName, true);
        /** @var Model $model */
        $model = class_exists($className) ? new $className($modelName) : new Model($modelName);
        $model->saveConf($conf, $confHash);
        $model->init();

        return $model;
    }

    /**
     * @var \ReflectionMethod
     */
    protected $_reflectionMMGetter;

    public function getModelManager(): IModelManager {
        if (null === $this->_reflectionMMGetter) {
            $this->_reflectionMMGetter = (new \ReflectionClass($this->conf['manager']['class']))
                ->getMethod('getInstance');
        }
        return $this->_reflectionMMGetter->invoke(null, $this, $this->conf['manager']['conf']);
    }

    /**
     * @param int|string $pk
     *
     * @return null|Model
     */
    public function load($pk) {
        return $this->modelManager->load($pk);
    }

    /**
     * @param int $scenario [optional]
     *
     * @return $this
     */
    public function save(int $scenario = Model::INSERT_NORMAL) {
        $this->modelManager->save($scenario);
        return $this;
    }

    /**
     * @param int|string|null $pk
     *
     * @return int
     */
    public function remove($pk = null) {
        return $this->modelManager->remove($pk);
    }

    /**
     * @param string $condition
     * @param array  $params
     *
     * @return null|Model
     */
    public function one(string $condition = '', array $params = []) {
        return $this->modelManager->one($condition, $params);
    }

    protected $_with = [];

    public function with(...$args) {
        foreach ($args as $idx => $val) {
            if (is_array($val)) {
                $withOne = $val;
            } elseif ($val instanceof BatchLoader) {
                $withOne = [$val];
            } elseif (is_string($val)) {
                $withOne = explode('.', $val);
            } else {
                $traces = debug_backtrace();
                throw new \TypeError(sprintf(
                    'Argument %d passed to %s must be of the type %s, %s given, called in %s on line %d',
                    $idx + 1,
                    __METHOD__,
                    'string, array or x2ts\db\orm\BatchLoader',
                    gettype($val),
                    $traces[1]['file'],
                    $traces[1]['line']
                ));
            }
            $key = key($withOne);
            if ($key === 0) {
                $first = array_shift($withOne);
                $with = count($withOne) ? [$withOne] : [];
            } else {
                $withZero = explode('.', $key);
                $first = array_shift($withZero);
                if (count($withZero)) {
                    $last = array_pop($withZero);
                    $withZero[$last] = $withOne[$key];
                    $with = [$withZero];
                } else {
                    $with = [];
                    if (is_array($withOne[$key])) {
                        foreach ($withOne[$key] as $k => $v) {
                            if (is_int($k)) {
                                $with[] = $v;
                            } else {
                                $with[] = [$k => $v];
                            }
                        }
                    } else {
                        $with[] = $withOne[$key];
                    }
                }
            }
            unset($withOne);
            if ($first instanceof BatchLoader) {
                $name = $first->name();
                $loader = $first;
            } else {
                $name = $first;
                if (!isset($this->relations[$name])) {
                    throw new UnresolvableRelationException(sprintf(
                        'The relation named %s cannot be resolved in class %s',
                        $name,
                        get_class($this)
                    ));
                }
                $loader = $this->relations[$name];
            }

            if (!isset($this->_with[$name])) {
                $this->_with[$name] = [
                    'loader' => $loader,
                    'with'   => $with,
                ];
            } elseif (count($with)) {
                foreach ($with as $withOne) {
                    $this->_with[$name]['with'][] = $withOne;
                }
            }
        }
        return $this;
    }

    /** @noinspection MoreThanThreeArgumentsInspection
     * @param string $condition
     * @param array  $params
     * @param int    $offset
     * @param int    $limit
     *
     * @return array
     */
    public function many(string $condition = '', array $params = array(), $offset = null, $limit = null) {
        return $this->loadWiths($this->modelManager->many($condition, $params, $offset, $limit));
    }

    /**
     * @param array $models
     *
     * @return array
     */
    protected function loadWiths(array $models) {
        if ($models && $this->_with) {
            foreach ($this->_with as $w) {
                /** @var BatchLoader $loader */
                $loader = $w['loader'];
                $subWith = $w['with'];
                $loader->batchLoadFor($models, $subWith);
            }
            $this->_with = [];
        }
        return $models;
    }

    /**
     * @param string $condition
     * @param array  $params
     *
     * @return int|bool
     */
    public function count(string $condition = '', array $params = []) {
        return $this->modelManager->count($condition, $params);
    }

    /**
     * @param string $sql
     * @param array  $params
     *
     * @return array
     * @throws \x2ts\db\DataBaseException
     */
    public function sql(string $sql, array $params = []) {
        return $this->loadWiths($this->modelManager->sql($sql, $params));
    }

    /**
     * @param array $properties
     *
     * @return $this
     */
    protected function setupOne(array $properties) {
        $pkName = $this->pkName;
        /** @var Column $column */
        foreach ($this->tableSchema->columns as $column) {
            if (!array_key_exists($column->name, $properties)) {
                continue;
            }

            if (null !== $properties[$column->name]) {
                if ($column->isInt()) {
                    $this->_properties[$column->name] =
                        (int) $properties[$column->name];
                } else if ($column->isFloat()) {
                    $this->_properties[$column->name] =
                        (float) $properties[$column->name];
                } else {
                    $this->_properties[$column->name] =
                        $properties[$column->name];
                }
            } else {
                $this->_properties[$column->name] = null;
            }
        }
        $this->_modified = array();
        $this->_oldPK = $this->_properties[$pkName] ?? null;
        return $this;
    }

    public function clearCache() {
        if ($this->modelManager instanceof RedisCachedModelManager) {
            $this->modelManager->removeAllRelatedCache();
        }
        return $this;
    }

    /**
     * @param array $properties
     *
     * @return Model[]|Model
     */
    public function setup(array $properties) {
        if (is_array(reset($properties))) {
            $modelList = array();
            foreach ($properties as $p) {
                $o = clone $this;
                $o->setupOne($p);
                $modelList[] = $o;
            }
            return $modelList;
        }

        return $this->setupOne($properties);
    }

    /**
     * @return SqlBuilder
     */
    public function getBuilder() {
        return $this->_builder;
    }

    /**
     * @param string $modelName
     */
    protected function __construct($modelName = null) {
        $this->_modelName = $modelName ??
            Toolkit::toCamelCase(
                basename(str_replace('\\', '/', get_class($this))),
                true
            );
    }

    public function init() {
        $this->_builder = new SqlBuilder($this->db);
        $columns = $this->getTableSchema()->getColumns();
        foreach ($columns as $column) {
            $this->_properties[$column->name] = $column->defaultValue;
        }
        $this->_modified = array();
    }

    /**
     * @return string
     */
    public function getModelName() {
        return $this->_modelName;
    }

    /**
     * @return string
     */
    public function getTableName() {
        if (empty($this->_tableName)) {
            $this->_tableName = $this->conf['tablePrefix'] . Toolkit::to_snake_case($this->_modelName);
        }
        return $this->_tableName;
    }

    /**
     * @return array
     */
    public function getProperties() {
        return $this->_properties;
    }

    public function setProperties($array) {
        foreach ($array as $key => $value) {
            $this->_propertySet($key, $value);
        }
    }

    /**
     * @return array
     */
    public function getModified() {
        return $this->_modified;
    }

    /**
     * @param array $mod
     */
    public function setModified($mod) {
        $this->_modified = $mod;
    }

    /**
     * @return bool
     */
    public function getIsNewRecord() {
        return null === $this->_oldPK;
    }

    /**
     * @return mixed
     */
    public function getOldPK() {
        return $this->_oldPK;
    }

    /**
     * @return mixed
     */
    public function getPK() {
        return $this->_properties[$this->getPKName()];
    }

    public function getPKName() {
        return $this->tableSchema->keys['PK'];
    }

    public function getRelations() {
        return $this->tableSchema->relations;
    }

    /**
     * @throws MissingPrimaryKeyException
     * @return TableSchema
     */
    public function getTableSchema() {
        if (null === $this->_tableSchema) {
            /** @var TableSchema $schema */
            $this->_tableSchema = X::getInstance(
                MySQLTableSchema::class,
                [$this->tableName, $this->db],
                $this->conf['schemaConf'],
                $this->confHash . '_TableSchema'
            );
            $keys = $this->_tableSchema->getKeys();
            if (empty($keys['PK']))
                throw new MissingPrimaryKeyException("Table {$this->tableName} does not have the Primary Key. It cannot be initialized as an Model");
        }
        return $this->_tableSchema;
    }

    /**
     * @return IDataBase
     * @throws \x2ts\ComponentNotFoundException
     * @throws \InvalidArgumentException
     */
    protected function getDb() {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return X::getComponent($this->conf['dbId']);
    }

    public function __get($name) {
        $getter = Toolkit::toCamelCase("get $name");
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        if (array_key_exists($name, $this->_properties)) {
            return $this->_properties[$name];
        }

        if (array_key_exists($name, $this->relations)) {
            if (!array_key_exists($name, $this->_relationObjects)) {
                $this->_relationObjects[$name] = $this->loadRelationObj($name);
            }
            return $this->_relationObjects[$name];
        }
        return null;
    }

    public function __set($name, $value) {
        $setter = Toolkit::toCamelCase("set $name");
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } elseif (array_key_exists($name, $this->_properties)) {
            $this->_propertySet($name, $value);
        } elseif ($value instanceof Model &&
            ($relation = $this->relations[$name] ?? false) &&
            $value->modelName === $relation->foreignModelName
        ) {
            $this->_relationObjects[$name] = $value;
        }
    }

    public function __isset($name) {
        return array_key_exists($name, $this->_properties)
            || array_key_exists($name, $this->relations)
            || ($getter = Toolkit::toCamelCase("get $name")) && method_exists($this, $getter);
    }

    public function __call($name, $args) {
        if (array_key_exists($name, $this->relations)) {
            array_unshift($args, $name);
            return call_user_func_array(array($this, 'loadRelationObj'), $args);
        }
        throw new BadMethodCallException("Call to undefined method $name");
    }

    /** @noinspection MoreThanThreeArgumentsInspection
     * @param        $name
     * @param string $condition
     * @param array  $params
     * @param int    $offset
     * @param int    $limit
     *
     * @return Model|array|null
     */
    protected function loadRelationObj(
        string $name,
        string $condition = '',
        array $params = [],
        int $offset = 0,
        int $limit = 200
    ) {
        X::logger()->trace("Loading relation $name");
        if (array_key_exists($name, $this->relations)) {
            $relation = $this->relations[$name];
            return $relation->fetchRelated($this, $condition, $params, $offset, $limit);
        }
        return null;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     *
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset) {
        /** @noinspection ImplicitMagicMethodCallInspection */
        return $this->__isset($offset);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     *                      </p>
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset) {
        /** @noinspection ImplicitMagicMethodCallInspection */
        return $this->__get($offset);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     *                      </p>
     * @param mixed $value  <p>
     *                      The value to set.
     *                      </p>
     *
     * @return void
     */
    public function offsetSet($offset, $value) {
        /** @noinspection ImplicitMagicMethodCallInspection */
        $this->__set($offset, $value);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     *                      The offset to unset.
     *                      </p>
     *
     * @throws MethodNotImplementException
     * @return void
     */
    public function offsetUnset($offset) {
        throw new MethodNotImplementException("Model properties are maintained by database schema. You cannot unset any of them");
    }

    /**
     * @param array|\Traversable $props
     *
     * @return $this
     * @throws TypeError
     */
    public function assign($props) {
        if (!is_array($props) && !$props instanceof \Traversable) {
            throw new TypeError('Argument 1 passed to ' .
                __METHOD__
                . ' must be an instance of iterable, ' . gettype($props) . ' given');
        }
        if ($this->isNewRecord && !empty($props[$this->pkName])) {
            $this->load($props[$this->pkName]);
        }

        foreach ($props as $key => $value) {
            $this->$key = $value;
        }
        return $this;
    }

    /**
     * @param string $name  The name of the property to be set
     * @param mixed  $value The value of the property
     *
     * @return int|bool Returns the number of changed properties,
     *                  or false if $name is invalid
     */
    protected function _propertySet($name, $value) {
        if (array_key_exists($name, $this->_properties)) {
            if ($this->_properties[$name] !== $value) {
                $this->_properties[$name] = $value;
                $this->_modified[$name] = $value;
                return 1;
            }
            return 0;
        }
        return false;
    }

    public function getIterator() {
        $exported = $this->getExportProperties();
        foreach ($exported as $prop) {
            yield $prop => $this->$prop;
        }
    }

    protected static $_export = [];

    protected function getExportProperties() {
        $within = [];
        $without = [];
        $base = null;
        foreach (static::$_export as $act) {
            if ($act === 0) {
                $base = [];
                continue;
            }
            $act = trim($act);
            if ($act[0] === '+') {
                $within[] = trim(substr($act, 1));
            } elseif ($act[0] === '-') {
                $without[] = trim(substr($act, 1));
            } else {
                $within[] = $act;
            }
        }
        if (null === $base) {
            $base = array_keys($this->_properties);
        }

        return array_diff(array_merge($base, $within), $without);
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *       which is a value of any type other than a resource.
     */
    public function jsonSerialize() {
        $jsonArray = array();
        foreach ($this as $key => $value) {
            $jsonArray[$key] = $value;
        }
        return $jsonArray;
    }
}
