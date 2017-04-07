<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/4/4
 * Time: PM7:30
 */

namespace x2ts\route\rule;

use x2ts\route\Action;

interface IRule {
    public function setConf(array $conf);

    public function getConf(): array;

    public function isMatch(string $uri): bool;

    public function fetchAction(): Action;

    public function fetchArguments(): array;
}