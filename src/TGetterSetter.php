<?php

namespace x2ts;

trait TGetterSetter {
    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name) {
        $getter = Toolkit::toCamelCase("get $name");
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        return null;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return void
     */
    public function __set($name, $value) {
        $setter = Toolkit::toCamelCase("set $name");
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } else {
            $this->$name = $value;
        }
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function __isset($name) {
        return isset($this->$name) ||
            ($getter = Toolkit::toCamelCase("get $name")) && method_exists($this, $getter);
    }
}