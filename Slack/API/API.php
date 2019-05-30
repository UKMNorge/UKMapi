<?php
	
namespace UKMNorge\Slack\API;

class API {
	static $token = null;
	
	public static function init( $token ) {
		self::$token = $token;
	}
	
	public static function getToken() {
		return self::$token;
	}
}