<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2016/7/28
 * Time: 下午10:40
 */

namespace x2ts\db\orm;


use x2ts\ComponentFactory as X;
use x2ts\MethodNotImplementException;

class ManyManyRelation extends Relation {
    public $relationTableName;

    public $relationTableFieldThis;

    public $relationTableFieldThat;

    /** @noinspection MoreThanThreeArgumentsInspection
     * @param Model  $model
     * @param string $condition [optional]
     * @param array  $params    [optional]
     * @param int    $offset    [optional]
     * @param int    $limit     [optional]
     *
     * @return array
     * @throws \x2ts\db\DataBaseException
     */
    public function fetchRelated(
        Model $model,
        string $condition = '',
        array $params = [],
        $offset = null,
        $limit = null
    ) {
        X::logger()->trace("Relation load {$this->name}");
        $condition = "`{$this->relationTableName}`.`{$this->relationTableFieldThis}`=:_fk" .
            (null === $condition || '' === $condition ?
                '' : " AND $condition");
        $params = array_merge(
            [':_fk' => $model->properties[$this->property]],
            $params
        );

        return Model::getInstance(
            [$this->foreignModelName],
            $model->conf,
            $model->confHash
        )->sql(
            <<<SQL
SELECT
  `{$this->foreignTableName}`.*
FROM
  `{$this->relationTableName}` INNER JOIN `{$this->foreignTableName}`
ON
  `{$this->relationTableName}`.`{$this->relationTableFieldThat}` = `{$this->foreignTableName}`.`{$this->foreignTableField}`
WHERE $condition
SQL
            ,
            $params
        );
    }

    /**
     * @param array $properties
     *
     * @return \x2ts\ICompilable
     */
    public static function __set_state($properties) {
        return new self($properties);
    }

    /**
     * @param Model[] $models
     * @param array   $subWiths
     *
     * @return void
     * @throws MethodNotImplementException
     */
    public function batchLoadFor($models, $subWiths) {
        throw new MethodNotImplementException('ManyManyRelation is unable to load with');
    }
}