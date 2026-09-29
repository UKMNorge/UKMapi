<?php

namespace UKMNorge\Samtykkeskjema;

use UKMNorge\Innslag\Personer\Person;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Arrangement\Oppgave\Oppgave;
use UKMNorge\Arrangement\Oppgave\OppgaveSkjema;

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
    

    public function getAlleRespondenter(): array {
        return [];
    }

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

    /**
     * Verdien som brukes i oppgave_skjema.skjema_type.
     */
    abstract public function getOppgaveSkjemaType(): string;

    public function erDelAvOppgave(): bool {
        return OppgaveSkjema::existsFor($this->getOppgaveSkjemaType(), (int) $this->getId());
    }

    public function getOppgave(): ?Oppgave {
        $ledd = OppgaveSkjema::loadFor($this->getOppgaveSkjemaType(), (int) $this->getId());
        return $ledd ? new Oppgave($ledd->getOppgaveId()) : null;
    }

    public function isAnswered($userId, $personId) : bool {
        return false;
    }

    public function isGodkjent($userId) : bool {
        return false;
    }

    /**
     * Krav om foresattesamtykke lagret på skjemaet (u15, u18, eller null).
     */
    abstract public function getParentConsentRequirement(): ?string;

    // Sjekker om skjemaet er godkjent, basert på parentConsentRequirement på skjemaet
    public function isForesattGodkjent($userId, $personId) : bool {
        // Deltaker er 18 år eller eldre derfor trenger ikke samtykke fra foresatte/foreldre
        if($this->isDeltaker18Plus($userId, $personId)) {
            return true;
        }

        $parentConsentRequirement = $this->getParentConsentRequirement();

        // Ingen krav om foresattesamtykke, skjemaet er godkjent
        if ($parentConsentRequirement === null) {
            return true;
        }

        // u15: foresatt kreves til og med 14 år. u18: foresatt kreves til og med 17 år.
        $consentAgeRequirement = $parentConsentRequirement === 'u15' ? 14 : 17;

        $person = Person::loadFromId($personId);
        // Deltaker er eldre enn kravet, skjemaet er godkjent uten foresatt
        if($person->getAlderTall() > $consentAgeRequirement) {
            return true;
        }

        return false;
    }

    public function isParentConsentRequired($userId, $personId): bool {
        $parentConsentRequirement = $this->getParentConsentRequirement();
        if($parentConsentRequirement === null) {
            return false;
        }

        $consentAgeRequirement = $parentConsentRequirement === 'u15' ? 14 : 17;
        
        $person = Person::loadFromId($personId);

        if($person->getAlderTall() > $consentAgeRequirement) {
            return false;
        }

        return true;
    }

    protected function isDeltaker18Plus($userId, $personId) : bool {
        try {
            $person = Person::loadFromId($personId);
            if($person->getAlder() >= 18) {
                return true;
            }
            
            if($this->isDeltaUser18Plus($person->getMobil())) {
                return true;
            }
            return false;
        } catch(Exception $e) {
            return false;
        }
        return false;
    }

    protected function getDeltaUserIdByMobil(string $mobil): int {
        $sql = new Query(
            "SELECT id from ukm_user WHERE phone = '#phone'",
            ['phone' => $mobil],
            'ukmdelta'
        );
        $res = $sql->run('array');
        return $res['id'] ?? -1;
    }

    protected function isDeltaUser18Plus($phone) : bool {
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
