<?php

namespace x2ts\route;

use x2ts\Component;
use x2ts\ComponentFactory as X;
use x2ts\route\event\PostRouteEvent;
use x2ts\route\event\PreRouteEvent;
use x2ts\route\http\Request;
use x2ts\route\http\Response;
use x2ts\route\rule\IRule;
use x2ts\Toolkit;

/**
 * Class Router
 *
 * @property IRule[]     $rules
 * @property-read Action $action
 * @package x2ts\route
 */
class Router extends Component {
    protected static $_conf = [
        'baseUri' => '',
        'rules'   => [],
    ];

    protected $action;

    public function init() {
        /** @var array $rules */
        $rules = $this->conf['rules'];
        foreach ($rules as $ruleClass => $ruleConf) {
            /** @var IRule $rule */
            $rule = new $ruleClass();
            $rule->setConf($ruleConf);
            $this->addRule($rule);
        }
    }

    /**
     * @var IRule[]
     */
    private $rules = [];

    public function addRule(IRule $rule) {
        $this->rules[] = $rule;
        return $this;
    }

    /**
     * @param Request  $req
     * @param Response $res
     *
     * @return bool
     */
    public function route($req, $res) {
        $uri = $req->server('REQUEST_URI');
        $preRouteEvent = new PreRouteEvent([
            'dispatcher' => $this,
            'uri'        => $uri,
        ]);
        X::bus()->dispatch($preRouteEvent);

        $subUri = ltrim(substr($uri, strlen($this->conf['baseUri'])), '/');
        foreach ($this->rules as $rule) {
            if ($rule->isMatch($subUri)) {
                $action = $rule->fetchAction();
                $args = $rule->fetchArguments();
                $action->request = $req;
                $action->response = $res;
                $this->action = $action;
                $postRoute = new PostRouteEvent([
                    'dispatcher' => $this,
                    'uri'        => $uri,
                    'action'     => $action,
                    'args'       => $args,
                ]);
                X::bus()->dispatch($postRoute);
                $action->init();
                $action->run($args);
                return true;
            }
        }

        $res->setHeader('Status', 404, true, 404);
        $res->setBody('Not Found');
        $res->response();
        Toolkit::trace("Action not found: $uri");
        return false;
    }

    public function onPreRoute(callable $callback, $state = null) {
        X::bus()->on('x2ts.route.PreRoute', $callback, $state);
        return $this;
    }

    public function onPostRoute(callable $callback, $state = null) {
        X::bus()->on('x2ts.route.PostRoute', $callback, $state);
        return $this;
    }

    /**
     * @return IRule[]
     */
    public function getRules(): array {
        return $this->rules;
    }

    /**
     * @param IRule[] $rules
     */
    public function setRules(array $rules) {
        $this->rules = $rules;
    }

    /**
     * @return Action
     */
    public function getAction() {
        return $this->action;
    }
}
