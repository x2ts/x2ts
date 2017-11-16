<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 15/8/18
 * Time: 下午3:34
 */

namespace x2ts\db\orm;


use x2ts\ComponentFactory as X;

class HasOneRelation extends Relation {
    /** @noinspection MoreThanThreeArgumentsInspection
     * @param Model  $model
     * @param string $condition [optional]
     * @param array  $params    [optional]
     * @param int    $offset    [optional]
     * @param int    $limit     [optional]
     *
     * @return null|Model
     */
    public function fetchRelated(
        Model $model,
        string $condition = '',
        array $params = [],
        $offset = null,
        $limit = null
    ) {
        X::logger()->trace("Relation load {$this->name}");
        $condition = $this->foreignTableField . '=:_fk' .
            ((null === $condition || '' === $condition) ?
                '' : " AND $condition");
        $params = array_merge($params, [
            ':_fk' => $model->properties[$this->property],
        ]);
        return Model::getInstance(
            [$this->foreignModelName],
            $model->conf,
            $model->confHash
        )->one($condition, $params);
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
     */
    public function batchLoadFor($models, $subWiths) {
        X::logger()->trace("Batch load relation models {$this->name}");
        if (count($models) === 0) {
            return;
        }

        $ids = [];
        foreach ($models as $model) {
            $val = $model->properties[$this->property];
            if (is_string($val)) {
                $ids[] = "'$val'";
            } else {
                $ids[] = $val;
            }
        }
        $idStr = implode(',', $ids);
        $model = reset($models);
        /** @var Model[] $foreignModels */
        $foreignModels = Model::getInstance([$this->foreignModelName], $model->conf, $model->confHash)
            ->with(...$subWiths)
            ->many($this->foreignTableField . " IN ($idStr)");
        $fMap = [];
        foreach ($foreignModels as $foreignModel) {
            $fMap[$foreignModel->properties[$this->foreignTableField]] = $foreignModel;
        }
        foreach ($models as $model) {
            $model->{$this->name} = $fMap[$model->properties[$this->property]] ?? null;
        }
    }
}