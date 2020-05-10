<?php

namespace UKMNorge\Slack\Block\Element;

use stdClass;
use UKMNorge\Slack\Block\Composition\Option;
use UKMNorge\Slack\Block\Structure\Collection\Options;
use UKMNorge\Slack\Block\Structure\Element;
use UKMNorge\Slack\Block\Structure\Exception;
use UKMNorge\Slack\Payload\Payload;

class Overflow extends Element
{
    const TYPE = 'overflow';
    const REQUIRE_ACTION_ID = true;

    const MIN_OPTIONS_LENGTH = 2;
    const MAX_OPTIONS_LENGTH = 5;

    public $options;

    /**
     * Create a overflow menu
     *
     * @param String $action_id
     * @param Array<Option> $options
     */
    public function __construct(String $action_id, array $options)
    {
        $this->setActionId($action_id);
        $this->getOptions()->set($options);
    }

    /**
     * Get collection of checkbox options
     *
     * @return Options
     */
    public function getOptions()
    {
        if (is_null($this->options)) {
            $this->options = new Options(5);
        }
        return $this->options;
    }

    /**
     * Get export data
     *
     * @return stdClass
     */
    public function export()
    {
        $data = parent::export(); // type + action_id (if not null)

        // Options
        if ($this->getOptions()->getLength() < static::MIN_OPTIONS_LENGTH || $this->getOptions()->getLength() > static::MAX_OPTIONS_LENGTH) {
            throw new Exception(
                'Checkboxes require at least ' . static::MIN_OPTIONS_LENGTH . ' and maximum ' . static::MAX_OPTIONS_LENGTH . ' options. ' .
                    'Given ' . $this->getOptions()->getLength(),
                'missing_options'
            );
        }
        $data->options = Payload::convert($this->getOptions());

        return $data;
    }
}
