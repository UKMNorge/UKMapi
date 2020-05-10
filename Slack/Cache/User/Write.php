<?php

namespace UKMNorge\Slack\Cache\User;

use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Slack\User\Users;

class Write
{
    const MAP = [
        'name' => 'getName',
        'real_name' => 'getRealName',
        'data' => 'getAdditionalData'
    ];

    /**
     * Store a new user object
     *
     * @param String $team_id
     * @param String $user_id
     * @param String $name
     * @return User
     */
    public static function create(String $team_id, String $user_id, String $name)
    {
        $query = new Insert(User::TABLE);
        $query->add('team_id', $team_id);
        $query->add('slack_id', $user_id);
        $query->add('name', $name);

        $insert_id = $query->run();
        return Users::getById($insert_id);
    }

    /**
     * Save changes to the user
     *
     * @param User $user
     * @return Bool
     */
    public static function save(User $user)
    {
        $db_user = Users::getById($user->getId());

        $query = new Update(
            User::TABLE,
            ['id' => $user->getId()]
        );

        foreach( static::MAP as $db_field => $function ) {
            if( $db_user->$function() != $user->$function() ) {
                $value = $user->$function();
                if( is_array($value) || is_a( $value, '\stdClass' ) ) {
                    $value = json_encode($value);
                }
                $query->add( $db_field, $value );
            }
        }

        if( !$query->hasChanges() ) {
            return true;
        }

        $query->run();
        return true;
    }
}
