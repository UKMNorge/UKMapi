<?php

namespace UKMNorge\Slack\Payload;

use stdClass;
use UKMNorge\Slack\Block\Structure\Exception;
use UKMNorge\Slack\Block\Structure\Collection\Blocks;

abstract class Payload implements PayloadInterface
{
    public $blocks;

    /**
     * Get all blocks
     *
     * @return Blocks
     */
    public function getBlocks()
    {
        if (is_null($this->blocks)) {
            $this->blocks = new Blocks(0);
        }
        return $this->blocks;
    }

    /**
     * Get type of payload
     *
     * @return String String(modal|home|message)
     */
    public function getType()
    {
        return static::TYPE;
    }

    /**
     * Start the exportdata array
     *
     * @return stdClass
     */
    public function export()
    {
        $data = new stdClass();

        if (in_array($this->getType(), ['modal', 'home'])) {
            $data->type = $this->getType();
        }

        if( $this->getBlocks()->getLength() > 0 ) {
            foreach( $this->getBlocks()->getAll() as $block ) {
                if( is_null($block)) {
                    continue;
                }
                $blocks[] = $block->export();
            }
            $data->blocks = $blocks;
        }

        return $data;
    }

    public static function convert( $data ) {
        switch($data) {
            case is_bool($data):
                return $data;            
            case is_string($data):
                $data = stripcslashes($data);
            case is_null($data):
            case is_numeric($data):
            case get_class($data) == 'stdClass':
                return $data;
            break;
            case $data instanceof CollectionInterface:
                $data = $data->getAll();
            case is_array($data):
                $tmp = [];
                foreach( $data as $key => $val ) {
                    $tmp[$key] = static::convert($val);   
                }
                return $tmp;
            break;
            case is_object( $data ) && method_exists( get_class($data), 'export'):
                return $data->export();
                    
            default:
                throw new Exception('Could not prepare given data for export', 'invalid_export_data');
        }
    }
}
