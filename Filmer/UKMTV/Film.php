<?php

namespace UKMNorge\Filmer\UKMTV;

use UKMCURL;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Filmer\UKMTV\Server\Server;
use UKMNorge\Filmer\UKMTV\Tags\Tags;
use UKMNorge\Filmer\UKMTV\Tags\Personer;
use UKMNorge\Http\Curl;

use Exception;

class Film implements FilmInterface
{
    var $id = 0;
    var $cron_id = null;
    var $file = null;
    var $title = null;
    var $sanitized_title = null;
    var $description = null;

    var $image_url = null;

    var $tags = null;
    var $tag_string = null;

    var $ext = null;
    var $file_exists_720p = null;
    var $did_check_for_720p = false;

    var $file_exists_smil = false;
    var $smil_file = null;

    var $slettet = false;

    var $arrangement_id;
    var $season;
    var $innslag_id;

    public function __construct(array $data, $createEmpty = false)
    {
        if ($createEmpty) {
            return;
        }

        $this->id = intval($data['tv_id']);
        $this->cron_id = intval($data['cron_id']);
        $this->arrangement_id = intval($data['pl_id']);
        $this->innslag_id = intval($data['b_id']);
        $this->season = intval($data['season']);

        $this->title = $data['tv_title'];
        $this->description = $data['tv_description'];

        $this->slettet = $data['tv_deleted'] != 'false';
        $this->image_url = $data['tv_img'];

        $this->file_exists_smil = $data['file_exists_smil'] == 'true';

        $this->tag_string = !empty($data['tags']) ? $data['tags'] : '';

        // Vet vi at denne finnes med en 720p-utgave? (pre 2013(?)-problem)
        $this->file_exists_720p = $data['file_exists_720p'] == 1;

        // De forskjellige fil-utgavene
        $this->setFile($data['tv_file']);
    }

    public static function convertFromCloudflare(CloudflareFilm $cfFilm) : Film {
        // Legger til 'cf_' fordi dette bruker for å idneitifere overførte Cloudflare filmer
        $filePath = 'ukmno/videos/migrated/cf_' . $cfFilm->getCloudflareId() . '.mp4';
        $thumbnailPath = 'ukmno/videos/migrated/' . $cfFilm->getCloudflareId() . '.jpg';

        $film = new Film([], true);
        $film->setFileAsItIs($filePath);
        $film->setImageUrl($thumbnailPath);
        $film->setTitle($cfFilm->getTitle());
        $film->setDescription($cfFilm->getDescription());
        $film->setTagsString($cfFilm->getTagsString());
        $film->setArrangementId($cfFilm->getArrangementId());
        $film->setInnslagId($cfFilm->getInnslagId());
        $film->setSeason($cfFilm->getSeason());
        $film->setCronId(999999);

        try{
            Write::save($film);
        } catch(Exception $e) {
            var_dump($e);
        }
        return $film;
    }

    /**
     * Hent metadata om filmen til bruk for opengraph
     *
     * @return Array $opengraph keyval data
     */
    public function getMeta()
    {
        return [
            'og:type' => 'video.other',
            'og:url' => $this->getTvUrl(),
            'og:image' => $this->getBildeUrl(),
            'og:title' => $this->getNavn(),
            'og:description' => $this->getSet(),
            'video:actor' => 'https://facebook.com/UKMNorge',
            'video:tag' => 'UKM-TV UKM UKM Norge'
        ];
    }

    /**
     * Hent filmens TV-URL (lenke til UKM-TV)
     *
     * @return String Url
     */
    public function getTvUrl()
    {
        return Server::getTvUrl() . $this->getSanitizedTitle() . '/' . $this->getId();
    }

    /**
     * Hent filmens Embed-URL (brukes av embedkoder)
     *
     * @return String Url
     */
    public function getEmbedUrl()
    {
        return Server::getEmbedUrl() . $this->getSanitizedTitle() . '/' . $this->getId();
    }

    /**
     * Hent URL til preview-bilde
     *
     * @return String Url
     */
    public function getBildeUrl()
    {
        return Server::getStorageUrl() . $this->image_url;
    }

    /**
     * Hent filmens tittel
     *
     * @return String tittel
     */
    public function getNavn()
    {
        return $this->title;
    }

    /**
     * Sett filmens tittel
     *
     * @param String $title
     * @return self
     */
    public function setTitle(String $title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Hent filmens beskrivelse
     *
     * @return String beskrivelse
     */
    public function getBeskrivelse()
    {
        return $this->description;
    }

    /**
     * Sett filmens beskrivelse
     *
     * @param String $description
     * @return self
     */
    public function setDescription(String $description)
    {
        $this->description = $description;
        return $this;
    }
    

    /**
     * Hent sanitized tittel
     *
     * @return String sanitized title
     */
    public function getSanitizedTitle()
    {
        if (null == $this->sanitized_title) {
            $this->sanitized_title = static::sanitizeTitle($this->getNavn());
        }
        return $this->sanitized_title;
    }

    /**
     * Har filmen en .smil-fil?
     *
     * @return bool
     */
    public function harSmil()
    {
        return $this->file_exists_smil;
    }

    /**
     * Hent URL til smil-filen
     *
     * @return String $url
     */
    public function getSmilFile()
    {
        if (null == $this->smil_file) {
            $this->smil_file = str_replace('_720p.mp4', '.smil', $this->getFile());
        }
        return $this->smil_file;
    }

    /**
     * Hent full filbane (inkl navn) til filen
     * 
     * Hvis vi ikke vet at det finnes en 720p-fil, kan vi anta dette,
     * og sjekke det med storage-server, i tilfelle vi har riktig.
     * (For da vil vi mye heller bruke den fila, som er bedre)
     *
     * @return String full filbane (inkl navn)
     */
    public function getFile()
    {

        if (!$this->har720p() && !$this->did_check_for_720p) {
            $this->_checkFor720p();
        }

        // UKM-TV er i low bandwidth-mode. 
        // Returner mobile-filen i stedet for 720p-filen
        // hvis 720p-utgaven finnes (for ellers har vi heller ikke mobil)
        if ($this->har720p() && Server::erSparemodus()) {
            $this->file = $this->file_mobile;
            $this->file_exists_smil = false;
        }

        if (!strpos($this->file, '_720p') && $this->har720p()) {
            return str_replace('.mp4', '_720p.mp4', $this->file);
        }

        return $this->file;
    }

    /**
     * Sett file bane uten å endre filnavn
     *
     * @param String $url
     * @return void
     */
    public function setFileAsItIs(String $file) {
        $this->file = $file;
    }

    /**
     * Sett full filbane til filen
     *
     * @param String $full_filepath
     * @return void
     */
    public function setFile(String $full_filepath)
    {
        $this->file         = str_replace('///', '/', $full_filepath);
        $lastslash          = strrpos($this->file, '/');
        $this->file_path    = substr($this->file, 0, $lastslash);
        $this->file_name    = substr($this->file, $lastslash + 1);
        if( strpos($this->file, '_720p') === false ) {
            $this->file_720p    = str_replace('.mp4', '_720p.mp4', $this->file);
            $this->file_mobile  = $this->file;
        } else {
            $this->file_720p    = $this->file;
            $this->file_mobile    = str_replace('_720p.mp4', '_mobile.mp4', $this->file_720p);
        }
    }

    /**
     * Hent mobilfilens navn
     *
     * @return String
     */
    public function getFileMobile(){
        return basename($this->file_mobile);
    }

    /**
     * Hent 720p-filens navn
     *
     * @return String
     */
    public function getFile720() {
        return basename($this->file_720p);
    }

    /**
     * Hent filmens TV-id
     *
     * @return Int $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Er filmen merket som slettet?
     *
     * @return Bool
     */
    public function erSlettet()
    {
        return $this->slettet;
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
     * @return Array<Any>
     */
    public function getTags()
    {
        if (null == $this->tags) {
            $this->tags = Tags::createFromString($this->tag_string);
        }

        return $this->tags;
    }

    /**
     * Sett filmens tags
     *
     * @param Tags $tags
     * @return self
     */
    public function setTagsString(String $tagsString)
    {
        $this->tag_string = $tagsString;
        return $this;
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
     * Hent filens navn (uten path)
     *
     * @return String $navn
     */
    public function getFilename()
    {
        return $this->file_name;
    }

    public function getTagsString() : String {
        return $this->tag_string;
    }

    /**
     * Hent filename tilpasset Mist server
     *
     * @return String $navn
     */
    public function getPathMistsServer()
    {
        if($this->har720p()) {
            $filnavn = $this->getFile720();
        } else {
            $filnavn = $this->getFilename();
        }
        $pathinfo = pathinfo($filnavn);

        if ($pathinfo['extension'] == 'flv') {
            $pathinfo['extension'] = 'mp4';
        }
        $filnavn = $pathinfo['filename'] . "." . $pathinfo['extension'];
    
        return $filnavn;
    }
    /**
     * Hent filens path (uten navn)
     *
     * @return String $path
     */
    public function getServerFilepath()
    {
        return rtrim($this->file_path,'/').'/';
    }

    /**
     * Vet vi at filen finnes i 720p-utgave?
     *
     * @return bool
     */
    public function har720p()
    {
        return $this->file_exists_720p;
    }

    /**
     * Hent filens extension
     *
     * @return String file extension (.mp4 basically?)
     */
    public function getExtension()
    {
        if (null == $this->ext) {
            // Jobber direkte på $this->file for å ikke trigge
            // unødvendig sjekk av 720p-utgave
            // (som $this->getFile() kan finne på å gjøre)
            $this->ext = substr($this->file, strrpos($this->file, '.'));
        }
        return $this->ext;
    }

    /**
     * Sjekk videostorage om det finnes 720p-utgave av fila?
     * 
     * Filnavnet lagret i databasen inneholder aldri `_720p` e.l.,
     * men er lagret som det rene filnavnet (uten extension).
     * 
     * Når vi sender dette til videostorage, svarer den tilbake med 
     * A) tilsendt filnavn hvis det ikke finnes 720p-fil
     * B) tilsendt filnavn + _720p hvis det finnes 720p-fil
     * Vi sjekker derfor om returnert filnavn inneholder _720p, og lagrer
     * dette i databasen, så serverne slipper å snakke så mye sammen (#introvert).
     *
     * @return void
     */
    private function _checkFor720p()
    {
        $curl = new Curl();
        $data = $curl->request(
            Server::getStorageUrl()
                . 'find.php'
                . '?file=' . $this->getFilename()
                . '&path=' . urlencode($this->getServerFilepath())
        );

        if( !isset( $data->filepath )) {
            return true;
        }

        $this->setFile($data->filepath);

        // Returnert fil inneholdt 720p, som betyr at den finnes. 
        // Lagre så vi vet det til senere (score!)
        if (strpos($data->filepath, '720p') !== false) {
            $SQL = new Update(
                'ukm_tv_files',
                [
                    'tv_id' => $this->id
                ]
            );
            $SQL->add('file_exists_720p', 1);
            $SQL->add('tv_file', $curl->data->filepath);
            $SQL->run();
        }
        $this->did_check_for_720p = true;
    }


    /**
     * Hent filmens bredde
     * 
     * @todo: IMPLEMENTER SKIKKELIG!
     *
     * @return Int
     */
    public function getWidth() {
        return 1280;
    }

    /**
     * Hent filmens høyde
     * 
     * @todo: IMPLEMENTER SKIKKELIG!
     *
     * @return Int
     */
    public function getHeight() {
        return 720;
    }

    /**
     * Registrer en avspilling
     *
     * @return void
     */
    public function play()
    {
        return Avspilling::play($this);
    }
    /**
     * Hent antall avspillinger for denne filmen
     *
     * @return Int antall avspillinger
     */
    public function getPlayCount()
    {
        return Avspilling::getAntall($this);
    }



    /**
     * Hent HTML-kode for embedding av UKM-TV
     *
     * @return String html iframe
     */
    public function getEmbedHtml(String $class = null, String $style = null)
    {
        return Html::getEmbed($this, $class, $style);
    }

    /**
     * Hent HTML-utgaven av metadata
     *
     * @return String html metadata
     */
    public function getMetaHtml()
    {
        return Html::getMeta($this);
    }



    /**
     * Sanitize tittel for bruk i URL f.eks.
     *
     * @param String $title
     * @return String sanitized title
     */
    public static function sanitizeTitle(String $title)
    {
        // Bruk kun første del av tittelen 
        // Typisk er innslagsnavnet før dash, mens tittelen er etter,
        // og vi vet ikke hvilken tittel filmen tilhører.
        $dashpos = strpos($title, ' - ');
        if (!$dashpos) {
            $dashpos = strlen($title);
        }

        // Sikre at tittelen er innenfor mtp URL-bruk
        $sanitized = static::sanitize(
            substr($title, 0, $dashpos)
        );
        if (empty($sanitized)) {
            $sanitized = 'Ukjent';
        }

        return $sanitized;
    }

    /**
     * Fjern uønskede karakterer for URL i string
     *
     * @param String $string
     * @return String sanitized string
     */
    public static function sanitize(String $string)
    {
        return preg_replace(
            '/[^a-z0-9A-Z-_]+/',
            '',
            str_replace(' ', '-', $string)
        );
    }

    /**
     * Hent SQL-spørringen som brukes for å hente ut relevante felt
     * 
     * For å begrense treff, kan du gjerne slenge på 
     * WHERE / AND `tv_deleted` = 'false'
     *
     * @return String SQL query
     */
    public static function getLoadQuery()
    {
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
     * Sett filmens TV-ID
     *
     * @param Int $tv_id
     * @return self
     */
    public function setTvId(Int $tv_id)
    {
        $this->id = $tv_id;
        return $this;
    }

    /**
     * Hent filmens TV-ID
     *
     * @return Int tv_id
     */
    public function getTvId()
    {
        return $this->getId();
    }

    /**
     * Hent hvilken cronId converteren ga filmen
     *
     * @return Int|null
     */
    public function getCronId()
    {
        return $this->cron_id;
    }

    public function setCronId(Int $cron_id)
    {
        $this->cron_id = $cron_id;
        return $this;
    }

    /**
     * Hent hvilket arrangement som lastet opp filmen
     *
     * @return Int
     */
    public function getArrangementId()
    {
        return $this->arrangement_id;
    }

    public function setArrangementId(Int $arrangement_id)
    {
        $this->arrangement_id = $arrangement_id;
        return $this;
    }

    public function getInnslagId()
    {
        return $this->innslag_id;
    }

    public function setInnslagId(Int $innslag_id)
    {
        $this->innslag_id = $innslag_id;
        return $this;
    }

    /**
     * Hent filmens tittel
     *
     * @return String
     */
    public function getTitle()
    {
        return $this->getNavn();
    }

    /**
     * Hent filmens beskrivelse
     *
     * @return String
     */
    public function getDescription()
    {
        return $this->getBeskrivelse();
    }

    /**
     * Hent filmens path på videostorage (inkl filnavn)
     *
     * @return String
     */
    public function getFilePath()
    {
        return $this->file;
    }

    /**
     * Hent preview-bildets path på videostorage
     *
     * @return String
     */
    public function getImagePath()
    {
        return $this->image_url;
    }

    /**
     * Sett preview-bildets path på videostorage
     *
     * @param String $image_path
     * @return self
     */
    public function setImageUrl(String $url)
    {
        $this->image_url = $url;
        return $this;
    }

    /**
     * Hent bilde-URL
     *
     * @return String url
     */
    public function getImageUrl()
    {
        return Server::getStorageUrl() . $this->getImagePath();
    }

    /**
     * Hent filmens sesong
     *
     * @return void
     */
    public function getSeason()
    {
        return $this->season;
    }

    /**
     * Set filmens sesong
     *
     */
    public function setSeason(int $season) {
        $this->season = $season;
        return $this;
    }

    /**
     * Returnerer storage base for filmen
     *
     * @return String 'videoserver' eller 'cloudflare'
     */
    public function getStorageBase() {
        return 'videoserver';
    }
}
