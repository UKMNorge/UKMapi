<?php

namespace UKMNorge\Slack\Payload;

use UKMNorge\Slack\Block\Structure\Collection\Blocks;

interface PayloadInterface {

    /**
     * Get all blocks
     *
     * @return Blocks
     */
    public function getBlocks();
}