<?php

namespace UKMNorge\Slack\Cache\Channel;

use Exception;
use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;

class Channels extends Collection
{
    public $team_id;

    public function __construct(String $team_id)
    {
        $this->team_id = $team_id;
    }

    /**
     * Get team id of current user collection
     *
     * @return String
     */
    public function getTeamId()
    {
        return $this->team_id;
    }

    /**
     * Get user by slack Id
     *
     * @param String $team_id
     * @param String $slack_id
     * @return Channel
     * @throws Exception
     */
    public static function getBySlackId(String $team_id, String $slack_id)
    {
        $query = new Query(
            "SELECT * 
            FROM `#table`
            WHERE `slack_id` = '#slack_id'
            AND `team_id` = '#team_id'",
            [
                'table' => Channel::TABLE,
                'slack_id' => $slack_id,
                'team_id' => $team_id
            ]
        );
        $data = $query->getArray();

        if ($data) {
            return new Channel($data);
        }
        throw new Exception('Could not find channel with slack id: ' . $slack_id);
    }

    /**
     * Get channel by name
     *
     * @param String $team_id
     * @param String $channel_name
     * @return Channel
     * @throws Exception
     */
    public static function getByName(String $team_id, String $channel_name)
    {
        $query = new Query(
            "SELECT * 
            FROM `#table`
            WHERE `name` = '#name'
            AND `team_id` = '#team_id'",
            [
                'table' => Channel::TABLE,
                'name' => trim($channel_name, '#'),
                'team_id' => $team_id
            ]
        );
        $data = $query->getArray();

        if ($data) {
            return new Channel($data);
        }
        throw new Exception('Could not find channel #' . trim($channel_name,'#'));
    }


    /**
     * Get channel by internal database Id
     *
     * @param Int $id
     * @return Channel
     * @throws Exception
     */
    public static function getById(Int $id)
    {
        $query = new Query(
            "SELECT * 
            FROM `#table`
            WHERE `id` = '#id'",
            [
                'table' => Channel::TABLE,
                'id' => $id
            ]
        );
        $data = $query->getArray();

        if ($data) {
            return new Channel($data);
        }
        throw new Exception('Could not find channel with id: ' . $id);
    }

    /**
     * Load all channels from database
     *
     * @return void
     */
    public function _load()
    {
        $query = new Query(
            "SELECT *
        FROM `#table`
        WHERE `team_id` = '#team_id'
        ORDER BY `name` ASC
        ",
            [
                'table' => Channel::TABLE,
                'team_id' => $this->getTeamId()
            ]
        );
        $res = $query->run();

        while ($row = Query::fetch($res)) {
            $this->add(new Channel($row));
        }
    }
}
