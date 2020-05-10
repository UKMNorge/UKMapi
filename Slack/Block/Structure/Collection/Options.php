<?php

namespace UKMNorge\Slack\Block\Structure\Collection;

use UKMNorge\Slack\Block\Structure\Exception;
use UKMNorge\Slack\Option;

class Options extends Collection
{
    public $type = 'options';


    public function __construct( Int $maxlength = null)
    {
        parent::__construct( is_null($maxlength) ? 0 : $maxlength);
    }
    /**
     * {@inheritDoc}
     *
     * @param Option 
     * @return self
     */
    public function add(Option $option)
    {
        return parent::add($option);
    }

    /**
     * {@inheritdoc}
     *
     * @param Array<Option> $options
     * @return self
     */
    public function set(array $options)
    {
        return parent::set($options);
    }

    /**
     * {@inheritDoc}
     *
     * @return Array<Option>
     */
    public function getAll()
    {
        return parent::getAll();
    }
}
