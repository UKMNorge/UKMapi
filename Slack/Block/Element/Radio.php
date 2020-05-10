<?php

namespace UKMNorge\Slack\Block\Element;

use stdClass;
use UKMNorge\Slack\Block\Composition\Option;
use UKMNorge\Slack\Block\Structure\Collection\Options;
use UKMNorge\Slack\Block\Structure\Element;
use UKMNorge\Slack\Block\Structure\Exception;
use UKMNorge\Slack\Payload\Payload;

class Radio extends Element {
    const TYPE = 'radio_buttons';
    const REQUIRE_ACTION_ID = true;

    public $options;
    public $initial_option;

    /**
     * Create a group of checkboxes
     *
     * @param String $action_id
     * @param Array<Option> $options
     */
    public function __construct( String $action_id, Array $options )
    {
        $this->setActionId($action_id);
        $this->getOptions()->set($options);
    }

    /**
     * Get collection of checkbox options
     *
     * @return Options
     */
    public function getOptions() {
        if( is_null($this->options)) {
            $this->options = new Options(0);
        }
        return $this->options;
    }


    /**
     * Set the initial option
     *
     * @param Option $option
     * @return self
     */
    public function setInitialOption( Option $option ) {
        $this->initial_option = $option;
        return $this;
    }

    /**
     * Get the initial option
     *
     * @return Option
     */
    public function getInitialOption() {
        return $this->initial_option;
    }
    
    /**
     * Get export data
     *
     * @return stdClass
     */
    public function export() {
        $data = parent::export(); // type + action_id (if not null)

        // Options
        if( $this->getOptions()->getLength() == 0 ) {
            throw new Exception('Radio buttons require at least one option', 'missing_options');
        }
        $data->options = Payload::convert($this->getOptions());

        // Initial options
        if( !is_null($this->getInitialOption())) {
            $data->initial_option = Payload::convert($this->getInitialOption());
        }

        return $data;
    }
}