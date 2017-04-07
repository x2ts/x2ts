<?php

namespace x2ts\db\orm;

use x2ts\ICompilable;

abstract class Relation implements ICompilable {
    public $name;

    public $property;

    public $foreignModelName;

    public $foreignTableName;

    public $foreignTableField;

    public function __construct($array = []) {
        foreach ($array as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * @param array $properties
     *
     * @return \x2ts\ICompilable
     */
    abstract public static function __set_state($properties);

    /** @noinspection MoreThanThreeArgumentsInspection
     * @param Model  $model
     * @param string $condition [optional]
     * @param array  $params    [optional]
     * @param int    $offset    [optional]
     * @param int    $limit     [optional]
     *
     * @return array|Model|null
     */
    public abstract function fetchRelated(
        Model $model,
        string $condition = '',
        array $params = [],
        $offset = null,
        $limit = null
    );
}
