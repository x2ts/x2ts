<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 15/8/10
 * Time: 下午7:25
 */

namespace x2ts;

trait TConfig {
    private $_confHash;

    /**
     * @return array
     */
    public function getConf() {
        return Configuration::$configuration[$this->getConfHash()] ?? [];
    }

    public function getConfHash() {
        return $this->_confHash;
    }

    public function saveConf(array $conf, string $hash) {
        if (array_key_exists($hash, Configuration::$configuration)) {
            Toolkit::override(Configuration::$configuration[$hash], $conf);
        } else {
            $settings = [];
            Toolkit::override($settings, static::$_conf);
            Toolkit::override($settings, $conf);
            Configuration::$configuration[$hash] = $settings;
            $this->_confHash = $hash;
        }
    }
}