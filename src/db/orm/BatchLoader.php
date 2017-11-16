<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/11/15
 * Time: 下午12:30
 */

namespace x2ts\db\orm;


interface BatchLoader {
    /**
     * @param Model[] $models
     * @param array   $subWiths
     *
     * @return void
     */
    public function batchLoadFor($models, $subWiths);

    /**
     * @return string
     */
    public function name(): string;
}