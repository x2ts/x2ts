<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/12/5
 * Time: 下午6:45
 */

namespace x2ts\route\rule;


use x2ts\route\Action;
use x2ts\Toolkit;

/**
 * Class SimpleController
 *
 * @package x2ts\route\rule
 * @property-read array $conf
 */
class SimpleController implements IRule {
    protected $_conf = [
        'defaultController' => 'index',
        'defaultAction'     => 'index',
    ];

    public function getConf(): array {
        return $this->_conf;
    }

    public function setConf(array $conf) {
        $this->_conf = $conf;
    }

    protected $controller;

    protected $actionName;

    protected $suffix;

    public function isMatch(string $uri): bool {
        list($path) = explode('?', $uri, 2);
        $lastDotPos = strrpos($path, '.');
        $this->suffix = '';
        if (false !== $lastDotPos) {
            $this->suffix = substr($path, $lastDotPos + 1);
            $path = substr($path, 0, $lastDotPos);
        }
        $parts = explode('/', trim($path, '/'));
        if (count($parts) < 2) { // at least two parts: controller/action
            if ($this->conf['defaultController']) {
                $classFullName = '';
            }
            return false;
        }
        $this->actionName = array_pop($parts);
        $controllerName =& $parts[count($parts) - 1];
        $controllerName = Toolkit::toCamelCase($controllerName, true);;
        $classFullName = 'action\\' . implode('\\', $parts);
        if (class_exists($classFullName)) {
            $this->controller = new $classFullName();
        } elseif (($classFullName .= 'Controller') && class_exists($classFullName)) {
            $this->controller = new $classFullName();
        } else {
            return false;
        }
        return true;
    }

    public function fetchAction(): Action {
        return $this->controller;
    }

    public function fetchArguments(): array {
        return [$this->actionName, $this->suffix];
    }
}