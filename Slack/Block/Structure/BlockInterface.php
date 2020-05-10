<?php

namespace UKMNorge\Slack\Block\Structure;

use stdClass;

interface BlockInterface {
    
    /** const TYPE must also be defined */

    /**
     * Get block type
     *
     * @return String
     */
    public function getType();
    
    /**
     * Get block Id
     *
     * @return String
     */
    public function getId();

    /**
     * Export data
     *
     * @return stdClass
     */
    public function export();
}