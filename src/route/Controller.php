<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/12/5
 * Time: 下午6:42
 */

namespace x2ts\route;

use ReflectionMethod;
use ReflectionParameter;
use Throwable;
use x2ts\ComponentFactory as X;
use x2ts\route\event\PostActionEvent;
use x2ts\route\event\PostRunEvent;
use x2ts\route\event\PreRunEvent;
use x2ts\Toolkit;

abstract class Controller extends Action {
    public function run(array $args = []) {
        X::logger()->trace('App Start: '
            . $this->server('REQUEST_METHOD') . ' '
            . $this->server('REQUEST_URI')
        );
        try {
            X::bus()->dispatch(new PreRunEvent([
                'dispatcher' => $this,
                'action'     => $this,
            ]));
            $this->_run($args[0] ?? '', $args[1] ?? '');
            X::bus()->dispatch(new PostRunEvent([
                'dispatcher' => $this,
                'action'     => $this,
            ]));
        } catch (ApplicationExitException $e) {
            if ($m = $e->getMessage()) {
                X::logger()->trace('App end with message ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            } else {
                X::logger()->trace('App end without message' . "\n" . $e->getTraceAsString());
            }
        } catch (Throwable $e) {
            X::logger()->error($e);
        }
        X::bus()->dispatch(new PostActionEvent([
            'dispatcher' => $this,
            'action'     => $this,
        ]));
        $this->response->response();
        X::logger()->trace('App Exit: '
            . $this->server('REQUEST_METHOD') . ' ' . $this->server('REQUEST_URI')
        );
    }

    /**
     * @param string $action
     * @param string $suffix
     */
    private function _run(string $action, string $suffix) {
        $method = Toolkit::toCamelCase(
            'http_' . $this->server('REQUEST_METHOD') . '_' . $action);
        if (!method_exists($this, $method)) {
            if ($this->server('REQUEST_METHOD') !== 'HEAD' ||
                !method_exists($this, $method = Toolkit::toCamelCase("http_get_$action"))) {
                $this->setStatus(404)->out('Not Found');
                return;
            }
        }
        $rf = new ReflectionMethod($this, $method);
        $params = $rf->getParameters();
        $args = [];
        foreach ($params as $param) {
            /** @var ReflectionParameter $param */
            $name = $param->getName();
            if (null !== $this->request->query($name)) {
                $args[] = $this->request->query($name);
            } else if ($param->isOptional()) {
                $args[] = $param->getDefaultValue();
            } else {
                $this->setStatus(400)->out("missing query parameter $name");
                return;
            }
        }
        $rf->invokeArgs($this, $args);
    }
}