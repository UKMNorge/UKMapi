<?php

namespace UKMNorge\Slack\Block\Element;

use stdClass;
use UKMNorge\Slack\Block\Composition\Confirm;
use UKMNorge\Slack\Block\Structure\ElementWithPlaceholder;
use UKMNorge\Slack\Block\Structure\Exception;
use UKMNorge\Slack\Payload\Payload;

class Input extends ElementWithPlaceholder
{
    const TYPE = 'plain_text_input';
    const REQUIRE_ACTION_ID = true;

    const MAX_PLACEHOLDER_LENGTH = 150;
    const MAX_MIN_LENGTH = 3000;

    public $initial_value;
    public $multiline;
    public $min_length;
    public $max_length;

    /**
     * Set initial value
     *
     * @param String $initial_value
     * @return self
     */
    public function setInitialValue(String $initial_value)
    {
        // TODO VALIDATE FORMAT
        $this->initial_value = $initial_value;
        return $this;
    }

    /**
     * Get initial value
     *
     * @return String
     */
    public function getInitialValue()
    {
        return $this->initial_value;
    }

    /**
     * Set whether input should be multiline or not
     *
     * @param Bool $status
     * @return self
     */
    public function setMultiline(Bool $status)
    {
        $this->multiline = $status;
        return $this;
    }

    /**
     * Get whether input should be multiline or not
     *
     * @return Bool
     */
    public function getMultiline()
    {
        return $this->multiline;
    }

    /**
     * Set minimum length of input
     *
     * @param Int $min
     * @return self
     */
    public function setMinLength(Int $min)
    {
        if ($min > static::MAX_MIN_LENGTH) {
            throw new Exception(
                'Minimum string length too long (way off here, aren\'t you?)',
                'maxlength_min_length'
            );
        }
        $this->min_length = $min;
        return $this;
    }

    /**
     * Get minimum length of input
     *
     * @return Int
     */
    public function getMinLength() {
        return $this->min_length;
    }

    /**
     * Set maximum length of input
     *
     * @param Int $min
     * @return self
     */
    public function setMaxLength(Int $max)
    {
        $this->max_length = $max;
        return $this;
    }

    /**
     * Get max length of input
     *
     * @return Int
     */
    public function getMaxLength() {
        return $this->max_length;
    }

    /**
     * Start ExportData object with basic data
     * 
     * type + action_id (if not null) + confirm (if not null) already set in Element
     *
     * @return stdClass
     */
    public function export()
    {
        $data = parent::export();
        
        // Initial value
        if( !is_null( $this->getInitialValue() )) {
            $datainitial_value = Payload::convert($this->getInitialValue());
        }
        
        // Multiline
        if( !is_null( $this->getMultiline() )) {
            $data->multiline = Payload::convert($this->getMultiline());
        }
        
        // Min length
        if( !is_null( $this->getMinLength() )) {
            $data->min_length = Payload::convert($this->getMinLength());
        }
        
        // Max length
        if( !is_null( $this->getMaxLength() )) {
            $data->max_length = Payload::convert($this->getMaxLength());
        }

        return $data;
    }

    /**
     * setConfirm (UNSUPPORTED)
     *
     * @throws Exception
     */
    public function setConfirm(Confirm $confirm)
    {
        throw new Exception('Confirm is not supported', 'not_supported_confirm');
    }
}
