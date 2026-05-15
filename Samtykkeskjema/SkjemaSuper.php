<?php

namespace UKMNorge\Samtykkeskjema;

use UKMNorge\Innslag\Personer\Person;
use UKMNorge\Database\SQL\Query;

use Exception;

/**
 * Abstract superclass representerer et skjema generelt.
 *
 * Provides the common identity/naming contract and the int-or-array
 * constructor dispatch that both concrete classes use.
 */
abstract class SkjemaSuper {

    protected string $id;
    protected string $navn = '';
    
    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getNavn(): string {
        return $this->navn;
    }

    public function isAnswered($userId, $personId) : bool {
        return false;
    }

    public function isGodkjent($userId) : bool {
        return false;
    }

    public function isForesattGodkjent($userId, $personId) : bool {
        return false;
    }

    protected function isDeltaker18Plus($userId, $personId) : bool {
        try {
            $person = Person::loadFromId($personId);
            if($person->getAlder() >= 18) {
                return true;
            }
            
            if($this->getDeltaUserIdByMobil($person->getMobil())) {
                return true;
            }
            return false;
        } catch(Exception $e) {
            return false;
        }
        return false;
    }

    protected function getDeltaUserIdByMobil($phone) : bool {
        if($phone) {
            $sql = new Query(
                "SELECT birthdate, is_18_year from ukm_user WHERE phone = '#phone'",
                ['phone' => $phone],
                'ukmdelta'
            );
            $res = $sql->run('array');
            if($res && isset($res['birthdate'])) {
                $birthdate = new \DateTime($res['birthdate']);
                $now = new \DateTime();
                $age = $now->diff($birthdate)->y;
                $age = (int) $age;
                
                if($age < 17) {
                    return false;
                }
                if($age == 17) {
                    if($res['is_18_year']) {
                        return true;
                    }
                    else {
                        return false;
                    }
                }
                return true;
            }
            return false;
        }
        return false;
    }
}
