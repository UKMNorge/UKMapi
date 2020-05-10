<?php

namespace UKMNorge\Slack\API\Response\Plugin\Filter;

use UKMNorge\Slack\API\Response\Plugin\Transport\TransportInterface;

abstract class BlockAction extends Filter {
    const TYPE = 'BlockAction';
    abstract public function process( TransportInterface $transport );
}