<?php

namespace UKMNorge\Filmer\UKMTV;

use UKMCURL;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Filmer\UKMTV\Server\Server;
use UKMNorge\Filmer\UKMTV\Tags\Tags;
use UKMNorge\Filmer\UKMTV\Tags\Personer;
use UKMNorge\Http\Curl;
use Exception;

class CloudflareFilm implements FilmInterface {
    private $id = null;
    private $title = null;
    private $description = null;
    private $cloudflareId = null;
    private $cloudflareLenke = null;
    private $cloudflareThumbnail = null;
    private $arrangementId = null;
    private $innslagId = null;
    private $sesong = null;
    private $arrangementType = null;
    private $fylkeId = null;
    private $kommuneId = null;
    private $personId = null;
    private $erSlettet = null;

    public function __construct(
            Int $id, 
            String $title, 
            String $description, 
            String $cloudflareId, 
            String $cloudflareLenke, 
            String $cloudflareThumbnail, 
            Int $arrangementId, 
            String $innslagId, 
            String $sesong,
            String $arrangementType,
            Int $fylkeId,
            Int $kommuneId,
            Int $personId,
            Bool $erSlettet = false) 
        {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->cloudflareId = $cloudflareId;
        $this->cloudflareLenke = $cloudflareLenke;
        $this->cloudflareThumbnail = $cloudflareThumbnail;
        $this->arrangementId = $arrangementId;
        $this->innslagId = $innslagId;
        $this->sesong = $sesong;
        $this->erSlettet = $erSlettet;
        $this->$arrangementType = $arrangementType;
        $this->$fylkeId = $fylkeId;
        $this->$kommuneId = $kommuneId;
        $this->$personId = $personId;
    }

    /**
     * Hent alle personer i filmen
     *
     * @return Personer
     */
    public function getPersoner() {
        throw new Exception('må implementeres');
        return null;
    }

    /**
     * Hent SQL-spørringen som brukes for å hente ut relevante felt
     * 
     * For å begrense treff, kan du gjerne slenge på 
     * WHERE / AND `tv_deleted` = 'false'
     *
     * @return String SQL query
     */
    public static function getLoadQuery() {
        return
            "SELECT *,
            (
                SELECT GROUP_CONCAT( CONCAT(`ukm_tv_tags`.`type`,':',`ukm_tv_tags`.`foreign_id` ) SEPARATOR '|')
                FROM `ukm_tv_tags`
                WHERE `ukm_tv_tags`.`tv_id` = `ukm_tv_files`.`tv_id`
            ) AS `tags`
            FROM `ukm_tv_files`";
    }

    /**
     * Sett filmens id
     *
     * @param Int $id
     * @return self
     */
    public function setTvId(Int $id) {
        $this->id = $id;
        return $this;
    }

    /**
     * Er filmen slettet
     *
     * @return bool $erSlettet
     */
    public function erSlettet() {
        return $this->erSlettet;
    }

    /**
     * Hent hvilken cronId converteren ga filmen
     * 
     * Brukes kun av filmer som har vært gjennom videoconverter.ukm.no
     *
     * @return Int|null
     */
    public function getCronId() {
        return null;
    }

    /**
     * Hent filmens id
     *
     * @return Int id
     */
    public function getTvId() {
        return $this->getId();
    }

    public function getId() {
        return $this->id;
    }

    /**
     * Hent filmens tags
     *
     * @return Tags
     */
    public function getTags() {
        return null;
    }

    /**
     * Hent filmens CloudFlare Stream id
     *
     * @return Int id
     */
    public function getCloudflareId() {
        return $this->cloudflareId;
    }

    /**
     * Hent hvilket arrangement som lastet opp filmen
     *
     * @return Int
     */
    public function getArrangementId() {
        return $this->arrangementId;
    }

    public function getInnslagId() {
        return $this->innslagId;
    }

    /**
     * Hent filmens tittel
     *
     * @return String
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Hent filmens beskrivelse
     *
     * @return String
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Hent filmens path
     *
     * @return String
     */
    public function getUrl() {
        return $this->cloudflareLenke;
    }

    public function getTvUrl() {
        return $this->getUrl();
    }

    /**
     * Hent filmens path (inkl filnavn) på videostorage (path, ikke URL)
     *
     * @return String
     */
    public function getFilePath() {
        return $this->getUrl();
    }

    /**
     * Hent preview-bildets (thumbnail)
     *
     * @return String
     */
    public function getThumbnail() {
        return $this->cloudflareThumbnail;
    }

    /**
     * Hent preview-bildets path på videostorage (path, ikke URL)
     *
     * @return String
     */
    public function getImagePath() {
        return $this->getThumbnail();
    }

    public function getBildeUrl() {
        return $this->getThumbnail();
    }

    /**
     * Hent bilde-URL
     *
     * @return String url
     */
    public function getImageUrl() {
        return $this->getImagePath();
    }

    /**
     * Hent filmens sesong
     *
     * @return String
     */
    public function getSesong() {
        return $this->sesong;
    }

    /**
     * Hent filmens sesong
     *
     * @return String
     */
    public function getSeason() {
        return $this->getSesong();
    }

    /**
     * Hent filmens arrangement type
     *
     * @return String
     */
    public function arrangementType() {
        return $this->arrangementType;
    }

    /**
     * Hent filmens fylke id
     *
     * @return Int
     */
    public function getFylkeId() {
        return $this->fylkeId;
    }

    /**
     * Hent filmens kommune id
     *
     * @return Int
     */
    public function getKommuneId() {
        return $this->kommuneId;
    }
   
    /**
     * Hent filmens kommune id
     *
     * @return Int
     */
    public function getPersonId() {
        return $this->personId;
    }
}
