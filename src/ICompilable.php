<?php

namespace x2ts;

interface ICompilable {
    /**
     * @param array $properties
     *
     * @return $this
     */
    public static function __set_state($properties);
}
