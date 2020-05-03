<?php

namespace UKMNorge\Slack\Team;

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
}
