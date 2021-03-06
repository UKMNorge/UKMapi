<?php

namespace UKMNorge\Slack\Cache\User;

use Exception;
use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;

class Users extends Collection
{
    public $team_id;
    private $manual_load = false;

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
     * Create a proxy user
     * 
     * Gives a user object, with only id's. If needed, the rest is loaded upon request
     *
     * @param String $team_id
     * @param String $user_id
     * @return void
     */
    public static function getProxy( String $team_id, String $user_id ) {
        return new User(['team_id' => $team_id, 'slack_id' => $user_id], true);
    }

    /**
     * Get user by slack Id
     *
     * @param String $slack_id
     * @return User
     * @throws Exception
     */
    public static function getBySlackId(String $team_id, String $slack_id) {
        $query = new Query(
            "SELECT * 
            FROM `#table`
            WHERE `slack_id` = '#slack_id'
            AND `team_id` = '#team_id'",
            [
                'table' => User::TABLE,
                'team_id' => $team_id,
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
    public static function getByHandlebar(String $team_id, String $handlebar) {
        $handlebar = str_replace('@','', $handlebar);
        $query = new Query(
            "SELECT * 
            FROM `#table`
            WHERE `name` = '#handlebar'
            AND `team_id` = '#team'",
            [
                'table' => User::TABLE,
                'handlebar' => $handlebar,
                'team' => $team_id
            ]
        );
        $data = $query->getArray();

        if( $data ) {
            return new User($data);
        }
        throw new Exception('Could not find user @'. $handlebar .' in team '. $team_id);
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
        $users->setManualLoad(true);

        // Sanitize data before direct query insert
        $san_helper = new Query('', []);
        foreach( $handlebars as $index => $handlebar ) {
            $handlebars[$index] = $san_helper->sanitize($handlebar);
        }

        $query = new Query(
            "SELECT *
            FROM `#table`
            WHERE `name` IN ('". join("','", $handlebars) ."')
            AND `team_id` = '#team'
            ORDER BY `real_name` ASC, `name` ASC
            ",
            [
                'table' => User::TABLE,
                'team' => $team_id
            ]
        );

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
     * Add possibility to create a users collection without autoloading all
     * 
     * @param Bool $status
     * @return self
     */
    public function setManualLoad( Bool $status ) {
        $this->manual_load = $status;
        return $this;
    }

    /**
     * Load all users from database
     * 
     * use static->setManualLoad(true) to deactivate autoload
     *
     * @return void
     */
    public function _load()
    {
        if( $this->manual_load ) {
            return true;
        }

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
