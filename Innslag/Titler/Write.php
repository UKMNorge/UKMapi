<?php

namespace UKMNorge\Innslag\Titler;

use UKMNorge\Database\SQL\Insert;
use UKMNorge\Innslag\Innslag;
use UKMNorge\Log\Logger;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Query;

require_once('UKM/Autoloader.php');

class Write
{

    /**
     * Oppretter et nytt tittel og lagrer i databasen.
     *
     * @param Innslag $innslag
     * @return Tittel
     */
    public static function create($innslag)
    {
        // Valider logger
        if (!Logger::ready()) {
            throw new Exception(
                'Logger is missing or incorrect set up.',
                50901
            );
        }
        // Valider input-data
        try {
            Innslag::validateClass($innslag);
        } catch (Exception $e) {
            throw new Exception(
                'Kunne ikke opprette tittel' . $e->getMessage(),
                $e->getCode()
            );
        }

        // Opprett spørringen
        $qry = new Insert($innslag->getType()->getTabell());
        $qry->add('b_id', $innslag->getId());
        switch ($innslag->getType()->getTabell()) {
            case 'smartukm_titles_scene':
                $action = 501;
                break;
            case 'smartukm_titles_video':
                $action = 510;
                break;
            case 'smartukm_titles_exhibition':
                $action = 514;
                break;
            default:
                // TODO
                throw new Exception(
                    'Kan kun opprette en ny tittel for scene, video eller utstilling. ' . $innslag->getType()->getTabell() . ' er ikke støttet enda.',
                    50902
                );
        }

        $insert_id = $qry->run();
        Logger::log($action, $innslag->getId(), $insert_id);

        if ($insert_id) {

            $relasjon = new Insert('ukm_rel_arrangement_tittel');
            $relasjon->add('innslag_id', $innslag->getId());
            $relasjon->add('tittel_id', $insert_id);
            $relasjon->add('arrangement_id', $innslag->getContext()->getMonstring()->getId());
            $relasjon->run();
            echo $relasjon->debug();
            
            $class = 'UKMNorge\Innslag\Titler\\' . $innslag->getType()->getTittelClass();
            return $class::getById($insert_id);
        }

        throw new Exception(
            'Klarte ikke å opprette ny tittel.',
            50903
        );
    }

    public static function save($tittel_save)
    {
        // Valider logger
        if (!Logger::ready()) {
            throw new Exception(
                'Logger is missing or incorrect set up.',
                50901
            );
        }
        // Valider inputdata
        try {
            Write::validerTittel($tittel_save);
        } catch (Exception $e) {
            throw new Exception(
                'Kan ikke lagre tittel. ' . $e->getMessage(),
                $e->getCode()
            );
        }

        #echo '<h3>TITLER\WRITE::save</h3>'; var_dump($tittel_save);

        // Opprett mønstringen tittelen kommer fra
        $monstring = new Arrangement($tittel_save->getContext()->getMonstring()->getId());
        // Hent innslaget fra gitt mønstring
        $innslag_db = $monstring->getInnslag()->get($tittel_save->getContext()->getInnslag()->getId(), true);
        // Hent personen fra gitt innslag
        $tittel_db = $innslag_db->getTitler()->get($tittel_save->getId());

        // TABELLER SOM KAN OPPDATERES
        $sql = new Insert(
            $tittel_save::TABLE,
            [
                't_id' => $tittel_save->getId(),
                'b_id' => $innslag_db->getId(),
            ]
        );
        // VERDIER SOM KAN OPPDATERES
        switch (str_replace('UKMNorge\Innslag\Titler\\', '', get_class($tittel_save))) {
            case 'Annet':
                $properties = [
                    'Tittel'                 => ['t_name', 502],
                    'VarighetSomSekunder'    => ['t_time', 503],
                ];
                break;
            case 'Musikk':
                $properties = [
                    'Tittel'                 => ['t_name', 502],
                    'VarighetSomSekunder'    => ['t_time', 503],
                    'Instrumental'            => ['t_instrumental', 504],
                    'Selvlaget'                => ['t_selfmade', 505],
                    'TekstAv'                => ['t_titleby', 506],
                    'MelodiAv'                => ['t_musicby', 507],
                ];
                break;
            case 'Teater':
                $properties = [
                    'Tittel'                 => ['t_name', 502],
                    'VarighetSomSekunder'    => ['t_time', 503],
                    'Selvlaget'                => ['t_selfmade', 505],
                    'TekstAv'                => ['t_titleby', 506],
                ];
                break;
            case 'Dans':
                $properties = [
                    'Tittel'                 => ['t_name', 502],
                    'VarighetSomSekunder'    => ['t_time', 503],
                    'Selvlaget'                => ['t_selfmade', 505],
                    'KoreografiAv'            => ['t_coreography', 508],
                ];
                break;
            case 'Litteratur':
                $properties = [
                    'Tittel'                 => ['t_name', 502],
                    'LesOpp'                => ['t_litterature_read', 509],
                    'VarighetSomSekunder'    => ['t_time', 503],
                    'TekstAv'                => ['t_titleby', 506],
                ];
                break;
                case 'Utstilling':
                    $properties = [
                        'Tittel'                 => ['t_e_title', 515],
                        'Type'                    => ['t_e_type', 516],
                        'Beskrivelse'            => ['t_e_comments', 517],
                    ];
                    break;
            case 'Film':
                $properties = [
                    'Tittel'                 => ['t_v_title', 511],
                    'VarighetSomSekunder'    => ['t_v_time', 512],
                    'Format'                => ['t_v_format', 513],
                ];
                break;
            default:
                throw new Exception(
                    'Kunne ikke lagre tittel. Ukjent database-tabell ' . str_replace('UKMNorge\Innslag\Titler\\', '', get_class($tittel_save)),
                    50904
                );
        }

        // LOOP ALLE VERDIER, OG EVT LEGG TIL I SQL
        foreach ($properties as $functionName => $logValues) {
            $function = 'get' . $functionName;
            $field = $logValues[0];
            $action = $logValues[1];

            if ($tittel_db->$function() != $tittel_save->$function()) {
                # Mellomlagre verdi som skal settes
                $value = $tittel_save->$function();

                # Konverter booleans til '0' eller '1' som er standard i databasen
                if (true === $value) {
                    $value = '1';
                } elseif (false === $value) {
                    $value = '0';
                }

                # Legg til i SQL
                $sql->add($field, $value);
                # Logg (eller dø) før vi kjører run
                Logger::log($action, $tittel_save->getId(), $value);
            }
        }

        if ($sql->hasChanges()) {
            $sql->run();
        }

        return true;
    }


    /********************************************************************************
     *
     *
     * LEGG TIL OG FJERN PERSON FRA COLLECTION
     *
     *
     ********************************************************************************/

    /**
     * Legg til tittelen i innslaget
     * Videresender automatisk til context-mønstring
     * 
     * @param tittel_v2 $tittel_save
     **/
    public static function leggtil($tittel_save)
    {
        // Valider inputs
        Write::_validerLeggtil($tittel_save);

        // Opprett mønstringen tittelen kommer fra
        $monstring = new Arrangement($tittel_save->getContext()->getMonstring()->getId());
        // Hent innslaget fra gitt mønstring
        $innslag_db = $monstring->getInnslag()->get($tittel_save->getContext()->getInnslag()->getId(), true);

        // En tittel vil alltid være lagt til lokalt

        // Videresend tittelen hvis ikke lokalmønstring
        if ($monstring->getType() != 'kommune') {
            $res = Write::_leggTilVideresend($tittel_save);
        }

        if ($res) {
            return $tittel_save;
        }

        throw new Exception(
            'Kunne ikke legge til ' . $tittel_save->getTittel() . ' i innslaget. ',
            50913
        );
    }


    /**
     * Fjern en videresendt tittel, og avmelder hvis gitt lokalmønstring
     *
     * @param Tittel $tittel_save
     *
     * @return Bool
     * @throws Exception 
     */
    public function fjern(Tittel $tittel_save)
    {
        // Valider inputs
        Write::_validerLeggtil($tittel_save);

        // Opprett mønstringen tittelen kommer fra
        $monstring = new Arrangement($tittel_save->getContext()->getMonstring()->getId());

        if ($monstring->getType() == 'kommune') {
            $res = Write::_fjernLokalt($tittel_save);
        } else {
            $res = Write::_fjernVideresend($tittel_save);
        }

        if ($res) {
            return true;
        }

        throw new Exception(
            'Kunne ikke fjerne ' . $tittel_save->getTittel() . ' fra innslaget. ',
            50514
        );
    }




    /********************************************************************************
     *
     *
     * LEGG TIL-HJELPERE
     *
     *
     ********************************************************************************/
    /**
     * Legg til en tittel på videresendt nivå
     *
     * @param tittel_v2 $tittel_save
     **/
    private function _leggTilVideresend($tittel_save)
    {
        throw new Exception(
            'Kan ikke videresende. Relasjon ukm_rel_arrangement_tittel ikke implementert. Kontakt UKM Norge'
        );
        $test_relasjon = new Query(
            "SELECT * FROM `smartukm_fylkestep`
				WHERE `pl_id` = '#pl_id'
				AND `b_id` = '#b_id'
				AND `t_id` = '#t_id'",
            [
                'pl_id'        => $tittel_save->getContext()->getMonstring()->getId(),
                'b_id'        => $tittel_save->getContext()->getInnslag()->getId(),
                't_id'        => $tittel_save->getId(),
            ]
        );
        $test_relasjon = $test_relasjon->run();

        // Hvis allerede videresendt, alt ok
        if (Query::numRows($test_relasjon) > 0) {
            return true;
        }
        // Videresend tittelen
        else {
            $videresend_tittel = new Insert('smartukm_fylkestep');
            $videresend_tittel->add('pl_id', $tittel_save->getContext()->getMonstring()->getId());
            $videresend_tittel->add('b_id', $tittel_save->getContext()->getInnslag()->getId());
            $videresend_tittel->add('t_id', $tittel_save->getId());

            $log_msg = $tittel_save->getId() . ': ' . $tittel_save->getTittel() . ' => PL: ' . $tittel_save->getContext()->getMonstring()->getId();
            Logger::log(322, $tittel_save->getContext()->getInnslag()->getId(), $log_msg);
            $res = $videresend_tittel->run();

            if ($res) {
                return true;
            }
        }

        throw new Exception(
            'Kunne ikke videresende ' . $tittel_save->getTittel(),
            50516
        );
    }


    /********************************************************************************
     *
     *
     * FJERN-HJELPERE
     *
     *
     ********************************************************************************/

    /**
     * Fjern en tittel fra innslaget helt
     * @param Tittel $tittel_save
     **/
    private static function _fjernLokalt( Tittel $tittel_save)
    {
        Logger::log(327, $tittel_save->getContext()->getInnslag()->getId(), $tittel_save->getId() . ': ' . $tittel_save->getTittel());
        $qry = new Delete(
            $tittel_save::TABLE,
            [
                't_id' => $tittel_save->getId(),
                'b_id' => $tittel_save->getContext()->getInnslag()->getId(),
            ]
        );
        $res = $qry->run();
        if ($res == 1) {
            
            // Fjerner alle relasjoner mellom arrangement og tittelen
            $delete_rel = new Delete(
                'ukm_rel_arrangement_tittel',
                [
                    'innslag_id' => $tittel_save->getContext()->getInnslag()->getId(),
                    'tittel_id' => $tittel_save->getId()
                ]
            );
            $delete_rel->run();
            
            return true;
        }

        throw new Exception(
            'Klarte ikke fjerne tittel ' . $tittel_save->getTittel(),
            50515
        );
    }

    /**
     * 
     * Avrelaterer en tittel fra dette innslaget.
     *
     * @param tittel_v2 $tittel_save
     *
     * @return (bool true|throw exception)
     */
    public function _fjernVideresend($tittel_save)
    {
        throw new Exception(
            'Kan ikke fjerne videresending. Relasjon ukm_rel_arrangement_tittel ikke implementert. Kontakt UKM Norge'
        );
        $videresend_tittel = new Delete(
            'smartukm_fylkestep',
            [
                'pl_id'     => $tittel_save->getContext()->getMonstring()->getId(),
                'b_id'         => $tittel_save->getContext()->getInnslag()->getId(),
                't_id'         => $tittel_save->getId()
            ]
        );
        $log_msg = $tittel_save->getId() . ': ' . $tittel_save->getTittel() . ' => PL: ' . $tittel_save->getContext()->getMonstring()->getId();
        Logger::log(323, $tittel_save->getContext()->getInnslag()->getId(), $log_msg);

        // Slett tittelen
        $res = $videresend_tittel->run();

        // Hvis slettingen gikk bra
        if ($res) {

            /**
             * Fjerning av siste tittel vil avmelde innslaget
             * Skulle dette ikke være ønsket effekt, må det her settes inn en ny fylkesstep-rad
             * med blank tittel-ID (som igjen må slettes ved innslag::avmeld()
             **/

            // Sjekk antall relasjoner som er igjen
            $test_remaining_fylkestep = new Query(
                "SELECT COUNT(`id`) AS `num`
				FROM `smartukm_fylkestep`
				WHERE `pl_id` = '#pl_id'
				AND `b_id` = '#b_id'",
                [
                    'pl_id'     => $tittel_save->getContext()->getMonstring()->getId(),
                    'b_id'         => $tittel_save->getContext()->getInnslag()->getId(),
                ]
            );
            $remaining_fylkestep = $test_remaining_fylkestep->run('field', 'num');

            /**
             * HVIS ingen relasjoner gjenstår etter tittelen er fjernet, avmeld innslaget (manuelt)
             * Kan ikke kalle write_innslag::fjern() da det blir circular loop
             **/
            if ($remaining_fylkestep == 0) {
                // Slett relasjonen manuelt
                $SQLdel = new Delete(
                    'smartukm_rel_pl_b',
                    [
                        'b_id'         => $tittel_save->getContext()->getInnslag()->getId(),
                        'pl_id'     => $tittel_save->getContext()->getMonstring()->getId(),
                        'season'     => $tittel_save->getContext()->getMonstring()->getSesong()
                    ]
                );
                Logger::log(311, $tittel_save->getContext()->getInnslag()->getId(), $tittel_save->getContext()->getInnslag()->getId());
                $res2 = $SQLdel->run();
            }
        }

        if ($res) {
            return true;
        }

        throw new Exception(
            'Kunne ikke avmelde ' . $tittel_save->getTittel() . ' fra mønstringen',
            50907
        );
    }


    /********************************************************************************
     *
     *
     * VALIDER INPUT-PARAMETRE
     *
     *
     ********************************************************************************/

    /**
     * Valider at gitt tittel-objekt er av riktig type
     * og har en numerisk Id som kan brukes til database-modifisering
     *
     * @param tittel_V2 $tittel
     * @return void
     **/
    public static function validerTittel($tittel)
    {
        if (!Tittel::validateClass($tittel)) {
            throw new Exception(
                'Tittel må være objekt av klassen tittel_v2',
                50905
            );
        }
        if (!is_numeric($tittel->getId()) || $tittel->getId() <= 0) {
            throw new Exception(
                'Tittel-objektet må ha en numerisk ID større enn null',
                50906
            );
        }
    }


    /**
     * Valider alle input-parametre for å legge til ny tittel
     *
     * @see leggTil
     **/
    private static function _validerLeggtil($tittel_save)
    {
        // Valider input-data
        try {
            Write::validerTittel($tittel_save);
        } catch (Exception $e) {
            throw new Exception(
                'Kan ikke legge til/fjerne tittel. ' . $e->getMessage(),
                $e->getCode()
            );
        }

        // Valider kontekst (tilknytning til mønstring)
        if ($tittel_save->getContext()->getMonstring() == null) {
            throw new Exception(
                'Kan ikke legge til/fjerne tittel. ' .
                    'Tittel-objektet er ikke opprettet i riktig kontekst',
                50911
            );
        }
        // Valider kontekst (tilknytning til innslag)
        if ($tittel_save->getContext()->getInnslag() == null) {
            throw new Exception(
                'Kan ikke legge til/fjerne tittel. ' .
                    'Tittel-objektet er ikke opprettet i riktig kontekst',
                50912
            );
        }
    }
}
