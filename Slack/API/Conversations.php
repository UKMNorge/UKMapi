<?php
	
namespace UKMNorge\Slack\API;

class Conversations {    
    public static function startWithUser( String $handlebar ) {
        return static::start( ['users' => $handlebar]);
    }

    public static function startWithUsers( Array $handlebars ) {
        return static::start( ['users' => implode(',',$handlebars)]);
    }

    private static function start( Array $data ) {
        $request_data = array_merge(
            [
                'types' => 'public_channel,private_channel,im',
            ],
            $data
        );
        return App::botPost('conversations.open', $request_data);
    }
}