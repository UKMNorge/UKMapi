<?php

namespace UKMNorge\Slack\Block\Structure\Collection;

use UKMNorge\Slack\Option;

class Ids extends Collection
{
    public $type = 'ids';

    public function __construct(Int $maxlength = null)
    {
        parent::__construct(is_null($maxlength) ? 0 : $maxlength);
    }
    /**
     * {@inheritDoc}
     *
     * @param Option 
     * @return self
     */
    public function add(String $user_id)
    {
        return parent::add($user_id);
    }

    /**
     * {@inheritdoc}
     *
     * @param Array<String> $options
     * @return self
     */
    public function set(array $user_ids)
    {
        return parent::set($user_ids);
    }

    /**
     * {@inheritDoc}
     *
     * @return Array<String>
     */
    public function getAll()
    {
        return parent::getAll();
    }
}
