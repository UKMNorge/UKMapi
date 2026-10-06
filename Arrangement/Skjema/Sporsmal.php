<?php

namespace UKMNorge\Arrangement\Skjema;

use UKMNorge\Database\SQL\Query;

use Exception;

class Sporsmal {
    private $id;
    private $skjema;
    private $rekkefolge;

    private $type;
    private $tittel;
    private $tekst;
    private $is_required;
    private $parent_consent_requirement = null;

    /**
     * Opprett spørsmål fra databaserad
     *
     * @param Array $db_row
     * @return Sporsmal $sporsmal
     */
    public static function createFromDatabase( $db_row ) {
        return new Sporsmal(
            $db_row['id'],
            $db_row['skjema'],
            $db_row['rekkefolge'],
            $db_row['type'],
            $db_row['tittel'],
            $db_row['tekst'],
            isset($db_row['is_required']) ? (bool) $db_row['is_required'] : true,
            isset($db_row['parent_consent_requirement']) ? $db_row['parent_consent_requirement'] : null
        );
    }

    public function __construct( Int $id, Int $skjema_id, Int $rekkefolge, String $type, String $tittel, String $tekst, bool $is_required = true, ?string $parent_consent_requirement = null )
    {
        $this->id = $id;
        $this->skjema = $skjema_id;
        $this->rekkefolge = $rekkefolge;
        $this->type = $type;
        $this->tittel = $tittel;
        $this->tekst = $tekst;
        $this->is_required = $is_required;
        $this->setParentConsentRequirement($parent_consent_requirement);
    }

    public static function getById(Int $id) : Sporsmal {
        $sql = new Query(
            "SELECT *
            FROM `ukm_videresending_skjema_sporsmal`
            WHERE `id` = '#id'",
            [
                'id' => $id
            ]
        );
        $data = $sql->getArray();
        if ($data) {
            return new Sporsmal(
                $data['id'],
                $data['skjema'],
                $data['rekkefolge'],
                $data['type'],
                $data['tittel'],
                $data['tekst'],
                isset($data['is_required']) ? (bool) $data['is_required'] : true,
                isset($data['parent_consent_requirement']) ? $data['parent_consent_requirement'] : null
            );
        }
        throw new Exception('Could not find spørsmål with id: '. $id);
    }

    /**
     * Hent spørsmålets ID
     * Brukes kun for database-interaksjon
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Hvilket skjema er dette spørsmålet for?
     */ 
    public function getSkjemaId()
    {
        return $this->skjema;
    }

    /**
     * Hvilket nummer har dette spørsmålet i skjemaet
     * 
     * @return Int $rekkefolge
     */ 
    public function getRekkefolge()
    {
        return $this->rekkefolge;
    }

    /**
     * Hent type spørsmål
     * 
     * @return String $type
     */ 
    public function getType()
    {
        return $this->type;
    }

    /**
     * Hent spørsmålets tittel (spørsmålet, altså 🤯)
     * 
     * @return String $tittel;
     */ 
    public function getTittel()
    {
        return $this->tittel;
    }

    /**
     * Hent spørsmålets hjelpetekst
     * 
     * @return String $hjelpetekst
     */ 
    public function getTekst()
    {
        return $this->tekst;
    }

    /**
     * Er spørsmålet obligatorisk å svare på?
     */
    public function isRequired(): bool
    {
        return (bool) $this->is_required;
    }

    /**
     * @return self
     */
    public function setIsRequired($is_required)
    {
        $this->is_required = (bool) $is_required;

        return $this;
    }

    /**
     * Hent krav om foresattesamtykke (u15, u18, eller null)
     *
     * @return string|null
     */
    public function getParentConsentRequirement(): ?string
    {
        return $this->parent_consent_requirement;
    }

    /**
     * Sett krav om foresattesamtykke (u15, u18, eller null)
     *
     * @param string|null $parent_consent_requirement
     * @return self
     */
    public function setParentConsentRequirement(?string $parent_consent_requirement)
    {
        $allowed = ['u15', 'u18'];
        if ($parent_consent_requirement === null || $parent_consent_requirement === '') {
            $this->parent_consent_requirement = null;
            return $this;
        }
        $this->parent_consent_requirement = in_array($parent_consent_requirement, $allowed, true)
            ? $parent_consent_requirement
            : null;

        return $this;
    }

    /**
     * Om spørsmålet skal godkjennes av foresatt for en deltaker i gitt alder.
     *
     * u15: foresatt kreves til og med 14 år.
     * u18: foresatt kreves til og med 17 år.
     * null: ingen krav, foresatt skal ikke godkjenne.
     *
     * Ukjent alder behandles som under terskelen. Deltakere som er 18 år,
     * eller har bekreftet at de er 18, krever ikke foresattesamtykke.
     */
    public function requiresParentConsentForAge(?int $age, bool $is18Year = false): bool
    {
        $requirement = $this->getParentConsentRequirement();
        if ($requirement === null || $is18Year) {
            return false;
        }

        if ($age === null) {
            return true;
        }

        // Samme terskel som SkjemaSuper::isForesattGodkjent / isParentConsentRequired.
        $consentAgeRequirement = $requirement === 'u15' ? 14 : 17;

        return $age <= $consentAgeRequirement;
    }

    /**
     * Set the value of tekst
     *
     * @return  self
     */ 
    public function setTekst($tekst)
    {
        $this->tekst = $tekst;

        return $this;
    }

    /**
     * Set the value of tittel
     *
     * @return  self
     */ 
    public function setTittel($tittel)
    {
        $this->tittel = $tittel;

        return $this;
    }

    /**
     * Set the value of type
     *
     * @return  self
     */ 
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return self
     */
    public function setRekkefolge($rekkefolge)
    {
        $this->rekkefolge = (int) $rekkefolge;

        return $this;
    }
}