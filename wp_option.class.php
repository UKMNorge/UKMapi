<?php

class wp_option {
	static $pl_id = false;
	static $path = false;
	
	private static function _query( $query ) {
		$link = mysql_connect( UKM_WP_DB_HOST, UKM_WP_DB_USER, UKM_WP_DB_PASSWORD );
		mysql_select_db(UKM_WP_DB_NAME, $link);
		mysql_set_charset('utf-8', $link);
		$res = mysql_query( $query );
 		mysql_close( $link );
 		return $res;
	}
	
	public static function setMonstring( $pl_id, $path ) {
		self::$pl_id = $pl_id;
		self::$path = $path;
	}
	
	public static function getOption( $key ) {
		if( false === self::$pl_id || false === self::$path ) {
			throw new Exception('WP_OPTION: getOption krever at setMonstring er kjørt først');
		}
		$table = 'wpms2012_'. self::_getBlogId( self::$path ) .'_options';
		$query = "SELECT `option_value` FROM `$table` WHERE `option_name` = '$key'";
		$res = self::_query( $query );
		
		$row = mysql_fetch_assoc( $res );
		$data = $row['option_value'];
#		echo '<h3>'. $query .'</h3>';

		// Empty means no result or false
		if( empty( $data ) ) {
			return false;
		}

		$data_serialized = @unserialize( $data );
		if( is_object( $data_serialized ) || is_array( $data_serialized ) ) {
			return $data_serialized;
		}

		$data_json = @json_decode( $data );
		if( is_object( $data_json ) || is_array( $data_json ) ) {
			return $data_json;	
		}

		return stripslashes($data);
	}
	
	private static function _getBlogId( $path ) {
		$query = "SELECT `blog_id` FROM `wpms2012_blogs` WHERE `path` = '/$path/'";
		$res = self::_query( $query );
		$row = mysql_fetch_assoc( $res );
		
		if( false === $res || false === $row ) {
			throw new Exception('WP_OPTION: bloggen finnes ikke (/'. $path .'/)');
		}
		return $row['blog_id'];
	}
}