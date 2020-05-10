<?php

namespace UKMNorge\Slack\API\Response\Plugin\Filter;

abstract class Filter implements FilterInterface {
    public function getPriority() {
        return explode(' ', microtime())[0];
    }

    public function getType() {
        return static::TYPE;
    }

    public function isAsync() {
        return static::ASYNC;
    }
}