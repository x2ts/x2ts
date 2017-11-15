<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2016/10/18
 * Time: 下午12:50
 */

namespace x2ts\db\orm;


use x2ts\ComponentFactory as X;
use x2ts\db\Redis;
use x2ts\Toolkit;

/**
 * Class RedisCachedModelManager
 *
 * @package x2ts\db\orm
 */
final class RedisCachedModelManager implements IModelManager {
    /**
     * @var Model
     */
    private $model;

    /**
     * @var array
     */
    private $conf = [
        'redisId'  => 'redis',
        'duration' => [
            'pool'  => 3600,
            'many'  => 300,
            'count' => 300,
            'one'   => 300,
        ],
    ];

    protected static $instance;

    /**
     * @param Model $model
     * @param array $conf
     *
     * @return RedisCachedModelManager
     */
    public static function getInstance(Model $model, array $conf = []) {
        if (null === self::$instance) {
            self::$instance = new RedisCachedModelManager();
        }
        self::$instance->model = $model;
        self::$instance->conf = [
            'redisId'  => 'redis',
            'duration' => [
                'pool'  => 3600,
                'many'  => 300,
                'one'   => 300,
                'count' => 300,
            ],
        ];
        Toolkit::override(self::$instance->conf, $conf);
        return self::$instance;
    }

    public function redis(): Redis {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return X::getComponent($this->conf['redisId']);
    }

    /**
     * @param int|string $pk
     *
     * @return null|Model
     */
    public function load($pk) {
        X::logger()->trace("Redis cached load $pk");
        $key = $this->getPoolKey($pk);
        /** @noinspection UnserializeExploitsInspection */
        $model = @unserialize($this->redis()->get($key));

        if ($model instanceof Model) {
            X::logger()->trace("Hit load $key");
            if ($model->isNewRecord || count($model->modified) > 0) {
                X::logger()->warn("The cache of {$model->tableName}-{$model->pk} is polluted");
                return $this->loadFromDb($pk);
            }
            return $this->model->setup($model->properties);
        }

        X::logger()->trace("Miss load $key");
        return $this->loadFromDb($pk);
    }

    /**
     * @param int $scenario [optional]
     *
     * @return Model
     * @throws \x2ts\db\DataBaseException
     */
    public function save(int $scenario = Model::INSERT_NORMAL) {
        X::logger()->trace('Redis cached save');
        $result = DirectModelManager::getInstance($this->model)
            ->save($scenario);
        $this->removeAllRelatedCache();
        return $result;
    }

    /**
     * @param int|string|null $pk
     *
     * @return int
     * @throws \x2ts\db\DataBaseException
     */
    public function remove($pk = null) {
        X::logger()->trace('Redis cached remove');
        $result = DirectModelManager::getInstance($this->model)->remove($pk);
        $this->removeAllRelatedCache();
        return $result;
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
        X::logger()->trace('Redis cached many');
        $key = $this->getManyKey($condition, $params, $offset, $limit);
        /** @var array $pks */
        /** @noinspection UnserializeExploitsInspection */
        $pks = @unserialize($this->redis()->get($key));
        if (is_array($pks)) {
            X::logger()->trace("Hit many $key");
            return $this->pks2models($pks);
        }

        X::logger()->trace("Miss many $key");
        return $this->manySet($key,
            DirectModelManager::getInstance($this->model)
                ->many($condition, $params, $offset, $limit)
        );
    }

    /**
     * @param string $condition
     * @param array  $params
     *
     * @return null|Model
     * @throws \x2ts\db\DataBaseException
     */
    public function one(string $condition = '', array $params = []) {
        X::logger()->trace('Redis cached one');
        $key = $this->getOneKey($condition, $params);
        $pk = $this->redis()->get($key);
        if ($pk) {
            X::logger()->trace("Hit one $key");
            return $this->load($pk);
        }

        X::logger()->trace("Miss one $key");
        $model = DirectModelManager::getInstance($this->model)
            ->one($condition, $params);
        if ($model) {
            $this->set(
                $key,
                $model->pk,
                $this->conf['duration']['one']
            );
            $this->poolSet($model);
        }
        X::logger()->trace("Saved one $key");
        return $model;
    }

    /**
     * @param string $sql
     * @param array  $params
     *
     * @return array
     * @throws \x2ts\db\DataBaseException
     */
    public function sql(string $sql, array $params = array()) {
        X::logger()->trace('Redis cached sql');
        $key = $this->getSqlKey($sql, $params);
        /** @noinspection UnserializeExploitsInspection */
        $pks = @unserialize($this->redis()->get($key));
        if ($pks) {
            X::logger()->trace("Hit sql $key");
            return $this->pks2models($pks);
        }

        X::logger()->trace("Miss sql $key");
        return $this->manySet($key,
            DirectModelManager::getInstance($this->model)
                ->sql($sql, $params)
        );
    }

    /**
     * @param string $condition
     * @param array  $params
     *
     * @return int
     * @throws \x2ts\db\DataBaseException
     */
    public function count(string $condition = '', array $params = array()) {
        X::logger()->trace('Redis cached count');
        $key = $this->getCountKey($condition, $params);
        $count = $this->redis()->get($key);
        if (!is_int($count) && !ctype_digit($count)) {
            X::logger()->trace("Miss count $key");
            $count = DirectModelManager::getInstance($this->model)
                ->count($condition, $params);
            $this->set(
                $key,
                $count,
                $this->conf['duration']['count']
            );
            X::logger()->trace("Saved count $key $count");
        } else {
            X::logger()->trace("Hit count $key $count");
            $count = (int) $count;
        }
        return $count;
    }

    protected function getPoolKey($pk = null) {
        if (null === $pk) {
            $pk = $this->model->pk;
        }
        return "rmc2:p:{$this->model->db->dbName}:{$this->model->tableName}:{$pk}";
    }

    /** @noinspection MoreThanThreeArgumentsInspection
     * @param string   $condition
     * @param array    $params
     * @param int|null $offset
     * @param int|null $limit
     *
     * @return string
     */
    protected function getManyKey(string $condition, array $params, $offset, $limit) {
        $p = serialize($params);
        $md5 = md5("$condition:$p:$offset:$limit");
        return "rmc2:m:{$this->model->db->dbName}:{$this->model->tableName}:{$md5}";
    }

    protected function getOneKey($condition, $params) {
        $p = serialize($params);
        $md5 = md5("$condition:$p");
        return "rmc2:o:{$this->model->db->dbName}:{$this->model->tableName}:{$md5}";
    }

    protected function getSqlKey($sql, $params) {
        $p = serialize($params);
        $md5 = md5("$sql:$p");
        return "rmc2:m:{$this->model->db->dbName}:{$this->model->tableName}:{$md5}";
    }

    protected function getCountKey($condition, $params) {
        $p = serialize($params);
        $md5 = md5("$condition:$p");
        return "rmc2:c:{$this->model->db->dbName}:{$this->model->tableName}:{$md5}";
    }

    /**
     * @param Model $model
     *
     * @return $this
     */
    private function poolSet(Model $model) {
        $key = $this->getPoolKey($model->pk);
        X::logger()->trace("Save $key to pool");
        $this->redis()->set(
            $key,
            serialize($model),
            $this->conf['duration']['pool']
        );
        X::logger()->trace("Saved pool $key");
        return $this;
    }

    /**
     * @param array $pks
     *
     * @return array
     */
    private function pks2models($pks): array {
        $pool_keys = array_map(function ($pk) {
            return $this->getPoolKey($pk);
        }, $pks);
        $serialized_models = $this->redis()->mget($pool_keys);
        $length = count($pks);
        $models = [];
        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < $length; $i++) {
            $pk = $pks[$i];
            $serialized_model = $serialized_models[$i];
            if (false === $serialized_model) {
                $loadedModel = $this->load($pk);
                if ($loadedModel instanceof Model) {
                    $model = clone $loadedModel;
                } else {
                    continue;
                }
            } else {
                /** @noinspection UnserializeExploitsInspection */
                $model = unserialize($serialized_models[$i]);
            }
            $models[] = $model;
        }
        return $models;
    }

    /**
     * @param string  $key
     *
     * @param Model[] $models
     *
     * @return Model[]
     */
    private function manySet($key, $models) {
        if (0 === count($models)) {
            return $models;
        }
        $pks = array_map(function (Model $model) {
            $this->poolSet($model);
            return $model->pk;
        }, $models);
        $this->set(
            $key,
            serialize($pks),
            $this->conf['duration']['many']
        );
        X::logger()->trace("Saved many $key");
        return $models;
    }

    private function loadFromDb($pk) {
        X::logger()->trace("Load from database $pk");
        if (DirectModelManager::getInstance($this->model)->load($pk)) {
            $this->poolSet($this->model);
            return $this->model;
        }
        return null;
    }

    private function set($key, $value, $duration = 0) {
        $this->redis()->sAdd($this->group(), $key);
        $this->redis()->set($key, $value, $duration);
    }

    private function group() {
        return "rmcg:{$this->model->db->dbName}:{$this->model->tableName}:";
    }

    public function removeAllRelatedCache() {
        $groupKey = $this->group();
        X::logger()->trace("Redis remove all related cache $groupKey");
        $keysInGroup = $this->redis()->sMembers($groupKey);
        if (!$keysInGroup) {
            $keysInGroup = [];
        }
        $num = $this->redis()->del($this->getPoolKey(), ...$keysInGroup);
        $this->redis()->del($groupKey);
        X::logger()->trace("$num keys removed");
        return $num;
    }
}