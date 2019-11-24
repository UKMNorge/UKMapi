<?php

namespace UKMNorge\Innslag\Personer;

use UKMNorge\Database\SQL\Query;

/**
 * Vennesøk - verktøy for å finne venner som har vært med i samme innslag som deg tidligere.
 *
 */
class Venner {
    /**
     * Hent alle venner
     *
     * @param Int $p_id
     * @param Int $b_id
     * @return Array<Person>
     */
    public static function getAll( Int $p_id, Int $b_id=0 ) {
        return self::findFriends( $p_id, $b_id );
    }

	public static function findFriends(int $p_id, int $b_id=0) {
		$friends = array();
		$sql = new Query(
            Person::getLoadQuery()."
	        JOIN `smartukm_rel_b_p` AS `b_p2`
		        ON (`smartukm_participant`.`p_id` = `b_p2`.`p_id`)
            WHERE `b_p2`.`b_id` IN (
                SELECT `b_id`
                FROM `smartukm_rel_b_p` AS `b_p`
                WHERE `b_p`.`p_id` = #p_id
            )
            AND `b_p2`.`b_id` != #b_id
            GROUP BY `b_p2`.`p_id`
            ORDER BY `smartukm_participant`.`p_firstname` ASC,
            `smartukm_participant`.`p_lastname` ASC;
			", 
			[
                'p_id' => $p_id,
                'b_id' => $b_id
            ]
		);

        $res = $sql->run();
		while( $rad = Query::fetch( $res ) ) {
			$friends[ $rad['p_id'] ] = new Person($rad);
        }
        // Også legg til seg selv, da delta filtrerer ut 
        // alle som er med i innslaget. 
        $friends[ $p_id ] = new Person($p_id); 

		return $friends;
    }
    
    /**
     * Remove some friends from the friend-list
     * Sounds harsh, but makes sense sometimes
     *
     * @param Array<Int> $outside
     * @param Array<Person> $everyone
     * @return Array<Person> $inside
     */
    public static function exclude( Array $outside, Array $everyone ) {
        $inside = [];
        foreach( $everyone as $evaluate ) {
            if( !in_array( $evaluate->getId(), $outside )) {
                $inside[] = $evaluate;
            }
        }
        return $inside;
    }
}