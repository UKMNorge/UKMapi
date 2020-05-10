<?php

namespace UKMNorge\Slack\Block\Structure\Collection;

use UKMNorge\Slack\Block\Composition\Text;

class Fields extends Collection {
    public $maxlength = 10;
    public $type = 'sections';

    /**
     * {@inheritDoc}
     *
     * @param Text $text
     * @return self
     */
    public function add( Text $text ) {
        return parent::add($text);
    }

    /**
     * {@inheritdoc}
     *
     * @param Array<Text> $elements
     * @return self
     */
    public function set( Array $elements ) {
        return parent::set($elements);
    }

    /**
     * {@inheritDoc}
     *
     * @return Array<Text>
     */
    public function getAll() {
        return parent::getAll();
    }
}