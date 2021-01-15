<?php

namespace UKMNorge\Slack;

use Exception;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Update;

class BlockAction
{

    const TABLE = 'slack_view_tempdata';

    public $view_id;
    public $key;
    public $value;

    /**
     * Create new Block Action instanca
     *
     * @param String view id
     * @param String key
     * @return BlockAction
     */
    public function __construct(String $view_id, String $key)
    {
        $this->view_id = $view_id;
        $this->key = $key;
    }

    /**
     * Create and persist new Block Action
     *
     * @param String view id
     * @param String key
     * @param String value
     * @return BlockAction
     */
    public static function create(String $view_id, String $key, String $value)
    {

        try {
            $blockAction = static::get($view_id, $key);
            $blockAction->setValue($value);
            return static::update($blockAction);
        } catch (Exception $e) {
            if ($e->getCode() == 1) {
                $query = new Insert(static::TABLE);
                $query->add('view_id', $view_id);
                $query->add('key', $key);
                $query->add('value', $value);
                $res = $query->run();

                $object = new static($view_id, $key);
                return $object->setValue($value);
            }
            throw $e;
        }
    }

    /**
     * Get Block Action object from database
     *
     * @param String view id
     * @param String block action key
     * @return BlockAction
     */
    public static function get(String $view_id, String $key)
    {
        $query = new Query(
            "SELECT `value` 
            FROM `#table`
            WHERE `view_id` = '#view_id' 
            AND `key` = '#key'",
            [
                'table' => static::TABLE,
                'view_id' => $view_id,
                'key' => $key
            ]
        );
        $data = $query->getArray();

        if (!$data) {
            throw new Exception(
                'Could not find BlockAction ' . $view_id . '::' . $key,
                1
            );
        }

        $object = new static($view_id, $key);
        $object->setValue($data['value']);
        return $object;
    }

    /**
     * Delete Block action from database
     *
     * @param BlockAction 
     * @return Bool
     */
    public static function delete(BlockAction $blockaction)
    {
        $query = new Delete(
            static::TABLE,
            [
                'view_id' => $blockaction->getViewId(),
                'key' => $blockaction->getKey()
            ]
        );
        $res = $query->run();
        return true;
    }


    /**
     * Store updated Block action to database
     *
     * @param String view id
     * @param String block action key
     * @return Bool
     */
    public static function update(BlockAction $blockaction)
    {
        $query = new Update(
            static::TABLE,
            [
                'view_id' => $blockaction->getViewId(),
                'key' => $blockaction->getKey()
            ]
        );
        $query->add('value', $blockaction->getValue());
        $res = $query->run();
        return true;
    }


    /**
     * toString output value
     *
     * @see getValue()
     */
    public function __toString()
    {
        return $this->getValue();
    }

    /**
     * Set value
     *
     * @param String value
     * @return self
     */
    public function setValue(String $value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Get value
     *
     * @return String
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get view Id
     *
     * @return String
     */
    public function getViewId()
    {
        return $this->view_id;
    }

    /**
     * Get key
     *
     * @return String key
     */
    public function getKey()
    {
        return $this->key;
    }


    /**
     * Get the actual value for given input field / block whatever
     *
     * @return String
     */
    public static function getValueFromField($field)
    {
        error_log('getValueFromField():' . var_export($field, true));
        switch ($field->type) {
            case 'datepicker':
                return $field->selected_date;
            case 'static_select':
                return $field->selected_option->value;
            case 'conversations_select':
                return $field->selected_conversation;
            case 'multi_static_select':
                return static::getValueArrayAsString($field->selected_options);
            case 'multi_users_select':
                return static::getValueArrayAsString($field->selected_users);
            case 'plain_text_input':
                return $field->value;
            case 'radio_buttons':
                return $field->selected_option->value;
        }
        throw new Exception('Unsupported data type ' . $field->type);
    }

    /**
     * Fetch all selected values as csv
     *
     * @param array
     * @return String
     */
    public static function getValueArrayAsString(array $array)
    {
        $value = [];
        if (is_array($array)) {
            foreach ($array as $option) {
                if( is_object($option) && isset($option->value ) ) {
                    $value[] = $option->value;
                } else {
                    $value[] = $option;
                }
            }
        }
        return join(',', $value);
    }

    /**
     * Get all Block Actions for given view
     *
     * @param String view id
     * @return Array<BlockAction>
     */
    public static function getAllFromView(String $view_id)
    {
        $query = new Query(
            "SELECT * 
            FROM `#table`
            WHERE `view_id` = '#view_id'",
            [
                'table' => static::TABLE,
                'view_id' => $view_id
            ]
        );
        $res = $query->run();

        $values = [];
        while ($row = Query::fetch($res)) {
            $value = new static($view_id, $row['key']);
            $values[$row['key']] = $value->setValue($row['value']);
        }
        return $values;
    }
}
