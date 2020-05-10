<?php

namespace UKMNorge\Slack\Block\Structure;

use stdClass;
use UKMNorge\Slack\Payload\Payload;

class Select extends ElementWithPlaceholder
{
    const REQUIRE_ACTION_ID = true;
    const IS_MULTI_SELECT = false;

    public $max_selected_items;

    /**
     * Set max number of selected items
     *
     * @param Int $max
     * @return self
     */
    public function setMaxSelectedItems(Int $max)
    {
        static::requireMultiSelect();
        if ($max < 1) {
            throw new Exception(
                'Multiselect requires at least one selected item',
                'invalid_max_selected_items_value'
            );
        }
        $this->max_selected_items = $max;
    }

    /**
     * Get maximum number of selected items
     *
     * @return Int
     */
    public function getMaxSelectedItems()
    {
        static::requireMultiSelect();
        return $this->max_selected_items;
    }

    /**
     * Get export data
     *
     * @return stdClass
     */
    public function export()
    {
        $data = parent::export(); // type + action_id (if not null) + confirm (if not null)

        // Max selected items
        if( static::isMultiSelect() && !is_null($this->getMaxSelectedItems()) ) {
            $data->max_selected_items = Payload::convert($this->getMaxSelectedItems());
        }

        return $data;
    }

    /**
     * Are we currently in an multi-select ?
     *
     * @return boolean
     */
    public static function isMultiSelect() {
        return static::IS_MULTI_SELECT;
    }

    /**
     * Are we currently in an single-select?
     *
     * @return boolean
     */
    public static function isSingleSelect() {
        return !static::isMultiSelect();
    }

    /**
     * Make sure we're executing the code in multi select-context
     *
     * @return void
     * @throws Exception
     */
    public static function requireMultiSelect() {
        if( !static::isMultiSelect() ) {
            static::unsupported('requireMultiSelect', 'function_requires_multi_select');
        }
    }

    /**
     * Make sure we're executing the code in single-select context
     *
     * @return void
     * @throws Exception
     */
    public static function requireSingleSelect() {
        if( static::isMultiSelect() ) {
            static::unsupported('requireSingleSelect', 'function_requires_single_select');
        }
    }
}
