<?php

namespace x2ts\route;

use ReflectionMethod;
use ReflectionParameter;
use Throwable;
use x2ts\ComponentFactory as X;
use x2ts\route\event\PostActionEvent;
use x2ts\route\event\PostResponseEvent;
use x2ts\route\event\PostRunEvent;
use x2ts\route\event\PreRunEvent;
use x2ts\route\http\Request;
use x2ts\route\http\Response;
use x2ts\route\rule\IRule;
use x2ts\TGetterSetter;
use x2ts\Toolkit;
use x2ts\view\IView;

/**
 * Class Action
 *
 * @package x2ts\app
 *
 * @property-read string $client_ip
 * @property-read string $current_url
 * @property-read string $current_uri
 * @property-read bool   $is_ajax
 * @property-read bool   $json_expected
 * @property-read bool   $is_https
 * @property-read IView  $view
 */
abstract class Action {
    use TGetterSetter;

    /**
     * @var Request @req
     */
    public $request;

    /**
     * @var Response $response
     */
    public $response;

    /**
     * @var string
     */
    public $suffix;

    /**
     * @var IRule
     */
    public $routeRule;

    private static function decodeUrlEncodedForm(string $input): array {
        if ($input !== '' && strpos($input, '=') === false) {
            X::logger()->warn("Invalid url encoded form string:\n$input");
            return [];
        }
        $form = [];
        $pairs = explode('&', $input);
        foreach ($pairs as $pair) {
            list($key, $value) = explode('=', $pair, 2);
            $form[urldecode($key)] = urldecode($value);
        }
        return $form;
    }

    /**
     * @param IRule $rule
     *
     * @return $this
     */
    public function setRouteRule(IRule $rule): Action {
        $this->routeRule = $rule;
        return $this;
    }

    public function init() { }

    private function _run(&$pArgs) {
        $method = Toolkit::toCamelCase('http ' . strtolower($this->server('REQUEST_METHOD', 'GET')));
        if (!method_exists($this, $method)) {
            if ($this->server('REQUEST_METHOD') === 'HEAD' && method_exists($this, 'httpGet')) {
                $method = 'httpGet';
            } else {
                $this->setStatus(405)->out('Method Not Allowed');
                return;
            }
        }
        $pArgsCount = count($pArgs);
        $rf = new ReflectionMethod($this, $method);
        if ($pArgsCount) {
            $requiredParamsNum = $rf->getNumberOfRequiredParameters();
            if ($requiredParamsNum <= $pArgsCount) {
                $rf->invokeArgs($this, $pArgs);
            } else {
                $this->setStatus(400)
                    ->out("Expecting $requiredParamsNum parameters while $pArgsCount given.");
                return;
            }
        } else {
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

    public function run(array $args = []) {
        X::logger()->trace('App Start: '
            . $this->server('REQUEST_METHOD') . ' ' . $this->server('REQUEST_URI')
        );
        try {
            try {
                X::bus()->dispatch(new PreRunEvent([
                    'dispatcher' => $this,
                    'action'     => $this,
                ]));
                $this->_run($args);
            } catch (ApplicationExitException $e) {
                if ($m = $e->getMessage()) {
                    X::logger()->trace('App end with message ' . $m . "\n" . $e->getTraceAsString());
                } else {
                    X::logger()->trace('App end without message' . "\n" . $e->getTraceAsString());
                }
            }
            X::bus()->dispatch(new PostRunEvent([
                'dispatcher' => $this,
                'action'     => $this,
            ]));
        } catch (Throwable $e) {
            X::logger()->error($e);
        }
        X::bus()->dispatch(new PostActionEvent([
            'dispatcher' => $this,
            'action'     => $this,
        ]));
        $this->response->response();
        X::bus()->dispatch(new PostResponseEvent([
            'dispatcher' => $this,
            'action'     => $this,
        ]));
        X::logger()->trace('App Exit: '
            . $this->server('REQUEST_METHOD') . ' ' . $this->server('REQUEST_URI')
        );
    }

    /**
     * @param callable $callback
     * @param null     $state
     *
     * @deprecated Since x2ts 2.8.0 Action do not dispatch PreActionEvent any more. Use PreRunEvent
     */
    public function onPreAction(callable $callback, $state = null) {
        $this->onPreRun($callback, $state);
    }

    public function onPreRun(callable $callback, $state = null) {
        X::bus()->on('x2ts.route.PreRun', $callback, $state);
        return $this;
    }

    public function onPostRun(callable $callback, $state = null) {
        X::bus()->on('x2ts.route.PostRun', $callback, $state);
        return $this;
    }

    public function onPostAction(callable $callback, $state = null) {
        X::bus()->on('x2ts.route.PostAction', $callback, $state);
        return $this;
    }

    public function onPostResponse(callable $callback, $state = null) {
        X::bus()->on('x2ts.route.PostResponse', $callback, $state);
        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function session($name = null, $default = null) {
        if (null === $name) {
            return $_SESSION;
        }
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        }
        return $default;
    }

    /**
     * Fetch the $name var from query string
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function query($name = null, $default = null) {
        return $this->request->query($name, $default);
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function post($name = null, $default = null) {
        return $this->request->post($name, $default);
    }

    /**
     * @param array $args
     *
     * @return $this
     * @internal param int $code
     * @internal param string $message
     * @internal param array $data
     * @internal param string $goto
     * @internal param string $tpl
     *
     */
    public function smartOutput(...$args) {
        if (count($args) === 1 && is_array($args[0])) {
            $code = $args[0]['code'] ?? 0;
            $message = $args[0]['message'] ?? null;
            $data = $args[0]['data'] ?? null;
            $goto = $args[0]['goto'] ?? null;
            $tpl = $args[0]['tpl'] ?? null;
        } else {
            $code = $args[0] ?? 0;
            $message = $args[1] ?? null;
            $data = $args[2] ?? null;
            $goto = $args[3] ?? null;
            $tpl = $args[4] ?? null;
        }
        if ($this->is_ajax || $this->json_expected) {
            X::logger()->trace('Output JSON');
            $this->jsonError($code, $message, $data, $goto);
        } else {
            if (!empty($goto)) {
                X::logger()->trace("Redirect to $goto");
                $this->redirect($goto);
            } else {
                if (null !== $message && !isset($data['message'])) {
                    $data['message'] = $message;
                }
                if ($tpl) {
                    X::logger()->trace("Display with tpl $tpl");
                    $this->display($tpl, $data);
                } else {
                    X::logger()->trace('Display');
                    $this->display($data);
                }
            }
        }
        return $this;
    }

    public function smartInput() {
        $contentType = strtolower($this->header('Content-Type', ''));
        if ($contentType === '') {
            return $this->query();
        }

        if ($this->server('REQUEST_METHOD') === 'POST' &&
            (strpos($contentType, 'application/x-www-form-urlencoded') === 0 ||
                strpos($contentType, 'multipart/form-data') === 0)
        ) {
            return $this->post();
        }

        if (strpos($contentType, 'application/x-www-form-urlencoded') === 0) {
            return self::decodeUrlEncodedForm($this->request->getRawContent());
        }

        if (strpos($contentType, 'application/json') === 0 ||
            strpos($contentType, 'text/json') === 0
        ) {
            return json_decode($this->request->getRawContent(), true) ?? [];
        }

        if ($this->server('REQUEST_METHOD') === 'PUT'
        ) {
            return $this->request->getRawContent();
        }

        throw new UnsupportedContentTypeException("The Content-Type $contentType has not been supported yet.");
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function header($name = null, $default = null) {
        return $this->request->header($name, $default);
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function cookie($name = null, $default = null) {
        return $this->request->cookie($name, $default);
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function server($name = null, $default = null) {
        return $this->request->server($name, $default);
    }

    /**
     * @param string $name
     * @param array  $default
     *
     * @return mixed
     */
    public function file($name = null, $default = null) {
        return $this->request->file($name, $default);
    }

    /** @noinspection MoreThanThreeArgumentsInspection
     * @param string $key
     * @param string $value
     * @param int    $expire
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param bool   $httpOnly
     *
     * @return $this
     */
    public function setCookie($key, $value = null, $expire = null, $path = null, $domain = null, $secure = null, $httpOnly = null) {
        $this->response->setCookie($key, $value, $expire, $path, $domain, $secure, $httpOnly);
        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     * @param bool   $replace
     * @param int    $status
     *
     * @return $this
     */
    public function setHeader(...$args) {
        if (is_array($args[0])) {
            $headers = array_shift($args);
            foreach ($headers as $key => $value) {
                $this->response->setHeader($key, $value, ...$args);
            }
        } else {
            $key = array_shift($args);
            $value = array_shift($args);
            $this->response->setHeader($key, $value, ...$args);
        }
        return $this;
    }

    public function setStatus($statusCode) {
        $this->response->setHeader('Status', $statusCode, true, $statusCode);
        return $this;
    }

    public function out($content, ...$args) {
        array_unshift($args, $content);
        foreach ($args as $arg) {
            $this->response->appendBody((string) $arg);
        }
        return $this;
    }

    public function jout($data, $options = 0, $depth = 512) {
        $this->response->setHeader('Content-Type', 'application/json');
        $this->response->setBody(json_encode($data, $options, $depth));
        return $this;
    }

    /**
     * @param mixed $var
     *
     * @return $this
     */
    public function dump($var) {
        ob_start();
        /** @noinspection ForgottenDebugOutputInspection */
        var_dump($var);
        $this->out('<pre>', ob_get_contents());
        ob_end_clean();
        return $this;
    }

    /**
     * @param string $msg
     * @param int    $logLevel
     *
     * @throws ApplicationExitException
     */
    public function end(string $msg = '', int $logLevel = X_LOG_DEBUG) {
        throw new ApplicationExitException($msg, $logLevel);
    }

    /**
     * @param string $msg
     * @param array  $data
     * @param string $goto
     *
     * @return $this
     */
    public function jsonEcho($msg = null, $data = null, $goto = null) {
        $this->jsonOutput(0, $msg, $data, $goto);
        return $this;
    }

    /** @noinspection MoreThanThreeArgumentsInspection
     * output a json encoded object to report an error
     *
     * @param int    $code
     * @param string $msg  The human readable error message
     * @param mixed  $data [optional]
     * @param string $goto [optional] The target url to redirect
     *
     * @return $this
     */
    public function jsonError($code, $msg = null, $data = null, $goto = null) {
        $this->jsonOutput($code, $msg, $data, $goto);
        return $this;
    }

    /** @noinspection MoreThanThreeArgumentsInspection
     * @param $code
     * @param $msg
     * @param $data
     * @param $goto
     */
    private function jsonOutput($code, $msg, $data, $goto) {
        $res = array('code' => $code);
        if (isset($msg))
            $res['message'] = (string) $msg;
        if (isset($data))
            $res['data'] = $data;
        if (!empty($goto))
            $res['goto'] = $goto;

        $this->setHeader('Content-Type', 'application/json')
            ->setHeader('Cache-Control', 'no-cache; must-revalidate')
            ->out(json_encode($res));
    }

    /**
     * Redirect user to the new url
     *
     * @param string $location
     * @param int    $statusCode
     *
     * @return $this
     */
    public function redirect($location, $statusCode = 302) {
        $this->response->setHeader('Location', $location, true, $statusCode);
        return $this;
    }

    /**
     * @var IView
     */
    private $_view;

    /**
     * @return IView
     */
    public function getView() {
        return $this->_view ?? ($this->_view = X::view());
    }

    /**
     * Set view layout
     *
     * @param string $layout
     *
     * @return $this
     */
    public function setLayout(string $layout) {
        /** @var IView $view */
        $this->view->setLayout($layout);
        return $this;
    }

    /**
     * Set page title
     *
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title) {
        $this->view->setPageTitle($title);
        return $this;
    }

    public function assign($key, $value) {
        $this->view->assign($key, $value);
        return $this;
    }

    /**
     * Render a template and returns html
     *
     * @param string $tpl
     * @param array  $params
     * @param string $cacheId
     *
     * @return string
     */
    public function render() {
        $args = func_get_args();
        if (isset($args[0]) && is_string($args[0])) {
            $tpl = array_shift($args);
        } else {
            $tpl = get_class($this);
            $paths = explode('\\', $tpl);
            array_shift($paths);
            $className = array_pop($paths);
            $actionSuffix = 'Action';
            if (substr($className, strrpos($className, $actionSuffix)) === $actionSuffix) {
                $basename = substr($className, 0, -strlen($actionSuffix));
            } else {
                $basename = $className;
            }
            array_push($paths, $basename);
            foreach ($paths as &$path) {
                $path = Toolkit::to_snake_case($path);
            }
            unset($path);
            $tpl = implode(DIRECTORY_SEPARATOR, $paths);
        }
        if (isset($args[0]) && is_array($args[0])) {
            $params = array_shift($args);
        } else {
            $params = [];
        }
        if (isset($args[0]) && is_string($args[0])) {
            $cacheId = array_shift($args);
        } else {
            $cacheId = '';
        }

        return $this->view->render($tpl, $params, $cacheId);
    }

    /**
     * Render a template and send html to response
     *
     * @param string $tpl
     * @param array  $params
     * @param string $cacheId
     *
     * @return $this
     */
    public function display() {
        return $this->out(call_user_func_array([$this, 'render'], func_get_args()));
    }

    public function isAjax() {
        return $this->getIsAjax();
    }

    public function getJsonExpected() {
        return $this->suffix === 'json' or
            (stripos($this->header('Accept', ''), 'json') !== false);
    }

    public function getIsAjax(): bool {
        return $this->query('ajax')
            or strtolower($this->header('X_REQUESTED_WITH')) === 'xmlhttprequest';
    }

    public function isHttps() {
        return $this->getIsHttps();
    }

    public function getIsHttps(): bool {
        return
            strtolower($this->header('X_FORWARDED_SSL')) === 'on' ||
            strtolower($this->header('X_FORWARDED_PROTO')) === 'https' ||
            strtolower($this->server('HTTPS')) === 'on';
    }

    /**
     * Gets the client side WAN ip address.
     *
     * @return string
     */
    public function getClientIp(): string {
        $s = $this->server();
        if (array_key_exists('HTTP_CLIENTIP', $s)) {
            if (!empty($s['HTTP_CLIENTIP'])) {
                return $s['HTTP_CLIENTIP'];
            }
        }
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $s) && !empty($s['HTTP_X_FORWARDED_FOR'])) {
            $addresses = explode(',', $s['HTTP_X_FORWARDED_FOR']);
            while (count($addresses)) {
                $ip = @trim(array_shift($addresses));
                if ($ip === '') {
                    continue;
                }
                if (!self::isLanIp($ip)) {
                    return $ip;
                }
            }
        }
        return $s['REMOTE_ADDR'];
    }

    public function getCurrentUrl(): string {
        return ($this->isHttps() ? 'https://' : 'http://') . $this->header('HOST') .
            $this->server('REQUEST_URI');
    }

    public function getCurrentUri(): string {
        return explode('?', $this->server('REQUEST_URI'), 2)[0];
    }

    private static function isLanIp(string $ipv4, bool $loopBack = false, bool $linkLocal = false): bool {
        if ($long = ip2long($ipv4))
            return
                $long & 0xff000000 === 0xa0000000 || // 10.0.0.0/8
                $long & 0xfff00000 === 0xac100000 || // 172.16.0.0/12
                $long & 0xffff0000 === 0xc0a80000 || // 192.168.0.0/16
                $loopBack &&
                $long & 0xff000000 === 0x7f000000 || // 127.0.0.0/8
                $linkLocal &&
                $long & 0xffff0000 === 0xa9fe0000;   // 169.254.0.0/16
        return false;
    }
}
