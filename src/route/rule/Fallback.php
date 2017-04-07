<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/4/5
 * Time: PM1:00
 */

namespace x2ts\route\rule;


use x2ts\route\Action;

class Fallback implements IRule {
    protected $_conf = [
        'fallbackClass' => 'action\\Error404Action',
    ];

    public function isMatch(string $uri): bool {
        return true;
    }

    public function fetchAction(): Action {
        $class = $this->_conf['fallbackClass'];
        return new $class();
    }

    public function fetchArguments(): array {
        return [];
    }

    /**
     * @return array
     */
    public function getConf(): array {
        return $this->_conf;
    }

    /**
     * @param array $conf
     */
    public function setConf(array $conf) {
        $this->_conf = $conf;
    }
}