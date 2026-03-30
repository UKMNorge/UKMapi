<?php

namespace UKMNorge\Samtykkeskjema;

use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Query;

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Innslag\Media\Bilder\Bilde;
use UKMNorge\Filmer\UKMTV\Film;
use UKMNorge\Innslag\Innslag;
use Exception;

require_once('UKM/Autoloader.php');

class Write {

    /**
     * Opprett et nytt samtykkeskjema
     *
     * @param string $navn
     * @return SamtykkeSkjema
     * @throws Exception
     */
    public static function create(string $navn, ?Arrangement $arrangement = null): SamtykkeSkjema {
        $sql = new Insert(SamtykkeSkjema::TABLE);
        $sql->add('navn', $navn);
        $id = $sql->run();

        if(!$id) {
            throw new Exception('Kunne ikke opprette samtykkeskjema');
        }

        $samtykkeskjema = new SamtykkeSkjema((int) $id);

        if($arrangement) {
            self::leggTilArrangement($samtykkeskjema, $arrangement);
        }
        
        return $samtykkeskjema;
    }

    /**
     * Lagre endringer på samtykkeskjemaet
     * @param SamtykkeSkjema $skjema
     * @return SamtykkeSkjema
     * @throws Exception
     */
    public static function save( SamtykkeSkjema $skjema ): SamtykkeSkjema
    {
        $sql = new Query(
            "UPDATE `" . SamtykkeSkjema::TABLE . "` SET `navn` = '#navn' WHERE `id` = '#id'",
            [
                'navn' => $skjema->getNavn(),
                'id'   => $skjema->getId(),
            ]
        );
        try {
            $sql->run();
        } catch (Exception $e) {
            throw new Exception('Kunne ikke lagre samtykkeskjema');
        }

        return new SamtykkeSkjema((int) $skjema->getId());
    }


    /**
     * Slett et samtykkeskjema
     * OBS: Sletter også tilknyttede versjoner, prosjekter og entiteter
     *
     * @param SamtykkeSkjema $skjema
     * @return bool
     * @throws Exception
     */
    public static function delete( SamtykkeSkjema $skjema ) : bool {
        $id = $skjema->getId();

        // Slett svar knyttet til alle versjoner
        $sql = new Query(
            "DELETE s FROM `" . SvarSamtykke::TABLE . "` s
             JOIN `" . SamtykkeVersjon::TABLE . "` v ON s.versjon_id = v.id
             WHERE v.skjema_id = '#id'",
            ['id' => $id]
        );
        $sql->run();

        // Slett versjoner
        $sql = new Query(
            "DELETE FROM `" . SamtykkeVersjon::TABLE . "` WHERE `skjema_id` = '#id'",
            ['id' => $id]
        );
        $sql->run();

        // Slett prosjekter
        $sql = new Query(
            "DELETE FROM `" . SamtykkeProsjekt::TABLE . "` WHERE `skjema_id` = '#id'",
            ['id' => $id]
        );
        $sql->run();

        // Slett entiteter
        $sql = new Query(
            "DELETE FROM `samtykkeskjema_entitet` WHERE `skjema_id` = '#id'",
            ['id' => $id]
        );
        $sql->run();

        // Slett arrangement-relasjoner
        $sql = new Query(
            "DELETE FROM `rel_samtykkeskjema_arrangement` WHERE `skjema_id` = '#id'",
            ['id' => $id]
        );
        $sql->run();

        // Slett selve skjemaet
        $sql = new Query(
            "DELETE FROM `" . SamtykkeSkjema::TABLE . "` WHERE `id` = '#id'",
            ['id' => $id]
        );
        $sql->run();

        return true;
    }


    /********************************************************************************
     *
     * VERSJONER
     *
     ********************************************************************************/

    /**
     * Opprett en ny versjon for et samtykkeskjema
     *
     * @param SamtykkeSkjema $skjema
     * @param string $versjonNr
     * @param string|null $beskrivelse
     * @param string|null $bodyText
     * @param string|null $filePath
     * @return SamtykkeVersjon
     * @throws Exception
     */
    public static function createVersjon( SamtykkeSkjema $skjema, string $versjonNr, ?string $beskrivelse = null, ?string $bodyText = null, ?string $filePath = null ) : SamtykkeVersjon {
    }

    /**
     * Lagre endringer på en versjon
     *
     * @param SamtykkeSkjema $skjema
     * @param SamtykkeVersjon $versjon
     * @return SamtykkeVersjon
     * @throws Exception
     */
    public static function saveVersjon(SamtykkeSkjema $skjema, SamtykkeVersjon $versjon): SamtykkeVersjon {
        $sql = new Query(
            "UPDATE `" . SamtykkeVersjon::TABLE . 
            "` SET `beskrivelse` = '#beskrivelse', `body_text` = '#body_text', `file_path` = '#file_path' 
            WHERE `id` = '#id'",
            [
                'beskrivelse' => $versjon->getBeskrivelse(),
                'body_text' => $versjon->getBodyText(),
                'file_path' => $versjon->getFilePath(),
                'id' => $versjon->getId(),
            ]
        );
        try {
            $sql->run();
        } catch (Exception $e) {
            throw new Exception('Kunne ikke lagre versjon');
        }
        return new SamtykkeVersjon((int) $sql->run());
    }

    /**
     * Slett en versjon
     * OBS: Kan ikke slettes hvis det finnes svar på versjonen
     *
     * @param SamtykkeVersjon $versjon
     * @return bool
     * @throws Exception
     */
    public static function deleteVersjon( SamtykkeVersjon $versjon ) : bool {
    }


    /********************************************************************************
     *
     * PROSJEKTER
     *
     ********************************************************************************/

    /**
     * Opprett et nytt prosjekt knyttet til et samtykkeskjema
     *
     * @param SamtykkeSkjema $skjema
     * @param string $navn
     * @param string|null $beskrivelse
     * @param int|null $arrangementId
     * @return SamtykkeProsjekt
     * @throws Exception
     */
    public static function createProsjekt( SamtykkeSkjema $skjema, string $navn, ?string $beskrivelse = null, ?int $arrangementId = null ) : SamtykkeProsjekt {
    }

    /**
     * Lagre endringer på et prosjekt
     *
     * @param SamtykkeProsjekt $prosjekt
     * @return SamtykkeProsjekt
     * @throws Exception
     */
    public static function saveProsjekt( SamtykkeProsjekt $prosjekt ) : SamtykkeProsjekt {
    }

    /**
     * Slett et prosjekt
     *
     * @param SamtykkeProsjekt $prosjekt
     * @return bool
     * @throws Exception
     */
    public static function deleteProsjekt( SamtykkeProsjekt $prosjekt ) : bool {
    }


    /********************************************************************************
     *
     * RELASJON: ARRANGEMENT
     *
     ********************************************************************************/

    /**
     * Knytt et arrangement til et samtykkeskjema
     *
     * @param SamtykkeSkjema $skjema
     * @param Arrangement $arrangement
     * @return bool
     * @throws Exception
     */
    public static function leggTilArrangement( SamtykkeSkjema $skjema, Arrangement $arrangement ) : bool {
        $sql = new Insert('rel_samtykkeskjema_arrangement');
        $sql->add('skjema_id', $skjema->getId());
        $sql->add('arrangement_id', $arrangement->getId());
        $res = $sql->run();
        if(!$res) {
            throw new Exception('Kunne ikke knytte arrangement til samtykkeskjema');
        }
        return true;
    }

    /**
     * Fjern et arrangement fra et samtykkeskjema
     *
     * @param SamtykkeSkjema $skjema
     * @param Arrangement $arrangement
     * @return bool
     * @throws Exception
     */
    public static function fjernArrangement( SamtykkeSkjema $skjema, Arrangement $arrangement ) : bool {
    }


    /********************************************************************************
     *
     * RELASJON: ENTITETER (Bilde, Film, Innslag, Arrangement)
     *
     ********************************************************************************/

    /**
     * Knytt et Bilde til et samtykkeskjema
     *
     * @param SamtykkeSkjema $skjema
     * @param Bilde $bilde
     * @return bool
     * @throws Exception
     */
    public static function leggTilBilde( SamtykkeSkjema $skjema, Bilde $bilde ) : bool {
    }

    /**
     * Fjern et Bilde fra et samtykkeskjema
     *
     * @param SamtykkeSkjema $skjema
     * @param Bilde $bilde
     * @return bool
     * @throws Exception
     */
    public static function fjernBilde( SamtykkeSkjema $skjema, Bilde $bilde ) : bool {
    }

    /**
     * Knytt en Film til et samtykkeskjema
     *
     * @param SamtykkeSkjema $skjema
     * @param Film $film
     * @return bool
     * @throws Exception
     */
    public static function leggTilFilm( SamtykkeSkjema $skjema, Film $film ) : bool {
    }

    /**
     * Fjern en Film fra et samtykkeskjema
     *
     * @param SamtykkeSkjema $skjema
     * @param Film $film
     * @return bool
     * @throws Exception
     */
    public static function fjernFilm( SamtykkeSkjema $skjema, Film $film ) : bool {
    }

    /**
     * Knytt et Innslag til et samtykkeskjema
     *
     * @param SamtykkeSkjema $skjema
     * @param Innslag $innslag
     * @return bool
     * @throws Exception
     */
    public static function leggTilInnslag( SamtykkeSkjema $skjema, Innslag $innslag ) : bool {
    }

    /**
     * Fjern et Innslag fra et samtykkeskjema
     *
     * @param SamtykkeSkjema $skjema
     * @param Innslag $innslag
     * @return bool
     * @throws Exception
     */
    public static function fjernInnslag( SamtykkeSkjema $skjema, Innslag $innslag ) : bool {
    }


    /********************************************************************************
     *
     * SVAR / SAMTYKKE
     *
     ********************************************************************************/

    /**
     * Opprett et nytt (tomt) svar for en bruker på en versjon
     *
     * @param SamtykkeVersjon $versjon
     * @param int $userId
     * @param bool $isForesatt
     * @return SvarSamtykke
     * @throws Exception
     */
    public static function createSvar( SamtykkeVersjon $versjon, int $userId, bool $isForesatt = false ) : SvarSamtykke {
    }

    /**
     * Lagre et brukersvar (oppdater eksisterende svar)
     *
     * @param SvarSamtykke $svar
     * @return SvarSamtykke
     * @throws Exception
     */
    public static function saveSvar( SvarSamtykke $svar ) : SvarSamtykke {
    }

    /**
     * Registrer at en bruker godtar samtykket
     *
     * @param SvarSamtykke $svar
     * @param int $userId
     * @param string|null $ipAddress
     * @param string $signedMethod
     * @return SvarSamtykke
     * @throws Exception
     */
    public static function godkjennSvar( SvarSamtykke $svar, int $userId, ?string $ipAddress = null, string $signedMethod = 'delta' ) : SvarSamtykke {
    }

    /**
     * Registrer at en bruker avslår samtykket
     *
     * @param SvarSamtykke $svar
     * @param int $userId
     * @param string|null $ipAddress
     * @return SvarSamtykke
     * @throws Exception
     */
    public static function avslaSvar( SvarSamtykke $svar, int $userId, ?string $ipAddress = null ) : SvarSamtykke {
    }

    /**
     * Slett et brukersvar
     * OBS: Kan normalt ikke brukes etter at samtykke er gitt
     *
     * @param SvarSamtykke $svar
     * @return bool
     * @throws Exception
     */
    public static function deleteSvar( SvarSamtykke $svar ) : bool {
    }
}
