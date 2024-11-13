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
    private $tag_string = '';
    private $erReportasje = null;

    public function __construct(array $data, $id=null) {
        if($id == null) {
            $this->constructFromData($data);
        }
        else {
            $this->tag_string = !empty($data['tags']) ? $data['tags'] : '';

            $cfQuery = new Query(
                "SELECT *
                FROM `cloudflare_videos` 
                WHERE id=#id AND `deleted` = 'false'",
                [
                    'id' => $id
                ]
            );
            $this->constructFromData($cfQuery->getArray());
        }
    }

    private function constructFromData($data) {
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
        $this->erReportasje = $data['erReportasje'] ? $data['erReportasje'] : false;
        return $this;
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
        return "SELECT cloudflare_videos.id, cloudflare_videos.cloudflare_id, cloudflare_videos.cloudflare_lenke, cloudflare_videos.cloudflare_thumbnail, cloudflare_videos.title, cloudflare_videos.description, cloudflare_videos.arrangement, cloudflare_videos.innslag, cloudflare_videos.sesong, cloudflare_videos.arrangement_type, cloudflare_videos.fylke, cloudflare_videos.erReportasje, cloudflare_videos.deleted from `cloudflare_videos`";
    }

    /**
     * Hent tag-verdi for gitt tag
     *
     * @param String $tag
     * @return Any verdi av tag
     */
    public function getTag(String $tag)
    {
        if ($this->getTags()->har($tag)) {
            return $this->getTags()->get($tag);
        }
        return false;
    }

    /**
     * Hent alle tags
     *
     * @return Tags
     */
    public function getTags()
    {
        if (null == $this->tags) {
            $this->tags = Tags::createFromString($this->tag_string);
        }

        return $this->tags;
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
        return Server::getTvUrl() .'watch/film/'. $this->getCloudflareId();// . $this->getSanitizedTitle() . '/' . $this->getId();
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
        return $this->cloudflareThumbnail. '?time=3s';
    }

    public function getThumbnailShare() {
        return $this->cloudflareThumbnail. '&height=360';
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
     * Hent true hvis filmen er reportasje
     *
     * @return Boolean
     */
    public function erReportasje() {
        return $this->erReportasje;
    }
    
    /**
     * Hent HTML-kode for embedding av UKM-TV
     *
     * @return String html iframe
     */
    public function getEmbedHtml(String $class = null, String $style = null) {
        return Html::getEmbed($this, $class, $style);
    }
    
    /**
     * Hent filmens Embed-URL (brukes av embedkoder)
     *
     * @return String Url
     */
    public function getEmbedUrl() {
        return $this->getUrl();
    }

    /**
     * Returnerer storage base for filmen
     *
     * @return String 'videoserver' eller 'cloudflare'
     */
    public function getStorageBase() {
        return 'cloudflare';
    }
}
