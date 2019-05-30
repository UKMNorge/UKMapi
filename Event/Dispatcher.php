<?php

namespace UKMNorge\Event;

class Dispatcher {

	const TABLE = 'ukm_event_listeners';

	function hook( String $trigger, String $className, String $function, Integer $priority = null ) {
		$sql = new SQLins(self::TABLE);
		$sql->add('trigger', $trigger);
		$sql->add('className', $className);
		$sql->add('function', $function);
		if( !is_null( $priority ) ) {
			$sql->add('priority', $priority);	
		}
		$sql->run();
	}

	function trigger( String $trigger, $event ) {
		$sql = new SQL("SELECT `className`, `function` 
			FROM `#table`
			WHERE `trigger` = '#trigger'
			ORDER BY `priority` DESC",
			[
				'table' => self::TABLE,
				'trigger' => $trigger
			]
		);
		$res = $sql->run();
		while( $listener = SQL::fetch( $res ) ) {
			call_user_func( 
				[
					$listener['className'], 
					$listener['function']
				],
				$event
			);
		}
	}

	function autoloader( $path ) {
		
	}
}