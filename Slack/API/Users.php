<?php
	
namespace UKMNorge\Slack\API;
use UKMCURL;

class Users extends API {

	public static function profileGet( $user_id ) {
		$curl = new UKMCURL();
		$curl->addHeader('Authorization: Bearer '. parent::getToken() );
		$result = $curl->request('https://slack.com/api/users.profile.get?user='. $user_id );

		return $result->profile;
	}
}

