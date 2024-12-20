<?php

namespace UKMNorge\Nettverk;

use UKMNorge\Nettverk\OmradeKontaktperson;
use UKMNorge\Nettverk\OmradeKontaktpersoner;

use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;
use UKMNorge\OAuth2\ArrSys\AccessControlArrSys;

use Exception;

class WriteOmradeKontaktperson {
   
    /**
     * Last opp bildet til kontaktpersonen (uten kobling til kontaktperson)
     *
     * @param OmradeKontaktperson $okp
     * @param bool $deletedProfileImage is the profile image deleted (no profile image)
     * @throws Exception
     * @return void
     */
    public static function uploadProfileImage($file, OmradeKontaktperson $okp, bool $deletedProfileImage) : void {    
        // Profilbildet er fjernet (ingen profilbilde)
        if($deletedProfileImage && $file['size'] == 0) {
            $okp->setProfileImageUrl(null);
            return;
        }

        // Bilder er ikke lastet opp (ikke endringer)
        if($file['size'] == 0) {
            return;
        }

        $file_name = $file['name'];
        $file_temp = $file['tmp_name'];
        // Check if the file is an image
        $check = getimagesize($file_temp);
        if($check === false) {
            throw new Exception('Filen er ikke et bilde', 400);
        }

        $upload_dir = wp_upload_dir();
        $image_data = file_get_contents( $file_temp );
        $filename = basename( $file_name );
        $filetype = wp_check_filetype($file_name);
        $filename = time().'.'.$filetype['ext'];

        if ( wp_mkdir_p( $upload_dir['path'] ) ) {
            $file = $upload_dir['path'] . '/' . $filename;
        }
        else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }

        file_put_contents( $file, $image_data );
        $wp_filetype = wp_check_filetype( $filename, null );
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name( $filename ),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attach_id = wp_insert_attachment( $attachment, $file );
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
        wp_update_attachment_metadata( $attach_id, $attach_data );

        $url = wp_get_attachment_url($attach_id);

        // Lagrer bilde på kontaktperson
        $okp->setProfileImageUrl($url);
    }

    /**
     * Fjern en områdekontaktperson fra et område
     *
     * Brukeren sjekkes for tilgang til området for å fjerne kontaktpersonen
     * 
     * @param OmradeKontaktperson $okp
     * @param Omrade $omrade
     * @throws Exception
     * @return Bool
     */
    public static function removeFromOmrade(OmradeKontaktperson $okp, Omrade $omrade) {
        // Sjekk tilgang
        try{
            self::checkAccessToOmrade($omrade);
        } catch( Exception $e ) {
            throw $e;
        }

        try {
            $query = new Update(
                OmradeKontaktpersoner::OMRADE_RELATION_TABLE,
                [
                    'kontaktperson_id' => $okp->getId(),
                    'omrade_type' => $omrade->getType(),
                    'omrade_id' => $omrade->getForeignId()
                ]
            );
            $query->add('is_active', 0);
    
            $query->run();
        } catch( Exception $e ) {
            throw new Exception(
                'Klarte ikke å fjerne kontaktpersonen fra området',
                562009
            );
        }
    }

    /**
     * Opprett en ny områdekontaktperson
     *
     * @param OmradeKontaktperson $okp
     * @throws Exception
     * @return OmradeKontaktperson
     */
    public static function createOmradekontaktperson(OmradeKontaktperson $okp) {
        // Sjekk tilgang
        try{
            self::checkAccess($okp);
        } catch( Exception $e ) {
            throw $e;
        }

        // Sjekk om kontaktpersonen allerede finnes
        try {
            $existedOkp = static::getOmrodeKontakpterson($okp->getId(), $okp->getMobil(), $okp->getEpost());
            return $existedOkp;
        } catch( Exception $e ) {
            if($e->getCode() != 562007) {
                throw $e;
            }
            // Personen finnes ikke, fortsett
        }


        // Opprett kontaktpersonen
        $sql = new Insert(OmradeKontaktpersoner::TABLE);
        $sql->add('mobil', $okp->getMobil());
        $sql->add('fornavn', $okp->getFornavn());
        $sql->add('etternavn', $okp->getEtternavn());
        $sql->add('beskrivelse', $okp->getBeskrivelse());
        $sql->add('epost', $okp->getEpost());
        $sql->add('eier_omrade_id', $okp->getEierOmradeId());
        $sql->add('eier_omrade_type', $okp->getEierOmradeType());
        $sql->add('profile_image_url', $okp->getProfileImageUrl());

        $res = $sql->run();

        $retOkp = new OmradeKontaktperson([
            'id' => $res,
            'mobil' => $okp->getMobil(),
            'fornavn' => $okp->getFornavn(),
            'etternavn' => $okp->getEtternavn(),
            'beskrivelse' => $okp->getBeskrivelse(),
            'epost' => $okp->getEpost(),
            'eier_omrade_id' => $okp->getEierOmradeId(),
            'eier_omrade_type' => $okp->getEierOmradeType(),
            'profile_image_url' => $okp->getProfileImageUrl()
        ]);

        return $retOkp;
    }

    /**
     * Hent en områdekontaktperson
     *
     * @param Int $id
     * @param String|null $mobil
     * @param String|null $epost
     * @throws Exception
     * @return OmradeKontaktperson
     */
    private static function getOmrodeKontakpterson(int $id, $mobil = null, $epost = null) {
        $where = $mobil != null && preg_match('/^\d{8}$/', $mobil) ? " WHERE `mobil` = '#mobil'" : " WHERE `id` = '#id'";
        $where .= $epost != null ? " OR `epost` = '#epost'" : '';

        $query = new Query(
            "SELECT * FROM `". OmradeKontaktpersoner::TABLE ."`" . $where,
            [
                'id' => $id,
                'mobil' => $mobil,
                'epost' => $epost
            ]
        );

        $res = $query->run('array');

        if( $res == null ) {
            throw new Exception(
                'Kontaktpersonen finnes ikke',
                562007
            );
        }

        return new OmradeKontaktperson($res);
    }

    /**
     * Legg til en områdekontaktperson til et område
     *
     * @param Omrade $omrade
     * @param OmradeKontaktperson $omradeKontaktperson
     * @throws Exception
     * @return Bool
     */
    public static function leggTilOmradeKontaktperson( Omrade $omrade, OmradeKontaktperson $omradeKontaktperson ) {
        $okp = null;

        // Ny kontaktperson, må opprettes først
        if($omradeKontaktperson->getId() == -1) {
            try{
                $okp = self::createOmradekontaktperson($omradeKontaktperson);
            } catch( Exception $e ) {
                throw $e;
            }
        }
        else {
            $okp = $omradeKontaktperson;
        }

        // Sjekk tilgang kun til området men ikke eier området. Man kan legge til kontaktpersoner til andre områder
        try{
            self::checkAccessToOmrade($omrade);
        } catch( Exception $e ) {
            throw $e;
        }

        // TODO: Sjekk om kontaktpersonen allerede er lagt til området, aktiver is_active
        $sqlCheck = new Query(
            "SELECT id FROM `". OmradeKontaktpersoner::OMRADE_RELATION_TABLE ."`
            WHERE `kontaktperson_id` = '#kontaktperson_id' AND
            `omrade_id` = '#omrade_id' AND
            `omrade_type` = '#omrade_type'",
            [
                'kontaktperson_id' => $okp->getId(),
                'omrade_id' => $omrade->getForeignId(),
                'omrade_type' => $omrade->getType()
            ]
        );

        $resCheck = $sqlCheck->run('array');
        if($resCheck != null) {
            // Kontaktpersonen er allerede lagt til området fra før, aktiver is_active
            $sqlRel = new Update(
                OmradeKontaktpersoner::OMRADE_RELATION_TABLE,
                [
                    'kontaktperson_id' => $okp->getId(),
                    'omrade_id' => $omrade->getForeignId(),
                    'omrade_type' => $omrade->getType()
                ]
            );
            $sqlRel->add('is_active', 1);
        }
        else {
            $sqlRel = new Insert(OmradeKontaktpersoner::OMRADE_RELATION_TABLE);
            $sqlRel->add('kontaktperson_id', $okp->getId());
            $sqlRel->add('omrade_id', $omrade->getForeignId());
            $sqlRel->add('omrade_type', $omrade->getType());       
        }



        try {
            $resRel = $sqlRel->run();
        } catch( Exception $e ) {
            if($e->getCode() != 901001) {
                throw 'Klarte ikke å lagre relasjonen. Feilmelding: ' . $e;
            }
        }

        return $resRel != null;
    }

    /**
     * Opprett en ny områdekontaktperson
     *
     * @param OmradeKontaktperson $okp
     * @throws Exception
     * @return OmradeKontaktperson
     */
    public static function editOmradekontaktperson(OmradeKontaktperson $okp) {
        // Sjekk tilgang
        try{
            self::checkAccess($okp);
        } catch( Exception $e ) {
            throw $e;
        }

        // Brukeren har tilgang til område, oppdater kontaktpersonen
        $query = new Update(
            OmradeKontaktpersoner::TABLE,
            [
                'id' => $okp->getId()
            ]
        );
        $query->add('fornavn', $okp->getFornavn());
        $query->add('etternavn', $okp->getEtternavn());
        $query->add('beskrivelse', $okp->getBeskrivelse());
        // $query->add('epost', $okp->getEpost()); // Epost kan ikke redigeres, brukes som identifikator
        $query->add('profile_image_url', $okp->getProfileImageUrl());

        $query->run();

        return $okp;
    }

    public static function connectOmradekontaktpersonTilWPUser(OmradeKontaktperson $okp, Int $wp_user_id) {
        // Sjekk tilgang
        try{
            self::checkAccess($okp);
        } catch( Exception $e ) {
            throw $e;
        }

        // Brukeren har tilgang til område, oppdater kontaktpersonen
        $setValidated = new Update(
            OmradeKontaktpersoner::TABLE,
            [
                'id' => $okp->getId()
            ]
        );
        $setValidated->add('wp_user_id', $okp->getWpUserId());
        $setValidated->run();

        return $okp;
    }

    /**
     * Sjekk om brukeren har tilgang til området for å redigere kontaktpersonenen
     *
     * @param OmradeKontaktperson $okp
     * @throws Exception
     * @return Bool
     */
    private static function checkAccess(OmradeKontaktperson $okp) {
        $omradeId = null;
        $omradeType = null;    
        
        // Ny kontaktperson
        if($okp->getId() == -1) {
            // Hent område fra kontaktpersonen
            $omradeId = $okp->getEierOmradeId();
            $omradeType = $okp->getEierOmradeType();
        }
        else {
            // Hent brukeren fra database og eierområde
            $query = new Query(
                "SELECT id, eier_omrade_id, eier_omrade_type
                FROM `". OmradeKontaktpersoner::TABLE ."`
                WHERE 
                `id`= '#id' OR `mobil` = '#mobil' OR `epost` = '#epost'",
                [
                    'id' => $okp->getId(),
                    'mobil' => $okp->getMobil() ?? -1,
                    'epost' => $okp->getEpost() ?? -1
                ]
            );
    
            $res = $query->run('array');
            if( $res == null ) {
                throw new Exception(
                    'Kontaktpersonen finnes ikke og derfor kan ikke redigeres',
                    562005
                );
            }

            $omradeId = $res['eier_omrade_id'];
            $omradeType = $res['eier_omrade_type'];
        }
        

        $access = false;
        $access = self::checkAccessToOmrade(Omrade::getByType($omradeType, $omradeId));

        // Legg til flere access typer når det støttes flere områder

        if($access == false) {
            throw new Exception(
                'Du har ikke tilgang til området og kan derfor ikke redigere kontaktpersonen',
                562006
            );
        }
        return true;
    }

    private static function checkAccessToOmrade(Omrade $omrade) : bool{
        return AccessControlArrSys::hasOmradeAccess($omrade);
    }
}