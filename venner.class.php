<?php

require_once('sql.class.php');
require_once('person.class.php');

/**
 * Vennesøk - verktøy for å finne venner som har vært med i samme innslag som deg tidligere.
 *
 */
class Venner {
	public static function findFriends(int $p_id, int $b_id=0) {
		$friends = array();
		$sql = new SQL(
            person_v2::getLoadQuery()."
	        JOIN `smartukm_rel_b_p` AS `b_p2`
		        ON (`smartukm_participant`.`p_id` = `b_p2`.`p_id`)
            WHERE `b_p2`.`b_id` IN (
                SELECT `b_id`
                FROM `smartukm_rel_b_p` AS `b_p`
                WHERE `b_p`.`p_id` = #p_id
            )
            AND `b_p2`.`b_id` != #b_id
            GROUP BY `b_p2`.`p_id`;
			", 
			[
                'p_id' => $p_id,
                'b_id' => $b_id
            ]
		);

        $res = $sql->run();
		while( $rad = SQL::fetch( $res ) ) {
			$friends[] = new person_v2($rad);
		}

		return $friends;
	}
}