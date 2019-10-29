<?php

namespace UKMNorge\Innslag\Titler;

use Exception;
use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Innslag\Context\Context;
use UKMNorge\Innslag\Type;
use UKMNorge\Tid;

require_once('UKM/Autoloader.php');

class Titler extends Collection
{

    var $context = null;
    var $varighet = 0;

    /**
     * Opprett en ny collection
     *
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        #echo '<h3>TITLER::construct</h3>'; var_dump($context);
        $this->context = $context;
    }

    /**
     * Finn tittel med gitt ID
     * 
     * Bruker context for å finne tilhørende innslag
     *
     * @param Int $id
     * @return person
     **/
    public function get($id)
    {
        foreach ($this->getAllInkludertIkkePameldte() as $tittel) {
            if ($tittel->getId() == $id) {
                return $tittel;
            }
        }

        throw new Exception(
            'TITLER: Kunne ikke finne tittel ' . $id . ' i innslag ' . $this->getContext()->getInnslag()->getId(),
            172002
        ); // OBS: code brukes av har()
    }

    /**
     * Hent alle titler (videresendt til aktivt arrangement)
     * 
     * Aktivt arrangement settes via context.
     * Når innslaget lastes inn via Arrangement->getInnslag().... 
     * er dette automatisk riktig satt på titler-collection
     *
     * @return Array<Tittel>
     */
    public function getAll()
    {
        return static::filterPameldte(
            $this->getContext()->getMonstring()->getId(),
            parent::getAll()
        );
    }

    /**
     * Hent alle titler som ikke er påmeldt aktivt arrangement
     * 
     * Aktivt arrangement settes via context.
     * Når innslaget lastes inn via Arrangement->getInnslag().... 
     * er dette automatisk riktig satt på titler-collection
     *
     * @return Array<Tittel>
     */
    public function getAllIkkePameldte()
    {
        return static::filterIkkeVideresendte(
            $this->getContext()->getMonstring()->getId(),
            parent::getAll()
        );
    }

    /**
     * Hent absolutt alle titler
     * 
     * Uavhengig om de er videresendt til aktivt arrangement eller ikke
     * Aktivt arrangement settes via context.
     * Når innslaget lastes inn via Arrangement->getInnslag().... 
     * er dette automatisk riktig satt på titler-collection
     *
     * @return Array<Tittel>
     */
    public function getAllInkludertIkkePameldte()
    {
        return $this->getAll();
    }

    /**
     * Hent titler som er påmeldt gitt arrangement
     *
     * @param Int $arrangement_id
     * @param Array<Tittel> $titler
     * @return Array<Tittel>
     */
    public static function filterPameldte(Int $arrangement_id, array $titler)
    {
        $filtered = [];
        foreach ($titler as $tittel) {
            if ($tittel->erPameldt($arrangement_id)) {
                $filtered[] = $tittel;
            }
        }
        return $filtered;
    }

    /**
     * Hent titler som ikke er påmeldt gitt arrangement
     *
     * @param Int $arrangement_id
     * @param Array<Tittel> $titler
     * @return Array<Tittel>
     */
    public static function filterIkkePameldte(Int $arrangement_id, array $titler)
    {
        $filtered = [];
        foreach ($titler as $tittel) {
            if (!$tittel->erVideresendtTil($arrangement_id)) {
                $filtered[] = $tittel;
            }
        }
        return $filtered;
    }

    /**
     * Legg til en tittel i collection
     * 
     * Gjennom save vil denne fjerne påmelding for dette arrangementet
     *
     * @param Tittel $tittel
     * @return self
     */
    public function leggTil($tittel)
    {
        try {
            Write::validerTittel($tittel);
        } catch (Exception $e) {
            throw new Exception(
                'Kunne ikke legge til tittel. ' . $e->getMessage(),
                10801
            );
        }
        // Hvis tittelen allerede er lagt til kan vi skippe resten
        if ($this->har($tittel)) {
            return true;
        }
        // Gi tittelen riktig context (hent fra collection, samme som new tittel herfra)
        #$tittel->setContext($this->getContext());

        parent::leggTil($tittel);
        return $this;
    }

    /**
     * Fjern en tittel fra collection
     * 
     * Gjennom save vil denne fjerne påmelding for dette arrangementet
     *
     * @param Tittel $tittel
     * @return void
     */
    public function fjern($tittel)
    {
        try {
            Write::validerTittel($tittel);
        } catch (Exception $e) {
            throw new Exception(
                'Kunne ikke fjerne tittel. ' . $e->getMessage(),
                10801
            );
        }

        // Hvis tittelen ikke er her, så slipper vi å fjerne den
        if (!$this->har($tittel)) {
            return true;
        }

        parent::fjern($tittel);
        return true;
    }

    /**
     * Hent total varighet for titler påmeldt dette arrangementet
     * (tilhørende dette innslaget)
     *
     * @return Tid
     */
    public function getVarighet()
    {
        $this->getAll(); // Sørger for at alt er lastet inn. Varighet summeres samtidig.
        return new Tid($this->varighet);
    }

    /**
     * Last inn alle titler tilhørende innslaget
     *
     * @return void
     */
    public function _load()
    {
        $this->varighet = 0;

        $tittel_type = $this->_getTittelClass();

        // Fra og med 2020 trenger alle titler relasjon til arrangementet
        // Gjør ting litt enklere
        if (2019 < $this->getContext()->getMonstring()->getSesong()) {
            $SQL = new Query(
                "SELECT `tittel`.*,
                    GROUP_CONCAT(`relasjon`.`arrangement_id`) AS `arrangementer`
                FROM `#table` AS `tittel`
                LEFT JOIN `ukm_rel_arrangement_tittel` AS `relasjon`
                    ON(`relasjon`.`innslag_id` = `tittel`.`b_id` AND `relasjon`.`tittel_id` = `tittel`.`t_id`)
                WHERE `tittel`.`b_id` = '#innslag'
                AND `tittel`.`t_id` > 0
                GROUP BY `tittel`.`t_id`
                ORDER BY `tittel`.`#tittelfelt`",
                [
                    'table' => $tittel_type::TABLE,
                    'tittelfelt' => $tittel_type::TABLE_NAME_COL,
                    'innslag' => $this->getContext()->getInnslag()->getId()
                ]
            );
        }
        // Til og med 2013-sesongen brukte vi tabellen "landstep" for videresending til land
        else if (2014 > $this->getContext()->getMonstring()->getSesong() && 'land' == $this->getContext()->getMonstring()->getType()) {
            $SQL = new Query(
                "SELECT `title`.*,
                `videre`.`id` AS `videre_if_not_empty`
                FROM `#table` AS `title`
                LEFT JOIN `smartukm_landstep` AS `videre`
                    ON(`videre`.`b_id` = `title`.`b_id` AND `videre`.`t_id` = `title`.`t_id`)
                WHERE `title`.`b_id` = '#b_id'
                GROUP BY `title`.`t_id`
                ORDER BY `title`.`#titlefield`",
                [
                    'table' => $tittel_type::TABLE,
                    'titlefield' => $tittel_type::TABLE_NAME_COL,
                    'b_id' => $this->getContext()->getInnslag()->getId()
                ]
            );
        } else {
            $SQL = new Query(
                "SELECT `title`.*,
                    GROUP_CONCAT(`videre`.`pl_id`) AS `pl_ids`
                FROM `#table` AS `title`
                LEFT JOIN `smartukm_fylkestep` AS `videre`
                    ON(`videre`.`b_id` = `title`.`b_id` AND `videre`.`t_id` = `title`.`t_id`)
                WHERE `title`.`b_id` = '#b_id'
                AND `title`.`t_id` > 0
                GROUP BY `title`.`t_id`
                ORDER BY `title`.`#titlefield`",
                [
                    'table' => $tittel_type::TABLE,
                    'titlefield' => $tittel_type::TABLE_NAME_COL,
                    'b_id' => $this->getContext()->getInnslag()->getId()
                ]
            );
        }
        
        $res = $SQL->run();

        if ($res && $this->getContext()->getMonstring()->getSesong() > 2019) {
            while ($row = Query::fetch($res)) {
                $tittel = new $tittel_type($row);
                $tittel->setContext($this->getContext());
                $this->add($tittel);
                if ($tittel->erPameldt($this->getContext()->getMonstring()->getId())) {
                    $this->varighet += $tittel->getVarighet()->getSekunder();
                }
            }
        } elseif ($res) {
            while ($row = Query::fetch($res)) {
                // Hvis innslaget er pre 2014 og på landsmønstring jukser vi
                // til at den har pl_ids for å få lik funksjonalitet videre
                if (isset($row['videre_if_not_empty'])) {
                    if (is_numeric($row['videre_if_not_empty'])) {
                        $row['pl_ids'] = $this->getContext()->getMonstring()->getId();
                    } else {
                        $row['pl_ids'] = null;
                    }
                }
                if (empty($row['pl_id'])) {
                    $row['pl_id'] = $this->getContext()->getMonstring()->getId();
                }
                // Legg til tittel i array
                $tittel = new $tittel_type($row);
                $tittel->setContext($this->getContext());

                $this->add($tittel);

                if ($tittel->erPameldt($this->getContext()->getMonstring()->getId())) {
                    $this->varighet += $tittel->getVarighet()->getSekunder();
                }
            }
        }
    }

    /**
     * Hent navn på tittel-klassen
     *
     * @return void
     */
    private function _getTittelClass()
    {
        return 'UKMNorge\Innslag\Titler\\' . Tittel::getTittelClassFromInnslagType($this->getContext()->getInnslag()->getType());
    }

    /**
     * Hent tittelens context
     *
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Opprett context for innslag (why?)
     *
     * @return Context
     */
    public function getContextInnslag()
    {
        throw new Exception('getContextInnslag ikke implementert. Kontakt UKM Norge support');
        /*
        return Context::createInnslag(
			$this->getInnslagId(),								// Innslag ID
			$this->getInnslagType(),							// Innslag type (objekt)
			$this->getContext()->getMonstring()->getId(),		// Mønstring ID
			$this->getContext()->getMonstring()->getType(),		// Mønstring type
			$this->getContext()->getMonstring()->getSesong()	// Mønstring sesong
        );
        */
    }
}
