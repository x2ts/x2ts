<?php

namespace x2ts;

/**
 * Class Component
 *
 * @package x2ts
 * @property-read array $conf
 * @property-read string $confHash
 */
abstract class Component implements IComponent {
    use TGetterSetter;
    use TConfig;

    /**
     * @static
     * @var array
     */
    protected static $_conf;

    public function init() { }
}