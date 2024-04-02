<?php

namespace UKMNorge\Slack\Block\Structure\Collection;

use UKMNorge\Slack\Block\Element\ElementInterface;
use UKMNorge\Slack\Block\Structure\BlockInterface;

class Blocks extends Collection {
    public $maxlength = null;
    public $type = 'blocks';

    /**
     * {@inheritDoc}
     *
     * @param $element
     * @return self
     */
    public function add( $text ) {
        return parent::add($text);
    }

    /**
     * {@inheritdoc}
     *
     * @param Array<BlockInterface> $elements
     * @return self
     */
    public function set( Array $elements ) {
        return parent::set($elements);
    }

    /**
     * {@inheritDoc}
     *
     * @return Array<BlockInterface>
     */
    public function getAll() {
        return parent::getAll();
    }
}
