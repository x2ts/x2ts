<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2016/10/21
 * Time: 下午4:56
 */

namespace x2ts\db\orm;


interface IModelManager {
    /**
     * @param Model $model
     * @param array $conf
     *
     * @return IModelManager
     */
    public static function getInstance(Model $model, array $conf = []);

    /**
     * @param int|string $pk
     *
     * @return null|Model
     */
    public function load($pk);

    /**
     * @param int $scenario [optional]
     *
     * @return Model
     */
    public function save(int $scenario = Model::INSERT_NORMAL);

    /**
     * @param int|string $pk
     *
     * @return int
     */
    public function remove($pk = null);

    /** @noinspection MoreThanThreeArgumentsInspection
     * @param string   $condition
     * @param array    $params
     * @param null|int $offset
     * @param null|int $limit
     *
     * @return Model[]
     */
    public function many(string $condition = '', array $params = [], $offset = null, $limit = null);

    /**
     * @param string $condition
     * @param array  $params
     *
     * @return null|Model
     */
    public function one(string $condition = '', array $params = []);

    /**
     * @param string $sql
     * @param array  $params
     *
     * @return array
     * @throws \x2ts\db\DataBaseException
     */
    public function sql(string $sql, array $params = []);

    /**
     * @param string $condition
     * @param array  $params
     *
     * @return int|bool
     */
    public function count(string $condition = '', array $params = []);

}