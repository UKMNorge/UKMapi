<?php

namespace UKMNorge\Arrangement\Program;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Logger\Logger;

require_once('UKM/Autoloader.php');

class Write
{
    /**
     * Opprett en ny hendelse
     *
     * @param monstring $monstring
     *
     * @return forestilling_v2 $forestilling
     **/
    public static function create($monstring, $navn, $start)
    {
        // Valider at logger er på plass
        if (!Logger::ready()) {
            throw new Exception(
                'Logger is missing or incorrect set up.',
                517004
            );
        }

        // Må være gitt mønstring
        if (!is_object($monstring) || !Arrangement::validateClass($monstring)) {
            throw new Exception(
                'Kan ikke opprette hendelse uten gyldig mønstring-objekt',
                517005
            );
        }

        // Må være gitt navn som string
        if (!is_string($navn)) {
            throw new Exception(
                'Kan ikke opprette hendelse uten navn som string',
                517006
            );
        }

        // Må være gitt start som DateTime
        if (get_class($start) != 'DateTime') {
            throw new Exception(
                'Kan ikke opprette hendelse uten start-tidspunkt som DateTime',
                517007
            );
        }

        ## CREATE INNSLAG-SQL
        $sql = new Insert('smartukm_concert');
        $sql->add('c_name', $navn);
        $sql->add('pl_id', $monstring->getId());
        $sql->add('c_start', $start->getTimestamp());

        $id = $sql->run();

        if ($id) {
            Logger::log(219, $id, $navn);
        } else {
            throw new Exception(
                'Klarte ikke å opprette hendelse',
                517008
            );
        }

        return new Hendelse($id);
    }

    /**
     * Lagre et hendelse-objekt
     *
     * @param hendelse_v2 $hendelse_save
     * @return bool true
     **/
    public static function save($hendelse_save)
    {
        // Valider logger
        if (!Logger::ready()) {
            throw new Exception(
                'Logger is missing or incorrect set up.',
                517003
            );
        }

        // Valider input-data
        try {
            Hendelse::validateClass($hendelse_save);
        } catch (Exception $e) {
            throw new Exception(
                'Kan ikke lagre hendelse. ' . $e->getMessage(),
                $e->getCode()
            );
        }

        // Hent sammenligningsgrunnlag
        try {
            $hendelse_db = new Hendelse($hendelse_save->getId());
        } catch (Exception $e) {
            throw new Exception(
                'Kan ikke lagre hendelsens endringer. Feil ved henting av kontroll-hendelse. ' . $e->getMessage(),
                $e->getCode()
            );
        }

        // TABELLER SOM KAN OPPDATERES
        $smartukm_concert = new Update(
            'smartukm_concert',
            [
                'c_id' => $hendelse_save->getId()
            ]
        );

        // VERDIER SOM KAN OPPDATERES
        $properties = [
            'Navn'                     => ['c_name', 'String', 206],
            'Sted'                    => ['c_place', 'String', 207],
            'Start'                    => ['c_start', 'DateTime', 208],
            'OppmoteFor'            => ['c_before', 'Int', 214],
            'OppmoteDelay'            => ['c_delay', 'Int', 215],
            'SynligRammeprogram'    => ['c_visible_program', 'Bool', 213],
            'SynligDetaljprogram'    => ['c_visible_detail', 'Bool', 217],
            'SynligOppmotetid'        => ['c_visible_oppmote', 'Bool', 224],
            'Type'                    => ['c_type', 'String', 221],
            'TypePostId'            => ['c_type_post_id', 'Int', 222],
            'TypeCategoryId'        => ['c_type_category_id', 'Int', 223],
            'Intern'                => ['c_intern', 'Bool', 225],
            'Beskrivelse'            => ['c_beskrivelse', 'String', 226],
            'Farge'                    => ['c_color', 'String', 227],
            'Fremhevet'                => ['c_fremhevet', 'Bool', 228]
        ];

        // LOOP ALLE VERDIER, OG EVT LEGG TIL I SQL
        foreach ($properties as $functionName => $logValues) {
            $function = 'get' . $functionName;
            $field = $logValues[0];
            $type = $logValues[1];
            $action = $logValues[2];

            if ($hendelse_db->$function() != $hendelse_save->$function()) {
                # Mellomlagre verdi som skal settes
                $value = $hendelse_save->$function();

                switch ($type) {
                    case 'Int':
                        $value = (int) $value;
                        break;
                    case 'Bool':
                        $value = $value ? 'true' : 'false';
                        break;
                    case 'DateTime':
                        $value = $value->getTimestamp();
                        break;
                }
                # Legg til i SQL
                $smartukm_concert->add($field, $value);
                # Logg (eller dø) før vi kjører run
                Logger::log($action, $hendelse_save->getId(), $value);
            }
        }

        if ($smartukm_concert->hasChanges()) {
            #echo $smartukm_concert->debug();
            $smartukm_concert->run();
        }
    }


    /**
     * Legg til innslaget i hendelsen
     * Videresender automatisk til context-mønstring
     * 
     * @param innslag_v2 $innslag_save
     **/
    public static function leggTil($innslag_save)
    {
        throw new Exception('SORRY: Systemet har en feil i implementeringen av leggTil. Kontakt support@ukm.no');
    }


    /**
     * Fjern et innslag fra alle forestillinger på en mønstring
     * Gjøres når et innslag er avmeldt en mønstring
     *
     * @param write_innslag $innslag
     * @return $this
     **/
    public static function fjernInnslagFraAlleForestillingerIMonstring($innslag)
    {
        Innslag::validateClass($innslag);

        // Opprett mønstringen innslaget kommer fra
        $monstring = new Arrangement($innslag->getContext()->getMonstring()->getId());

        // Fjern innslaget fra alle hendelser i mønstringen
        foreach ($monstring->getProgram()->getAllInkludertSkjulte() as $forestilling) {
            if ($forestilling->getInnslag()->har($innslag)) {
                // Modifiserer ikke collectionen, da den kun eksisterer internt i funksjonen
                Logger::log(220, $forestilling->getId(), $innslag->getId());
                $qry = new SQLdel(
                    'smartukm_rel_b_c',
                    [
                        'c_id' => $forestilling->getId(),
                        'b_id' => $innslag->getId()
                    ]
                );
                $res = $qry->run();
            }
        }
    }
}
