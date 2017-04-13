<?php

namespace x2ts\db;
/**
 * Class SqlBuilder
 *
 * @package x2ts\db
 */
class SqlBuilder {
    /**
     * @var string
     */
    protected $sql = '';

    /**
     * @var array
     */
    protected $params = array();

    /**
     * @var IDataBase
     */
    public $db = null;

    /**
     * @param IDataBase $db
     */
    public function __construct(IDataBase $db = null) {
        $this->db = $db;
    }

    /**
     * @param string   $column
     * @param string[] ...$columns [optional]
     *
     * @return SqlBuilder
     */
    public function select($column, ...$columns) {
        $this->params = [];
        if (count($columns)) {
            array_unshift($columns, $column);
            $columnStr = implode('`,`', $columns);
            $this->sql = "SELECT `{$columnStr}` ";
        } else {
            $this->sql = "SELECT {$column} ";
        }
        return $this;
    }

    /**
     * @param string $table
     *
     * @return SqlBuilder
     */
    public function update($table) {
        $this->sql = "UPDATE `$table` ";
        $this->params = [];
        return $this;
    }

    /**
     * @return SqlBuilder
     */
    public function delete() {
        $this->sql = 'DELETE ';
        $this->params = [];
        return $this;
    }

    /**
     * @param string $table
     *
     * @return SqlBuilder $this
     */
    public function insertInto($table) {
        $this->sql = "INSERT INTO `$table` ";
        $this->params = array();
        return $this;
    }

    /**
     * @param $table
     *
     * @return SqlBuilder $this
     */
    public function insertIgnoreInto($table) {
        $this->sql = "INSERT IGNORE INTO `$table` ";
        $this->params = array();
        return $this;
    }

    /**
     * @param string|array $columns
     *
     * @return SqlBuilder $this
     */
    public function columns($columns) {
        if (is_array($columns)) {
            $this->sql .= '(`' . implode('`, `', $columns) . '`) ';
        } else if (is_string($columns)) {
            $this->sql .= "($columns) ";
        }
        return $this;
    }

    /**
     * @param array $bindings
     *
     * @return SqlBuilder $this
     */
    public function values(array $bindings) {
        $references = array();
        foreach ($bindings as $k => $v) {
            $references[] = ":$k";
        }

        $this->sql .= 'VALUES (' . implode(', ', $references) . ') ';
        $this->params = array_merge($this->params, array_combine($references, array_values($bindings)));
        return $this;
    }

    /**
     * @param string $table
     *
     * @return SqlBuilder
     */
    public function from(string $table) {
        $this->sql .= " FROM `$table` ";
        return $this;
    }

    /**
     * @param string $condition
     * @param array  $params
     *
     * @return SqlBuilder
     */
    public function where(string $condition = '', array $params = []) {
        if ($condition) {
            $this->sql .= " WHERE $condition ";
            $this->params = array_merge($this->params, $params);
        }
        return $this;
    }

    /**
     * @param array|string $exp
     * @param array        $params
     *
     * @return $this
     */
    public function onDupKeyUpdate($exp, array $params = []) {
        if (is_string($exp)) {
            $this->sql .= " ON DUPLICATE KEY UPDATE $exp ";
        } elseif (is_array($exp)) {
            $columns = [];
            $params = [];
            foreach ($exp as $k => $v) {
                $columns[] = "`$k`=:$k";
                $params[":$k"] = $v;
            }
            $this->sql .= 'ON DUPLICATE KEY UPDATE ' . implode(', ', $columns);
        }
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    /**
     * @param int $offset
     * @param int $length
     *
     * @return SqlBuilder
     */
    public function limit(int $offset, int $length = 0) {
        if (0 === $length) {
            if (0 === $offset) {
                return $this;
            }
            $length = $offset;
            $offset = 0;
        }
        $this->sql .= "LIMIT $offset, $length";
        return $this;
    }

    /**
     * @param string|array $exp
     * @param array        $params
     *
     * @return SqlBuilder
     */
    public function set($exp, array $params = []) {
        if (is_string($exp)) {
            $this->sql .= " SET $exp ";
        } else if (is_array($exp)) {
            $columns = array();
            $params = array();
            foreach ($exp as $k => $v) {
                $columns[] = "`$k`=:$k";
                $params[":$k"] = $v;
            }
            $this->sql .= 'SET ' . implode(', ', $columns);
        }
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    /**
     * @param array $params
     *
     * @return array
     * @throws \x2ts\db\DataBaseException
     */
    public function query(array $params = []) {
        $this->params = array_merge($this->params, $params);
        return $this->db->query($this->sql, $this->params);
    }

    /**
     * @return array
     */
    public function export() {
        return [
            'sql'    => $this->sql,
            'params' => $this->params,
        ];
    }
}


