<?php

namespace UKMNorge\Filmer\UKMTV;

use UKMCURL;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Filmer\UKMTV\Server\Server;
use UKMNorge\Filmer\UKMTV\Tags\Tags;
use UKMNorge\Filmer\UKMTV\Tags\Personer;
use UKMNorge\Http\Curl;
use Exception;

class CloudflareFilm {
    private $id = null;
    private $title = null;
    private $description = null;
    private $cloudflareId = null;
    private $cloudflareLenke = null;
    private $cloudflareThumbnail = null;
    private $arrangementId = null;
    private $innslagId = null;
    private $season = null;

    public function __construct(Int $id, String $title, String $description, String $cloudflareId, String $cloudflareLenke, String $cloudflareThumbnail, Int $arrangementId, String $innslagId, String $season) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->cloudflareId = $cloudflareId;
        $this->cloudflareLenke = $cloudflareLenke;
        $this->cloudflareThumbnail = $cloudflareThumbnail;
        $this->arrangementId = $arrangementId;
        $this->innslagId = $innslagId;
        $this->season = $season;
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
     * Hent filmens id
     *
     * @return Int id
     */
    public function getId() {
        return $this->id;
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

    /**
     * Hent preview-bildets (thumbnail)
     *
     * @return String
     */
    public function getThumbnail() {
        return $this->cloudflareThumbnail;
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
     * @return void
     */
    public function getSeason() {
        return $this->season;
    }
}
