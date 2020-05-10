<?php

namespace UKMNorge\Slack\Block\Structure;

use stdClass;
use UKMNorge\Slack\Block\Composition\PlainText;
use UKMNorge\Slack\Payload\Payload;

class ElementWithPlaceholder extends Element
{
    const MAX_PLACEHOLDER_LENGTH = 150;
    public $placeholder;

    /**
     * Create a new multiselect users list
     *
     * @param String $action_id
     * @param array $options
     * @param PlainText $placeholder
     */
    public function __construct(String $action_id, PlainText $placeholder = null)
    {
        $this->setActionId($action_id);
        if( !is_null($placeholder)) {
            $this->setPlaceholder($placeholder);
        }
    }

    /**
     * Set the placeholder
     *
     * @param PlainText 
     * @return self
     */
    public function setPlaceholder( PlainText $placeholder ) {
        if( $placeholder->getLength() > static::MAX_PLACEHOLDER_LENGTH ) {
            throw new Exception(
                'Placeholder must be less than '. static::MAX_PLACEHOLDER_LENGTH .' characters. Given '. $placeholder->getLength(),
                'maxlength_placeholder'
            );
        }
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     * Get the placeholder
     *
     * @return PlainText
    */
    public function getPlaceholder() {
        return $this->placeholder;
    }

    /**
     * Start ExportData object with basic data
     * 
     * Most child classes should extend this one
     * 
     * // type + action_id (if not null) + confirm (if not null)
     *
     * @return stdClass
     */
    public function export()
    {
        $data = parent::export();

        // Placeholder
        if (!is_null($this->getPlaceholder())) {
            $data->placeholder = Payload::convert($this->getPlaceholder());
        }

        return $data;
    }
}
