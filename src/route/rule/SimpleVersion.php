<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/4/15
 * Time: PM12:14
 */

namespace x2ts\route\rule;


use x2ts\ComponentFactory as X;
use x2ts\route\Action;
use x2ts\Toolkit;

class SimpleVersion implements IRule {
    protected $_conf = [
        'versionUri'   => '/api/v<VER>/',
        'emptyVersion' => 1,
    ];

    public function setConf(array $conf) {
        Toolkit::override($this->_conf, $conf);
    }

    public function getConf(): array {
        return $this->_conf;
    }

    protected $before = '';

    protected $ver = 1;

    protected $after = '';

    /**
     * @var \x2ts\route\rule\Simple
     */
    protected $simple;

    public function isMatch(string $uri): bool {
        $parts = explode('<VER>', ltrim($this->_conf['versionUri'], '/'), 2);
        $prefix = $parts[0];
        $suffix = $parts[1] ?? '';
        if (strpos($uri, $prefix) !== 0) {
            X::logger()->trace('Prefix not match: expect: ' . $prefix . ' given: ' . $uri);
            return false;
        };
        $ver = '';
        $restUri = substr($uri, strlen($prefix));
        for ($i = 0; ctype_digit($restUri[$i]); $i++) {
            $ver .= $restUri[$i];
        }
        $restUri = substr($restUri, $i);
        if ('' !== $suffix && strpos($restUri, $suffix) !== 0) {
            X::logger()->trace('Suffix not match: expect: ' . $suffix . 'given: ' . $restUri);
            return false;
        }
        $this->before = $prefix;
        $this->after = $restUri;
        $this->ver = (int) $ver;

        $simple = new Simple();
        for (; $this->ver > 0; $this->ver--) {
            $uri = $this->before .
                ($this->ver === $this->_conf['emptyVersion'] ? '' : $this->ver) .
                $this->after;
            if ($simple->isMatch($uri)) {
                X::logger()->trace("Matched version uri: $uri");
                $this->simple = $simple;
                return true;
            }
        }
        return false;
    }

    public function fetchAction(): Action {
        return $this->simple->fetchAction();
    }

    public function fetchArguments(): array {
        return $this->simple->fetchArguments();
    }
}