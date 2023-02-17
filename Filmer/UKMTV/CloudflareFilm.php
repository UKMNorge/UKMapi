<?php

namespace UKMNorge\Filmer\UKMTV;

use UKMCURL;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Filmer\UKMTV\Server\Server;
use UKMNorge\Filmer\UKMTV\Tags\Tags;
use UKMNorge\Filmer\UKMTV\Tags\Personer;
use UKMNorge\Http\Curl;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Geografi\Kommuner;
use UKMNorge\Geografi\Kommune;
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
    private $erSlettet = null;
    private $tags = null;
    private $kommuner = null;

    public function __construct(array $data) {
        $this->id = (int)$data['id'];
        $this->title = (string)$data['title'];
        $this->description = (string)$data['description'];
        $this->cloudflareId = (string)$data['cloudflare_id'];
        $this->cloudflareLenke = (string)$data['cloudflare_lenke'];
        $this->cloudflareThumbnail = (string)$data['cloudflare_thumbnail'];
        $this->arrangementId = (int)$data['arrangement'];
        $this->innslagId = (string)$data['innslag'];
        $this->sesong = (string)$data['sesong'];
        $this->arrangementType = (string)$data['arrangement_type'];
        $this->fylkeId = (int)$data['fylke'];
        $this->erSlettet = $data['deleted'] ? $data['deleted'] : false;
    }

    /**
     * Hent alle tags i filmen
     *
     * @return Tags
     */
    public function getTags() {
        if($this->tags == null) {
            $this->fetchTags();
        }
        return $this->tags;
    }

    /**
     * Add tag
     * Legger til tag men lagrer ikke det i DB
     * 
     * @return void
     */
    public function addTag(String $type, String $foreignKey) {
        $this->getTags()->opprett($type, $foreignKey);
    }

    /**
     * Fetch all the tags from database
     *
     * @return void
     */
    private function fetchTags() {
        $this->tags = new Tags();

        $query = new Query(
            "SELECT *
            FROM `ukm_tv_tags` 
            WHERE is_cloudflare=true AND tv_id=#tv_id",
            [
                'tv_id' => $this->id,
            ]
        );

        $res = $query->run();
        while ($r = Query::fetch($res)) {
            $this->tags->opprett($r['type'], $r['foreign_id']);
        }
    }
    
    /**
     * Hent alle personer i filmen
     *
     * @return Kommuner
     */
    public function getKommuner() {
        if($this->kommuner == null) {
            $this->fetchKommuner();
        }
        return $this->kommuner;
    }

    /**
     * Fetch alle kommuner i filmen
     *
     * @return Kommuner
     */
    private function fetchKommuner() {
        $this->kommuner = new Kommuner;
        
        $query = new Query(
            "SELECT *
            FROM `cloudflare_videos_kommune` 
            WHERE cloudflarefilm_id=#id",
            [
                'id' => $this->id,
            ]
        );
    
        $res = $query->run();
        while ($r = Query::fetch($res)) {
            $this->kommuner->add(new Kommune($r['kommune_id']));
        }
        
        return $this->kommuner;
    }

    /**
     * Hent alle personer i filmen
     *
     * @return Personer
     */
    public function getPersoner()
    {
        return $this->getTags()->getPersoner();
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
        return "SELECT * from `cloudflare_videos`";
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

    public function setId(Int $id) {
        if($this->id == -1) {
            $this->id = $id;
        }
        else {
            throw new Exception("id kan ikke settes til ekte CloudflareFilmer");
        }
    }

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
}
