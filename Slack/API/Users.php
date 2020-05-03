<?php
	
namespace UKMNorge\Slack\API;

class Users {
    /**
     * Get the profile of a user
     *
     * @param String $user_id
     * @return void
     */
	public static function profileGet( String $user_id ) {
		$result = App::get('users.profile.get', ['user' => $user_id] );
		return $result->profile;
    }
    
    public static function getAll() {
        return App::botGet('users.list');
    }

}

