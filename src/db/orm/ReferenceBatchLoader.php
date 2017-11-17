<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/11/17
 * Time: 上午12:41
 */

namespace x2ts\db\orm;

use x2ts\ComponentFactory as X;

class ReferenceBatchLoader implements BatchLoader {

    private $name;

    private $prop;

    private $model;

    public function __construct(string $name, string $refProp, Model $refModel) {
        $this->name = $name;
        $this->prop = $refProp;
        $this->model = $refModel;
    }

    /**
     * @param Model[] $models
     * @param array   $subWiths
     *
     * @return void
     */
    public function batchLoadFor($models, $subWiths) {
        if (count($models) === 0) {
            return;
        }
        $ids = [];
        foreach ($models as $model) {
            $id = $model->properties[$this->prop];
            if (is_string($id)) {
                $ids[] = "'$id'";
            } elseif (is_int($id)) {
                $ids[] = $id;
            } elseif ($id !== null) {
                X::logger()->warn('Unsupported reference type ' . gettype($id)
                    . ' of ' . get_class($model) . '->' . $this->prop
                    . ' (PK: ' . $model->pk . ')'
                );
            }
        }
        $idStr = implode(',', $ids);
        /** @var Model[] $foreignModels */
        $foreignModels = $this->model
            ->with(...$subWiths)
            ->many("`{$this->model->pkName}` IN ($idStr)");
        $map = [];
        foreach ($foreignModels as $foreignModel) {
            $map[$foreignModel->pk] = $foreignModel;
        }
        foreach ($models as $model) {
            $model->{$this->name} = $map[$model->properties[$this->prop]] ?? null;
        }
    }

    /**
     * @return string
     */
    public function name(): string {
        return $this->name;
    }
}