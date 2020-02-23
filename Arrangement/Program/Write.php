<?php

namespace UKMNorge\Arrangement\Program;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Innslag\Context\Context;
use UKMNorge\Innslag\Context\Forestilling;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Innslag\Innslag;
use UKMNorge\Log\Logger;

require_once('UKM/Autoloader.php');

class Write
{
    /**
     * Opprett en ny hendelse
     *
     * @param monstring $monstring
     *
     * @return Hendelse $forestilling
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
            Logger::log(219, $id, $navn); // 219 er feil, men fortsett ut 2020-sesongen for konsekvent logg
            Logger::log(229, $id, $navn); // 229 er riktig
        } else {
            throw new Exception(
                'Klarte ikke å opprette hendelse',
                517008
            );
        }

        return new Hendelse($id);
    }

    /**
     *
     * Dupliserer et arrangement med alle innstillinger og innslag.
     * @param Hendelse $opprinnelig_hendelse
     * @return Hendelse $ny_hendelse
     */
    public static function dupliser( Hendelse $hendelse ) {
        // Valider at logger er på plass
        if (!Logger::ready()) {
            throw new Exception(
                'Logger is missing or incorrect set up.',
                517004
            );
        }
        Logger::log(230, $hendelse->getId(), $hendelse->getNavn());

        // Finn mønstring fra hendelsen
        $monstring = $hendelse->getMonstring();

        if (!is_object($monstring) || !Arrangement::validateClass($monstring)) {
            throw new Exception(
                'Fant ikke gyldig mønstrings-objekt i hendelsen',
                517005
            );
        }

        // Opprett den nye hendelsen
        $ny_hendelse = static::create($monstring, "Kopi av ".$hendelse->getNavn(), $hendelse->getStart());

        if( get_class($ny_hendelse) != 'UKMNorge\Arrangement\Program\Hendelse' ) {
            throw new Exception(
                'Klarte ikke å opprette en kopi av hendelsen. '.get_class($ny_hendelse),
                517009
            );
        }

        // Last inn hendelse på nytt for å få rett context.
        $ny_hendelse = $monstring->getProgram()->get($ny_hendelse->getId());
        
        // Sett verdier fra original hendelse til ny hendelse
        $ny_hendelse->setSted($hendelse->getSted());
        $ny_hendelse->setSynligDetaljprogram($hendelse->getSynligDetaljprogram());
        $ny_hendelse->setType($hendelse->getType());
        $ny_hendelse->setSynligRammeProgram($hendelse->getSynligRammeProgram());
        $ny_hendelse->setIntern($hendelse->getIntern());
        $ny_hendelse->setBeskrivelse($hendelse->getBeskrivelse());
        $ny_hendelse->setTypePostId($hendelse->getTypePostId()); # ? 
        $ny_hendelse->setTypeCategoryId($hendelse->getTypeCategoryId());
        $ny_hendelse->setFarge($hendelse->getFarge());
        $ny_hendelse->setFremhevet($hendelse->getFremhevet());
        $ny_hendelse->setOppmoteFor($hendelse->getOppmoteFor());
        $ny_hendelse->setOppmoteDelay($hendelse->getOppmoteDelay());
        $ny_hendelse->setSynligOppmotetid($hendelse->getSynligOppmotetid());

        static::save($ny_hendelse);

        // Legg til alle innslag fra original hendelse.
        $alle_innslag = $hendelse->getInnslag()->getAll();

        foreach($alle_innslag as $innslag) {
            $ny_hendelse = static::leggTil($ny_hendelse, $innslag);
        }

        return $ny_hendelse;
    }

    public static function slett( Hendelse $hendelse ) {
        // Valider at logger er på plass
        if (!Logger::ready()) {
            throw new Exception(
                'Logger is missing or incorrect set up.',
                517004
            );
        }
        Logger::log(229, $hendelse->getId(), $hendelse->getNavn());

        // Slett rekkefølgen i hendelsen
        $rel = new Delete(
            'smartukm_rel_b_c',
            [
                'c_id' => $hendelse->getId()
            ]
        );
        $res = $rel->run();

        // Slett hendelsen
        $del = new Delete(
            'smartukm_concert',
            [
                'c_id' => $hendelse->getId()
            ]
        );
        $res = $del->run();

        return true;
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
     * Legg til innslag i hendelse
     * 
     * Husk å manuelt legge til innslaget i hendelsens
     * innslag-collection, evt overskrive $hendelse med returnert
     * hendelse
     * 
     * @param Hendelse $hendelse
     * @param Innslag $innslag_save
     * @return Hendelse
     **/
    public static function leggTil( Hendelse $hendelse, Innslag $innslag )
    {
        Logger::log( 219, $hendelse->getId(), $innslag->getId() );

		$lastorder = new Query("SELECT `order`
            FROM `smartukm_rel_b_c`
            WHERE `c_id` = '#hendelse'
            ORDER BY `order` DESC
            LIMIT 1",
            [
                'hendelse' => $hendelse->getId() 
            ]
        );
		$lastorder = $lastorder->getField();
		$order = (int)$lastorder+1;
		
		$qry = new Insert('smartukm_rel_b_c');
		$qry->add('b_id', $innslag->getId() );
		$qry->add('c_id', $hendelse->getId() );
		$qry->add('order', $order);
		$res = $qry->run();
		
		if( !$res ) {
			throw new Exception(
				'Klarte ikke å legge til innslaget i forestillingen.',
				517009
			);
        }
        
        if( !$hendelse->getInnslag()->har( $innslag->getId() ) ) {
            $hendelse->getInnslag()->leggTil( $innslag );
        }

		return $hendelse;
    }

    /**
     * Fjern innslag fra hendelse
     *
     * Husk å manuelt fjerne innslaget fra hendelsens
     * innslag-collection, evt overskrive $hendelse med returnert
     * hendelse
     * 
     * @param Hendelse $hendelse
     * @param Innslag $innslag
     * @return Hendelse $hendelse
     */
    public static function fjern( Hendelse $hendelse, Innslag $innslag ) {
        // Logg (eller dø) før sql utføres
		Logger::log( 220, $innslag->getContext()->getForestilling()->getId(), $innslag->getId() );

		// Fjern fra forestillingen
		$qry = new Delete(
            'smartukm_rel_b_c', 
            [   
                'c_id' => $hendelse->getId(),
                'b_id' => $innslag->getId()
            ]
        );
		$res = $qry->run();

		if( 1 != $res ) {
			throw new Exception(
				'Klarte ikke å fjerne innslaget fra forestillingen.',
				505020
			);
        }
        
        if( $hendelse->getInnslag()->har( $innslag ) ) {
            $hendelse->getInnslag()->fjern($innslag);
        }

		return $hendelse;
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
                $qry = new Delete(
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

    /**
     * Reverser rekkefølgen på innslag i gitt hendelse
     *
     * @param Hendelse $hendelse
     * @return Bool success
     */
    public static function setRekkefolgeMotsatt( Hendelse $hendelse ) {
        $alle_innslag = $hendelse->getInnslag()->getAll();
        $reverserte_innslag = array_reverse($alle_innslag);
        $reversert_id = array();

        foreach( $reverserte_innslag as $innslag ) {
            $reversert_id[] = $innslag->getId();
        }

        return static::redefineOrder($hendelse, $reversert_id);
    }

    /**
     * Tell opp og lagre innslagenes rekkefølge på nytt.
     *
     * @param Hendelse $hendelse
     * @return void
     */
    public static function reCountOrder( Hendelse $hendelse ) {
        $count = 0;
        $relasjoner = new Query("SELECT `bc_id`,`b_id`
            FROM `smartukm_rel_b_c`
            WHERE `c_id` = '#hendelse'
            ORDER BY `order` ASC",
            [
                'hendelse' => $hendelse->getId()
            ]
        );
        $res = $relasjoner->run();
        while( $row = Query::fetch( $res ) ) {
            $count++;
            $update = new Update(
                'smartukm_rel_b_c',
                [
                    'bc_id' => $row['bc_id'],
                    'b_id' => $row['b_id'],
                    'c_id' => $hendelse->getId()
                ]
            );
            $update->add('order', $count);
            $update->run();
        }
        return true;
    }

    /**
     * Oppdater hendelsens rekkefølge ifølge gitt array
     *
     * @param Hendelse $hendelse
     * @param Array $innslag_id
     * @return Bool
     */
    public static function redefineOrder( Hendelse $hendelse, Array $innslag_id ) {
        $delete = new Delete(
            'smartukm_rel_b_c',
            [
                'c_id' => $hendelse->getId()
            ]
        );
        $delete = $delete->run();
            
        $count = 0;
        foreach( $innslag_id as $innslag ) {
            $count++;
            $insert = new Insert('smartukm_rel_b_c');
            $insert->add('c_id', $hendelse->getId());
            $insert->add('b_id', $innslag);
            $insert->add('order', $count);
            $insert->run();
        }
        return true;
    }
}
