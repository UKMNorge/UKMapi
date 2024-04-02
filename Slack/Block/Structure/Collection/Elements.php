<?php

namespace UKMNorge\Slack\Block\Structure\Collection;

use UKMNorge\Slack\Block\Element\ElementInterface;

class Elements extends Collection {
    public $maxlength = 5;
    public $type = 'actions';

    /**
     * {@inheritDoc}
     *
     * @param ElementInterface $element
     * @return self
     */
    public function add( $text ) {
        return parent::add($text);
    }

    /**
     * {@inheritdoc}
     *
     * @param Array<ElementInterface> $elements
     * @return self
     */
    public function set( Array $elements ) {
        return parent::set($elements);
    }

    /**
     * {@inheritDoc}
     *
     * @return Array<ElementInterface>
     */
    public function getAll() {
        return parent::getAll();
    }
}
