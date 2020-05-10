<?php

namespace UKMNorge\Slack\Block\Structure\Collection;

use stdClass;
use UKMNorge\Slack\Block\Composition\PlainText;
use UKMNorge\Slack\Block\Structure\Exception;
use UKMNorge\Slack\Block\Structure\Collection\Options;
use UKMNorge\Slack\Payload\Payload;


/**
 * Option group composition
 * 
 * @see https://api.slack.com/reference/block-kit/composition-objects#option_group
 */
class OptionGroup
{
    public $options;
    public $label;

    const MAX_OPTIONS_LENGTH = 100;
    const MAX_LABEL_LENGTH = 75;


    /**
     * Create a new option group
     *
     * @param PlainText $label
     * @param Array<Option> $options
     */
    public function __construct( PlainText $label, Array $options ) 
    {
        $this->setLabel($label);
        $this->getOptions()->set($options);
    }
    /**
     * Set input label
     *
     * @param PlainText $text
     * @return self
     */
    public function setLabel( PlainText $text)
    {
        if ($text->getLength() > static::MAX_LABEL_LENGTH ) {
            throw new Exception(
                'Label length cannot be more than '.static::MAX_LABEL_LENGTH.' characters',
                'maxlength_label'
            );
        }
        $this->label = $text;
        return $this;
    }

    /**
     * Get label
     *
     * @return PlainText
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Get collection of group options
     *
     * @return Options
     */
    public function getOptions() {
        if( is_null($this->options)) {
            $this->options = new Options(static::MAX_OPTIONS_LENGTH);
        }
        return $this->options;
    }

    /**
     * Export data
     *
     * @return stdClass
     */
    public function export() {
        $data = new stdClass();
    
        // Label
        if( is_null($this->getLabel()) ) {
            throw new Exception('Option group label is required','missing_option_groups_label');
        }
        $data->label = Payload::convert($this->getLabel());
        
        if( $this->getOptions()->getLength() == 0 ){
            throw new Exception('Option group requires at least one option', 'missing_options');
        }
        $data->options = Payload::convert($this->getOptions());
        
        return $data;
    }
}
