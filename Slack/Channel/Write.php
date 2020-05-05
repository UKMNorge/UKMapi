<?php

namespace UKMNorge\Slack\Channel;

use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Update;

class Write
{
    const MAP = [
        'name' => 'getName',
        'description' => 'getDescription',
        'data' => 'getAdditionalData'
    ];

    /**
     * Store a new channel object
     *
     * @param String $team_id
     * @param String $user_id
     * @param String $name
     * @return Channel
     */
    public static function create(String $team_id, String $user_id, String $name)
    {
        $query = new Insert(Channel::TABLE);
        $query->add('team_id', $team_id);
        $query->add('slack_id', $user_id);
        $query->add('name', $name);

        $insert_id = $query->run();
        return Channels::getById($insert_id);
    }

    /**
     * Save changes to the user
     *
     * @param User $user
     * @return Bool
     */
    public static function save(Channel $object)
    {
        $db_object = Channels::getById($object->getId());

        $query = new Update(
            Channel::TABLE,
            ['id' => $object->getId()]
        );

        foreach( static::MAP as $db_field => $function ) {
            if( $db_object->$function() != $object->$function() ) {
                $value = $object->$function();
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
