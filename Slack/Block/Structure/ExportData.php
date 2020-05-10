<?php

namespace UKMNorge\Slack\Block\Structure;

use UKMNorge\Slack\Block\Structure\Collection\CollectionInterface;

class ExportData {
    public $data;

    public function add(String $key, $data) {
        switch($data) {
            case is_null($data):
                // ignore null-data parameters
            break;
            case is_bool($data):
                throw new Exception('IMPLEMENT: ExportData::add("", Bool)', 'todo_implement');
            case is_string($data):
            case is_numeric($data):
                $this->data[$key] = $data;
                break;
            case $data instanceof CollectionInterface:
            case is_object( $data ) && method_exists( get_class($data), '__toArray'):
                $this->data[$key] = $data->__toArray();
            break;
            default:
                throw new Exception('Could not prepare given data for export', 'invalid_export_data');
        }
    }

    /**
     * Renders export data to array
     *
     * @return Array
     */
    public function __toArray() {
        return $this->data;
    }

    public function __toString() {
        return json_encode( $this->__toArray());
    }
}