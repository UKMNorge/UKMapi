<?php

namespace UKMNorge\Slack\Cache\User;

use Exception;
use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;

class Users extends Collection
{
    public $team_id;

    public function __construct( String $team_id ) {
        $this->team_id = $team_id;
    }

    /**
     * Get team id of current user collection
     *
     * @return String
     */
    public function getTeamId() {
        return $this->team_id;
    }

    /**
     * Get user by slack Id
     *
     * @param String $slack_id
     * @return User
     * @throws Exception
     */
    public static function getBySlackId(String $slack_id) {
        $query = new Query(
            "SELECT * 
            FROM `#table`
            WHERE `slack_id` = '#slack_id'",
            [
                'table' => User::TABLE,
                'slack_id' => $slack_id
            ]
        );
        $data = $query->getArray();

        if( $data ) {
            return new User($data);
        }
        throw new Exception('Could not find user with slack id: '. $slack_id);
    }

    /**
     * Get user by handlebar
     *
     * @param String $slack_id
     * @return User
     * @throws Exception
     */
    public static function getByHandlebar(String $handlebar) {
        $handlebar = str_replace('@','', $handlebar);
        $query = new Query(
            "SELECT * 
            FROM `#table`
            WHERE `name` = '#slack_id'",
            [
                'table' => User::TABLE,
                'slack_id' => $handlebar
            ]
        );
        $data = $query->getArray();

        if( $data ) {
            return new User($data);
        }
        throw new Exception('Could not find user @'. $handlebar);
    }

    /**
     * Get multiple users by handlebar
     *
     * @param String $team_id
     * @param Array $handlebars
     * @return Users
     */
    public static function getByHandleBars( String $team_id, Array $handlebars ) {
        $users = new static( $team_id );

        // Sanitize data before direct query insert
        $san_helper = new Query('', []);
        foreach( $handlebars as $index => $handlebar ) {
            $handlebars[$index] = $san_helper->sanitize($handlebar);
        }

        $query = new Query(
            "SELECT *
            FROM `#table`
            WHERE `name` IN ('". join("','", $handlebars) ."')
            ORDER BY `real_name` ASC, `name` ASC
            ",
            [
                'table' => User::TABLE
            ]
        );
        error_log('getByHandlebars: '. $query->debug());

        $res = $query->run();

        while( $data = Query::fetch( $res ) ) {
            $users->add( new User( $data ));
        }

        return $users;
    }


    /**
     * Get user by internal database Id
     *
     * @param Int $id
     * @return User
     * @throws Exception
     */
    public static function getById(Int $id) {
        $query = new Query(
            "SELECT * 
            FROM `#table`
            WHERE `id` = '#id'",
            [
                'table' => User::TABLE,
                'id' => $id
            ]
        );
        $data = $query->getArray();

        if( $data ) {
            return new User($data);
        }
        throw new Exception('Could not find user with id: '. $id);
    }

    /**
     * Load all users from database
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
                'table' => User::TABLE,
                'team_id' => $this->getTeamId()
            ]
        );
        $res = $query->run();
        
        while($row = Query::fetch($res)) {
            $this->add( new User( $row ));
        }
    }
}
