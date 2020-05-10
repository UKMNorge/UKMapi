<?php

namespace UKMNorge\Slack\API\Response\Plugin\Filter;

use UKMNorge\Slack\API\Response\Plugin\Transport\TransportInterface;

abstract class ViewSubmission extends Filter {
    const TYPE = 'ViewSubmission';
    abstract public function process( TransportInterface $transport );
}