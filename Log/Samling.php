<?php

namespace UKMNorge\Log;

use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;

class Samling extends Collection
{
    public $type = null;
    public $id = null;

    public function __construct(String $type, Int $id)
    {
        $this->type = $type;
        $this->id = $id;
    }

    private function _load()
    {
        switch( $this->type ) {
            case 'arrangement':
                $where = "WHERE `log`.`log_pl_id` = '#pl_id'";
            break;
            case 'innslag':
                $where = "WHERE `log`.`log_the_object_id` = '#pl_id'
                    AND `log`.`log_object` = 3";
            break;
        }

        $sql = new Query(
            "SELECT * 
            FROM `log_log` AS `log`
			JOIN `log_actions` AS `action` 
                ON (`log`.`log_action` = `action`.`log_action_id`)
			JOIN `log_objects` AS `object` 
                ON (`object`.`log_object_id` = `log`.`log_object`)
			LEFT JOIN `log_value` AS `value` 
                ON (`value`.`log_id` = `log`.`log_id`)
            ". $where ."
            ORDER BY `log`.`log_id` DESC",
            [
                'pl_id' => $this->getId()
            ]
        );
        $res = $sql->run();

        while( $row = Query::fetch( $res ) ) {
            $this->add( new Event( $row ) );
        }
    }

    public function getId() {
        return $this->id;
    }
}
