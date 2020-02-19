<?php

namespace UKMNorge\Innslag\Media\Bilder;

use UKMNorge\Database\SQL\Query;
use UKMNorge\Innslag\Media\Samling as MediaSamling;
use Exception;

require_once('UKM/Autoloader.php');

class Samling extends MediaSamling
{
    /**
     * Har innslaget et valgt bilde
     *
     * @param Int $tittel
     * @return Bool
     */
    public function harValgt(Int $tittel = 0)
    {
        try {
            $this->getvalgt($tittel);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Hent enten det valgte bildet, eller det første
     *
     * @return Bilde
     * @throws Exception Not found
     */
    public function getValgtOrFirst()
    {
        try {
            return $this->getValgt(false);
        } catch (Exception $e) {
            try {
                return $this->getFirst();
            } catch (Exception $inner_e) {
                throw new Exception(
                    'Innslaget har ingen bilder',
                    321006
                );
            }
        }
    }

    /**
     * Hent det bildet som er valgt ved videresending
     * For kunstner-bilde: velg tittel = 0
     * For ett gitt valgt bilde, uavhengig av tittel: input bool false
     * 
     * @param $tittel [0 | Integer | false]
     **/
    public function getValgt($tittel = 0)
    {
        $sql = new Query(
			"SELECT `bilde_id` 
			FROM `smartukm_videresending_media`
			WHERE `b_id` = '#innslag'
			" . ($tittel === false ? '' : "AND `t_id` = '#tittel'"),
            [
                'innslag'    => $this->getInnslagId(),
                'tittel'    => $tittel,
            ]
        );
       # echo $sql->debug();
        return $this->find($sql->getField());
    }

    /**
     * Last inn innslagets bilder
     *
     * @return void
     */
    public function _load()
    {
        $SQL = new Query(
            "SELECT * FROM `ukmno_wp_related`
            JOIN `ukm_bilder` 
                ON (`ukmno_wp_related`.`post_id` = `ukm_bilder`.`wp_post` 
                    AND `ukmno_wp_related`.`b_id` = `ukm_bilder`.`b_id`
                )
            WHERE `ukmno_wp_related`.`b_id` = '#innslag_id'
            AND `post_type` = 'image'",
            [
                'innslag_id' => $this->getInnslagId()
            ]
        );
        $get = $SQL->run();
        if (!$get) {
            throw new Exception(
                'Kunne ikke hente inn bilder for innslag ' . $this->getInnslagId(),
                321007
            );
        }
        while ($row = Query::fetch($get)) {
            $this->add(new Bilde($row));
        }
    }
}