<?php

namespace x2ts\view;

/**
 * Interface View
 *
 * @package x2ts
 * @property string       $_layout
 * @property-write string $pageTitle
 */
interface IView {
    /**
     * @param string $title
     *
     * @return $this
     */
    public function setPageTitle(string $title);

    /**
     * @param string $layout
     *
     * @return $this
     */
    public function setLayout(string $layout);

    /**
     * @return string
     */
    public function getLayout(): string;

    /**
     * @param string $tpl
     * @param array  $params  [optional]
     * @param string $cacheId [optional]
     *
     * @return string
     */
    public function render(string $tpl, array $params = [], string $cacheId = '');

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return $this
     */
    public function assign(string $name, $value);

    /**
     * @param array $params
     *
     * @return $this
     */
    public function assignAll(array $params);
}