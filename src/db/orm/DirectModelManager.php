<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2016/10/20
 * Time: 下午2:34
 */

namespace x2ts\db\orm;


final class DirectModelManager implements IModelManager {
    /**
     * @var Model
     */
    private $model;

    private static $instance;

    protected function __construct() { }

    /**
     * @param Model $model
     * @param array $conf
     *
     * @return DirectModelManager
     */
    public static function getInstance(Model $model, array $conf = []) {
        if (null === self::$instance) {
            self::$instance = new DirectModelManager();
        }
        self::$instance->model = $model;
        return self::$instance;
    }

    /**
     * @param int $scenario [optional]
     *
     * @return Model|null
     * @throws \x2ts\db\DataBaseException
     */
    public function save(int $scenario = Model::INSERT_NORMAL) {
        $pkName = $this->model->pkName;
        if ($this->model->isNewRecord) {
            $pk = 0;
            switch ($scenario) {
                case Model::INSERT_NORMAL:
                    $this->model->builder
                        ->insertInto($this->model->tableName)
                        ->columns($this->model->tableSchema->columnNames)
                        ->values($this->model->properties)
                        ->query();
                    $pk = $this->model->db->getLastInsertId();
                    if (empty($pk) && !empty($this->model->pk)) {
                        $pk = $this->model->pk;
                    }
                    break;
                case Model::INSERT_IGNORE:
                    $this->model->builder
                        ->insertIgnoreInto($this->model->tableName)
                        ->columns($this->model->tableSchema->columnNames)
                        ->values($this->model->properties)
                        ->query();
                    $pk = $this->model->db->getLastInsertId();
                    break;
                case Model::INSERT_UPDATE:
                    $this->model->builder
                        ->insertInto($this->model->tableName)
                        ->columns($this->model->tableSchema->columnNames)
                        ->values($this->model->properties)
                        ->onDupKeyUpdate($this->model->modified)
                        ->query();
                    $pk = $this->model->db->getLastInsertId();
                    break;
            }
            if ($pk) {
                $this->load($pk);
            }
        } else if (0 !== count($this->model->modified)) {
            $this->model->builder
                ->update($this->model->tableName)
                ->set($this->model->modified)
                ->where("`$pkName`=:_table_pk", array(
                    ':_table_pk' => $this->model->oldPK,
                ))->query();
            $this->load($this->model->pk);
        }
        return $this->model;
    }

    /**
     * @param int|string $pk
     *
     * @return null|Model
     * @throws \x2ts\db\DataBaseException
     */
    public function load($pk) {
        $r = $this->model->builder->select('*')
            ->from($this->model->tableName)
            ->where("`{$this->model->pkName}`=:pk")
            ->query(array(':pk' => $pk));
        if (count($r)) {
            return $this->model->setup($r[0]);
        }
        return null;
    }

    /** @noinspection MoreThanThreeArgumentsInspection
     * @param string   $condition
     * @param array    $params
     * @param null|int $offset
     * @param null|int $limit
     *
     * @return Model[]
     * @throws \x2ts\db\DataBaseException
     */
    public function many(string $condition = '', array $params = array(), $offset = null, $limit = null) {
        $this->model->builder
            ->select('*')
            ->from($this->model->tableName);

        if ($condition) {
            $this->model->builder->where($condition, $params);
        }

        if (null !== $offset) {
            if (null === $limit) {
                $this->model->builder->limit($offset);
            } else {
                $this->model->builder->limit($offset, $limit);
            }
        }
        $r = $this->model->builder->query();
        if (!is_array($r) || 0 === count($r)) {
            return [];
        }

        return $this->model->setup($r);
    }

    /**
     * @param string $condition
     * @param array  $params
     *
     * @return null|Model
     * @throws \x2ts\db\DataBaseException
     */
    public function one(string $condition = '', array $params = []) {
        $this->model->builder->select('*')
            ->from($this->model->tableName);
        if ($condition) {
            $this->model->builder->where($condition, $params);
        }
        $r = $this->model->builder->limit(1)
            ->query();
        if (!is_array($r) || 0 === count($r)) {
            return null;
        }
        return $this->model->setup($r[0]);
    }

    /**
     * @param string $sql
     * @param array  $params
     *
     * @return Model[]
     * @throws \x2ts\db\DataBaseException
     */
    public function sql(string $sql, array $params = []) {
        $r = $this->model->db->query($sql, $params);
        if (!is_array($r) || 0 === count($r)) {
            return [];
        }
        return $this->model->setup($r);
    }

    /**
     * @param string $condition
     * @param array  $params
     *
     * @return int|bool
     * @throws \x2ts\db\DataBaseException
     */
    public function count(string $condition = '', array $params = array()) {
        $this->model->builder->select('COUNT(*)')
            ->from($this->model->tableName);
        if ($condition) {
            $this->model->builder->where($condition, $params);
        }
        $r = $this->model->builder->query();
        if (!is_array($r) || 0 === count($r)) {
            return false;
        }
        return (int) reset($r[0]);
    }

    /**
     * @param int|string|null $pk
     *
     * @return int
     * @throws \x2ts\db\DataBaseException
     */
    public function remove($pk = null) {
        if (null === $pk) {
            $pk = $this->model->pk;
        }
        $this->model->builder
            ->delete()
            ->from($this->model->tableName)
            ->where("`{$this->model->pkName}`=:pk", array(':pk' => $pk,))
            ->query();
        return $this->model->db->getAffectedRows();
    }
}