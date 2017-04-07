<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 15/7/26
 * Time: 上午12:07
 */

namespace x2ts\route\http;

/**
 * Class Response
 *
 * Binding to the http response to downstream
 *
 * @package x2ts\http
 */
class Response {
    /**
     * @var string
     */
    protected $_body = '';

    /**
     * @var array
     */
    protected $_header = [];

    protected $_cookie = [];

    /**
     * @param string $body
     */
    public function setBody($body) {
        $this->_body = $body;
    }

    /**
     * @return string
     */
    public function getBody() {
        return $this->_body;
    }

    /**
     * @param string $body
     */
    public function appendBody($body) {
        $this->_body .= $body;
    }

    /** @noinspection MoreThanThreeArgumentsInspection
     * @param string $name
     * @param string $value
     * @param bool $replace
     * @param int $status
     * @return void
     */
    public function setHeader($name, $value, $replace = true, $status = null) {
        $this->_header[] = ["$name: $value", $replace, $status];
    }

    /** @noinspection MoreThanThreeArgumentsInspection
     * @param $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @return void
     */
    public function setCookie($name, $value=null, $expire=null, $path=null, $domain=null, $secure=null, $httpOnly=null) {
        $this->_cookie[] = [$name, $value, $expire, $path, $domain, $secure, $httpOnly];
    }

    /**
     * Send the response
     */
    public function response() {
        if (count($this->_header)) {
            foreach ($this->_header as $header) {
                header(...$header);
            }
        }

        if (count($this->_cookie)) {
            foreach ($this->_cookie as $cookie) {
                setcookie(...$cookie);
            }
        }

        echo $this->_body;
    }
}