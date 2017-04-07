<?php

namespace x2ts\db\orm;

use x2ts\ICompilable;
use x2ts\TGetterSetter;

/**
 * Class Column
 *
 * @package x2ts\db\orm
 *
 * @property mixed $defaultValue
 * @property string $phpType
 */
class Column implements ICompilable {
    use TGetterSetter;

    public $name = '';

    public $type = 'int';

    protected $defaultValue = '';

    public $canBeNull = false;

    public $isPK = false;

    public $isUQ = false;

    public $position = 0;

    public function __construct(array $array = []) {
        foreach ($array as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function isInt():bool {
        return in_array(
            $this->type,
            ['bigint', 'int', 'mediumint', 'smallint', 'tinyint']
            , true
        );
    }

    public function isFloat():bool {
        return in_array(
            $this->type,
            array('decimal', 'float', 'real', 'double'),
            true
        );
    }

    public function getDefaultValue() {
        return $this->defaultValue;
    }

    public function setDefaultValue($value) {
        if (null === $value) {
            $this->defaultValue = $this->canBeNull ? null : $this->nullToDefault();
        } else {
            $this->defaultValue = $this->valueToDefault($value);
        }
    }

    /**
     * @param array $properties
     *
     * @return \x2ts\ICompilable
     */
    public static function __set_state($properties) {
        return new self($properties);
    }

    private function nullToDefault() {
        switch ($this->type) {
            case 'varchar':
            case 'char':
            case 'text':
                return '';
            case 'date':
                return '1970-01-01';
            case 'datetime':
                return '1970-01-01 00:00:00';
            case 'time':
                return '00:00:00';
            case 'int':
            case 'bigint':
            case 'smallint':
            case 'tinyint':
                return 0;
            case 'float':
            case 'decimal':
                return 0.0;
            default:
                return '';
        }
    }

    private function valueToDefault($value) {
        switch ($this->type) {
            case 'varchar':
            case 'char':
            case 'text':
            case 'date':
            case 'datetime':
            case 'time':
                return (string) $value;
            case 'int':
            case 'bigint':
            case 'smallint':
            case 'tinyint':
            case 'bool':
                return (int) $value;
            case 'float':
            case 'decimal':
                return (float) $value;
            case 'timestamp':
                return 'CURRENT_TIMESTAMP' === $value ? null : $value;
            default:
                return $value;
        }
    }

    public function getPhpType() {
        switch ($this->type) {
            case 'int':
            case 'bigint':
            case 'smallint':
            case 'tinyint':
                return 'int';
            case 'float':
            case 'decimal':
                return 'float';
        }
        return 'string';
    }
}
