<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/4/4
 * Time: PM11:15
 */

namespace x2ts\route\rule;

use x2ts\route\Action;
use x2ts\TGetterSetter;
use x2ts\Toolkit;

/**
 * Class Simple
 *
 * @property array $conf
 * @package x2ts\route\rule
 */
class Simple implements IRule {
    use TGetterSetter;

    protected $_conf = [
        'default' => '/index',
    ];

    public function getConf(): array {
        return $this->_conf;
    }

    public function setConf(array $conf) {
        $this->_conf = $conf;
    }

    /**
     * @var Action
     */
    public $action;

    /**
     * @var array
     */
    public $args = [];

    /**
     * @param string $uri
     *
     * @return bool
     */
    public function isMatch(string $uri): bool {
        list($path) = explode('?', $uri, 2);
        if ($path === '') {
            $path = trim($this->conf['default'], '/');
        }
        /** @noinspection ReturnFalseInspection */
        $lastDotPos = strrpos($path, '.');
        $suffix = '';
        if (false !== $lastDotPos) {
            $suffix = substr($path, $lastDotPos + 1);
            $path = substr($path, 0, $lastDotPos);
        }
        $parts = explode('/', $path);
        $this->args = [];
        while (count($parts)) {
            $p = $parts;
            $lastPart =& $p[count($p) - 1];
            $lastPart = Toolkit::toCamelCase($lastPart, true);
            $className = 'action\\' . implode('\\', $p);
            $classNameAction = $className . 'Action';
            $this->action = null;
            if (class_exists($classNameAction)) {
                $this->action = new $classNameAction();
            } else if (class_exists($className)) {
                $this->action = new $className();
            }

            if ($this->action instanceof Action) {
                $this->action->suffix = $suffix;
                return true;
            }
            array_unshift($this->args, array_pop($parts));
        }

        return false;
    }

    public function fetchAction(): Action {
        return $this->action->setRouteRule($this);
    }

    public function fetchArguments(): array {
        return $this->args;
    }
}