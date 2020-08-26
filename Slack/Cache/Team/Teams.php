<?php

namespace UKMNorge\Slack\Cache\Team;

use Exception;
use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;

class Teams extends Collection
{
    const TABLE = "slack_access_token";

    public function _load()
    {
        $query = new Query(
            "SELECT *
        FROM `#table`
        GROUP BY `team_id`
        ORDER BY `team_name` ASC
        ",
            [
                'table' => static::TABLE
            ]
        );
        $res = $query->run();
        
        while($row = Query::fetch($res)) {
            $this->add( new Team( $row ));
        }
    }

    /**
     * Get a team by its internal cache ID
     *
     * @param Int $id
     * @return Team
     */
    public static function getById( Int $id ) {
        $query = new Query(
            "SELECT * 
            FROM `#table`
            WHERE `id` = '#id'",
            [
                'table' => static::TABLE,
                'id' => $id
            ]
        );
        $data = $query->getArray();

        if( $data ) {
            return new Team($data);
        }
        throw new Exception('Could not find team with id: '. $id);
    }

    /**
     * Get a team by its Slack ID
     *
     * @param String $id
     * @return Team
     */
    public static function getBySlackId( String $id ) {
        $query = new Query(
            "SELECT * 
            FROM `#table`
            WHERE `id` = '#id'",
            [
                'table' => static::TABLE,
                'team_id' => $id
            ]
        );
        $data = $query->getArray();

        if( $data ) {
            return new Team($data);
        }
        throw new Exception('Could not find team with Slack id: '. $id);
    }
}
