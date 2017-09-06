<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2016/7/12
 * Time: 下午7:17
 */

namespace x2ts;


interface IAssignable {
    /**
     * @param array|\Traversable $array
     *
     * @return $this
     */
    public function assign($array);
}