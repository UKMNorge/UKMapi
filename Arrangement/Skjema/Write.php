<?php

namespace UKMNorge\Arrangement\Skjema;

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Arrangement\Eier;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Delete;
use Exception;
use UKMNorge\Database\SQL\Update;

require_once('UKM/Autoloader.php');

class Write {
    
    /**
     * Opprett et skjema
     * Lagres automatisk i databasen
     *
     * @param Arrangement $arrangement
     * @return Skjema $skjema
     * @throws Exception hvis database-persist feiler
     */
    public static function createForArrangement( Arrangement $arrangement ) {
        return static::create($arrangement, 'arrangement');
    }

    /**
     * Opprett et skjema
     * Lagres automatisk i databasen
     *
     * @param Arrangement $arrangement
     * @return Skjema $skjema
     * @throws Exception hvis database-persist feiler
     */
    public static function createForPerson( Arrangement $arrangement ) {
        return static::create($arrangement, 'person');
    }

    /**
     * Opprett et skjema
     * Lagres automatisk i databasen
     *
     * @deprecated Should be private
     * @param Arrangement $arrangement
     * @return Skjema $skjema
     * @throws Exception hvis database-persist feiler
     */
    public static function create( Arrangement $arrangement, $type = 'arrangement' ) {
        $insert = new Insert('ukm_videresending_skjema');
        $insert->add('pl_id', $arrangement->getId());
        $insert->add('eier_type', $arrangement->getEierObjekt()->getType());
        $insert->add('eier_id', $arrangement->getEierObjekt()->getId());
        $insert->add('type', $type);

        $res = $insert->run();

        if( !$res ) {
            throw new Exception(
                'Kunne ikke opprette spørreskjema. '.
                'Systemet sa '. $insert->getError(),
                551001
            );
        }

        return new Skjema(
            $res,
            $arrangement->getId(),
            $arrangement->getEierObjekt()->getType(),
            $arrangement->getEierObjekt()->getId(),
            $type
        );
    }

    /**
     * Opprett et nytt spørsmål
     *
     * @param Skjema $skjema
     * @param Int $rekkefolge
     * @param String $type
     * @param String $tittel
     * @param String $tekst
     * @return Spørsmål $sporsmal
     */
    public static function createSporsmal( Skjema $skjema, Int $rekkefolge, String $type, String $tittel, String $tekst) {
        $insert = new Insert('ukm_videresending_skjema_sporsmal');
        $insert->add('skjema', $skjema->getId());
        $insert->add('rekkefolge', $rekkefolge);
        $insert->add('type', $type);
        $insert->add('tittel', $tittel);
        $insert->add('tekst', $tekst);

        $res = $insert->run();
        if( !$res ) {
            throw new Exception(
                'Kunne ikke opprette spørsmål '. $tittel .'. '.
                'Systemet sa '. $res->getError(),
                551002
            );
        }
        return new Sporsmal(
            $res, 
            $skjema->getId(),
            $rekkefolge,
            $type,
            $tittel,
            $tekst
        );
    }

    /**
     * Lagre et eksisterene spørsmål
     *
     * @param Sporsmal $sporsmal
     * @return Sporsmal $sporsmal
     */
    public static function saveSporsmal( Sporsmal $sporsmal ) {
        if( !is_numeric($sporsmal->getId()) || $sporsmal->getId() == 0) {
            throw new Exception(
                'Opprett spørsmål før du kjører lagre på de. @see ::saveSporsmal',
                551003
            );
        }
        $query = new Update(
            'ukm_videresending_skjema_sporsmal',
            [
                'id' => $sporsmal->getId()
            ]  
        );
        $query->add('rekkefolge', $sporsmal->getRekkefolge());
        $query->add('type', $sporsmal->getType());
        $query->add('tittel', $sporsmal->getTittel());
        $query->add('tekst', $sporsmal->getTekst());

        $res = $query->run();

        if( $res === false ) {
            throw new Exception(
                'Kunne ikke lagre spørsmål '. $sporsmal->getTittel() .'. '.
                'Systemet sa: '. $res->getError(),
                551004
            );
        }

        return $sporsmal;
    }

    /**
     * Fjern et eksisterende spørsmål. 
     * OBS: Man bør fortsatt lagre resterende spørsmål i controlleren, for å oppdatere rekkefølge-verdier!
     *
     * @param Sporsmal $sporsmal
     * @return bool success
     */
    public static function fjernSporsmalFraSkjema( Int $sporsmal_id, Int $skjema_id ) {
        if( $sporsmal_id == 0) {
            throw new Exception(
                'Kan ikke slette et spørsmål som ikke er lagret',
                551005
            );
        }

        // Sjekk om det finnes noen som har svart på dette spørsmålet allerede
        $query = new Query("SELECT COUNT(*) FROM ukm_videresending_skjema_svar WHERE `skjema` = '#skjema' AND `sporsmal` = '#sporsmal'", [ 'skjema' => $skjema_id, 'sporsmal' => $sporsmal_id]);
        $answers = $query->run('field');
        if( $answers > 0 ) {
            throw new Exception(
                'Du kan dessverre ikke slette et spørsmål som har fått svar allerede. Kontakt support@ukm.no for hjelp med dette',
                551007
            );
        }

        $query = new Delete('ukm_videresending_skjema_sporsmal', [ 'id' => $sporsmal_id, 'skjema' => $skjema_id ]);
        $res = $query->run();
        if( $res != 1 ) {
            throw new Exception(
                'Klarte ikke å slette spørsmål',
                551006
            );
        }
    }


    /**
     * Lagre et helt svarsett
     *
     * @param SvarSett $svarSett
     * @return SvarSett
     */
    public static function saveSvarSett( SvarSett $svarSett ) {
        $errors = [];
        foreach( $svarSett->getAll() as $svar ) {
            try {
                static::_saveSvar($svarSett, $svar);
            } catch( Exception $e ) {
                $errors[] = $e->getMessage();
            }
        }

        if( sizeof($errors) > 0 ) {
            throw new Exception(
                'Kunne ikke lagre alle svar. Systemet sa: '.
                join("\r\n", $errors),
                551008
            );
        }
        return $svarSett;
    }

    /**
     * Skriv svar til databasde
     *
     * @param SvarSett $svarSett
     * @param Svar $svar
     * @throws Exception
     * @return Bool
     */
    public static function _saveSvar(SvarSett $svarSett, Svar $svar) {
        if( !$svar->isChanged() ) {
            return true;
        }

        if( $svar->getId() == 0 ) {
            $query = new Insert('ukm_videresending_skjema_svar');
            $query->add('skjema', $svarSett->getSkjemaId());
            $query->add( ($svarSett->erArrangement() ? 'pl':'p').'_fra', $svarSett->getFra());
            $query->add('sporsmal', $svar->getSporsmalId());
        } else {
            $query = new Update(
                'ukm_videresending_skjema_svar',
                [
                    'id' => $svar->getId()
                ]
            );
        }
        $query->add('svar', $svar->getValueRaw());

        $res = $query->run();

        if( $res ) {
            if( get_class($query) == 'UKMNorge\Database\SQL\Insert') {
                $svar->setId( $res );
            }
            return true;
        }

        if( $res === 0 && get_class($query) == 'UKMNorge\Database\SQL\Update') {
            return true;//-ish
        }
        
        throw new Exception(
            'Kunne ikke lagre svar for "'. 
            $svarSett->getSkjema()->getSporsmal( $svar->getSporsmalId() )
                ->getTittel()
            .'".',
            551009
        );
    }
}