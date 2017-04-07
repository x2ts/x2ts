<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 15/8/18
 * Time: 下午3:33
 */

namespace x2ts\db\orm;

use x2ts\Toolkit;

class HasManyRelation extends Relation {

    /** @noinspection MoreThanThreeArgumentsInspection
     * @param Model  $model
     * @param string $condition [optional]
     * @param array  $params    [optional]
     * @param int    $offset    [optional]
     * @param int    $limit     [optional]
     *
     * @return array
     */
    public function fetchRelated(
        Model $model,
        string $condition = '',
        array $params = [],
        $offset = null,
        $limit = null
    ) {
        Toolkit::trace("Relation load {$this->name}");
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
        )->many($condition, $params, $offset, $limit);
    }

    /**
     * @param array $properties
     *
     * @return \x2ts\ICompilable
     */
    public static function __set_state($properties) {
        return new self($properties);
    }
}