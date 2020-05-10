<?php

namespace UKMNorge\Slack\API\Response;

use UKMNorge\Database\SQL\Query;

class Template {
    const TABLE = 'slack_template';

    public $name;
    public $data;

    /**
     * Create new template instance
     * 
     * @return Template
     */
    public function __construct( Array $data ) {
        $this->name = $data['name'];
        $this->data = json_decode($data['data']);
    }

    /**
     * Get template name
     *
     * @return String name
    */
    public function getName() {
        return $this->name;
    }

    /**
     * Render response JSON for Slack
     *
     * @param Array render data
     * @return String JSON
     */
    public function render( Array $data = null ) {
        return $this->data; // TODO: run through twig
    }

    /**
     * Fetch template with given name from database
     * 
     * @return Template
     */
    public static function getByName(String $name ) {
        $query = new Query(
            "SELECT *
            FROM `#table`
            WHERE `name` = '#name'",
            [
                'table' => static::TABLE,
                'name' => $name
            ]
        );
        $data = $query->getArray();
        if( !$data ) {
            throw new Exception(
                'Could not find template'
            );
        }
        return new static($data);
    }
}