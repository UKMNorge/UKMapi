<?php

require_once('sql.class.php');
require_once('person.class.php');

/**
 * Vennesøk - verktøy for å finne venner som har vært med i samme innslag som deg tidligere.
 *
 */
class Venner {
	public static function findFriends(int $p_id) {
		$friends = array();
		$sql = new SQL("
			SELECT p_id FROM `smartukm_rel_b_p` AS b_p2 WHERE b_p2.b_id IN 
				( SELECT b_id FROM `smartukm_rel_b_p` AS b_p WHERE b_p.p_id = #p_id )
			GROUP BY b_p2.p_id;
			", 
			array("p_id" => $p_id)
		);

		$res = $sql->run();
		while( $rad = SQL::fetch( $res ) ) {
			$friends[] = new person_v2($rad['p_id']);
		}

		return $friends;
	}
}