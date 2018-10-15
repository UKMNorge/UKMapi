<?php
require_once('UKM/sql.class.php');

class wp_option {
	static $pl_id = false;
	static $path = false;
	
	private static function _query( $query, $key_val_map=[] ) {
		$sql = new SQL( $query, $key_val_map, 'wordpress' );
		return $sql->run('field', 'value');
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
		$query = "SELECT `option_value` AS `value` FROM `$table` WHERE `option_name` = '#key'";
		$data = self::_query( $query, ['key' => $key] );

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
		$query = "SELECT `blog_id` AS `value` FROM `wpms2012_blogs` WHERE `path` = '/#path/'";
		$res = self::_query( $query, ['path'=>$path] );
		
		if( false === $res ) {
			throw new Exception('WP_OPTION: bloggen finnes ikke (/'. $path .'/)');
		}
		return $res;
	}
}