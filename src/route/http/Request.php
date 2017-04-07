<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 15/7/25
 * Time: 下午2:19
 */

namespace x2ts\route\http;

use x2ts\TGetterSetter;

/**
 * Class Request
 *
 * Binding to the http request from downstream
 *
 * @package x2ts\http
 * @property-read array $query
 * @property-read array $cookie
 * @property-read array $post
 * @property-read array $header
 * @property-read array $server
 * @property-read array $file
 */
class Request {
    use TGetterSetter;

    /**
     * @var array
     */
    protected $_header = null;

    /**
     * @param string $name
     * @param string $default
     *
     * @return mixed
     */
    public function query($name = null, $default = null) {
        if (null === $name) {
            return $_GET;
        }
        return $_GET[$name] ?? $default;
    }

    /**
     * @param string $name
     * @param string $default
     *
     * @return mixed
     */
    public function cookie($name = null, $default = null) {
        if (null === $name) {
            return $_COOKIE;
        }
        return $_COOKIE[$name] ?? $default;
    }

    /**
     * @param string $name
     * @param string $default
     *
     * @return mixed
     */
    public function post($name = null, $default = null) {
        if (null === $name) {
            return $_POST;
        }
        return $_POST[$name] ?? $default;
    }

    /**
     * @param string $name
     * @param string $default
     *
     * @return mixed
     */
    public function header($name = null, $default = null) {
        if (null === $name) {
            return $this->getHeader();
        }
        $key = 'HTTP_' . str_replace('-', '_', strtoupper($name));
        return $_SERVER[$key] ?? $default;
    }

    /**
     * @param string $name
     * @param string $default
     *
     * @return mixed
     */
    public function file($name = null, $default = null) {
        if (null === $name) {
            return $_FILES;
        }
        return $_FILES[$name] ?? $default;
    }

    /**
     * @param string $name
     * @param string $default
     *
     * @return mixed
     */
    public function server($name = null, $default = null) {
        if (null === $name) {
            return $_SERVER;
        }
        return $_SERVER[$name] ?? $default;
    }

    /**
     * @return array
     */
    public function getQuery() {
        return $_GET;
    }

    /**
     * @return array
     */
    public function getCookie() {
        return $_COOKIE;
    }

    /**
     * @return array
     */
    public function getPost() {
        return $_POST;
    }

    /**
     * @return array
     */
    public function getHeader() {
        if (null === $this->_header) {
            $this->_header = [];
            foreach ($_SERVER as $key => $value) {
                if (stripos($key, 'HTTP_') === 0) {
                    $this->_header[substr($key, 5)] = $value;
                }
            }
        }
        return $this->_header;
    }

    /**
     * @return array
     */
    public function getServer() {
        return $_SERVER;
    }

    public function getRawContent() {
        return file_get_contents('php://input');
    }
}