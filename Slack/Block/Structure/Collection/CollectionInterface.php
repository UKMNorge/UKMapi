<?php

namespace UKMNorge\Slack\Block\Structure\Collection;

interface CollectionInterface {
    public $type;
    public function add($element);
    public function getLength();
    public function getAll();
    public function export();
}
