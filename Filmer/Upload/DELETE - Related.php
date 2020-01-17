<?php

namespace UKMNorge\Filmer\Upload;

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Filmer\Server\Server;
use UKMNorge\Innslag\Innslag;

/**
 * Klassen forbereder data for Write::opprett / oppdater
 * og er en bro mellom database og Filmer\Write::opprett()
 * 
 * Henter informasjon om en film relatert til et innslag
 * (som er info vi har før filmen finnes i UKM-TV, og 
 * som er grunnlaget for sync mellom UKM-påmeldinger og UKM-tv)
 */
class Related
{

    var $tittel;
    var $post_meta;

    var $arrangement;
    var $arrangement_id;

    var $beskrivelse;
    var $fil;

    /**
     * Hent informasjon om en film fra ukmno_wp_related
     * 
     * Rad i denne tabellen finnes først etter filmen er lastet opp,
     * konvertert og registrert på UKM.no
     * 
     * Rad i denne tabellen gjør ikke at den vises i UKM-TV, og 
     * eksisterer ikke umiddelbart etter opplasting, men først når
     * filmen er registrert.
     *
     * @param Int $cronId
     * @return void
     */
    public function getByCronId(Int $cronId)
    {
        $query = new Query(
            "SELECT 
                `ukmno_wp_related`.*,
                (SELECT `pl_id` 
                    FROM `ukm_related_video`
                    WHERE `cron_id` = '#cronid'
                ) AS `pl_id` 
            FROM `ukmno_wp_related`
            WHERE `post_id` = '#cronid'
            AND `post_type` = 'video'
            LIMIT 1",
            [
                'cronid' => $cronId
            ]
        );
        return new Related($query->getArray());
    }


    /**
     * Opprett Related-objekt
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->arrangement_id = intval($data['pl_id']);
        $this->post_meta = unserialize($data['post_meta']);
        $this->fil = $this->getMeta('file');
        $this->kategori = 'todo';
        $this->tags = 'todo';
        
        $this->image = $this->getMeta('img');
        if( empty( $this->image ) ) {
            $this->image = $this->_finnBildeFraFil();
        }
        $titler = $this->getInnslag()->getTitler();
        if( $titler->getAntall() > 0 ) {
            $tittel = $titler->getAll()[0]; // 😱 this hurts. Men har alltid vært sånn..
            $this->tittel = $this->getMeta('title') .' - '. $tittel->getNavn();
            $this->beskrivelse = $tittel->getParentes();
        } else {
            $this->beskrivelse = '';
            $this->tittel = $this->getInnslag()->getNavn();
        }

    }

    /**
     * Hent filmens tittel
     *
     * @return String
     */
    public function getTittel()
    {
        return $this->tittel;
    }

    /**
     * Hent fil-bane til filmen
     *
     * @return String
     */
    public function getFil() {
        return $this->fil;
    }

    /**
     * Hent filmens beskrivelse
     *
     * @return String
     */
    public function getBeskrivelse() {
        return $this->beskrivelse;
    }

    /**
     * Prøv å gjette oss frem til bilde-banen hvis lagret info er blank
     *
     * @return String url
     */
    private function _finnBildeFraFil() {
        $video = $this->getFil();
		$ext = strrpos($video, '.');
		$img = substr($video, 0, $ext).'.jpg';
		if( $this->_img_exists($img) ) {
            return $img;
        }
        return $video.'.jpg';
    }

    /**
     * Vurl videoserver for å høre om bildet finnes
     *
     * @param String $url
     * @return Bool
     */
    private function _img_exists( String $url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, Server::getStorageUrl() . $url);
        
        curl_setopt($ch, CURLOPT_REFERER, $_SERVER['PHP_SELF']);
        curl_setopt($ch, CURLOPT_USERAGENT, "UKMNorge API");
        
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $output = curl_exec($ch);
        $hd_curl_info = curl_getinfo($ch);
    
        curl_close($ch);
        return $hd_curl_info['content_type'] == 'image/jpeg';
    }

    private function _getKategoriNavn() {
        switch( $this->getArrangement()->getEierType() ) {
            case 'fylke': 
                return 
                    'Fylkesfestivalen i '. $this->getFylke()->getNavn() .
                    ' '. $this->getArrangement()->getSesong();
            case 'land':
                return 'UKM-festivalen '. $this->getArrangement()->getSesong();
            default:
                return 
                    $this->getArrangement()->getKommune()->getNavn() .
                    ' '. $this->getArrangement()->getSesong();
        }
    }

    /**
     * Hent arrangementet filmen er fra
     *
     * @return Arrangement
     */
    public function getArrangement() {
        if( null == $this->arrangement ) {
            $this->arrangement = new Arrangement( $this->arrangement_id );
        }
        return $this->arrangement;
    }

    /**
     * Hent innslaget
     *
     * @return Innslag
     */
    public function getInnslag() {
        return $this->getArrangement()->getInnslag()->get( $this->innslag_id, true);
    }



    /**
     * Oppdater ukmno_wp_related-tabellen
     * 
     * Denne tabellen kobler innslaget mot filmen
     *
     * @param Int $cronId
     * @param Int $blogId
     * @param String $blogUrl
     * @param Innslag $innslag
     * @param Arrangement $arrangement
     * @param String $storage_path
     * @param String $storage_filename
     * @return Bool $query->run()
     */
    public static function updateWpRelated(Int $cronId, Int $blogId, String $blogUrl, Innslag $innslag, Arrangement $arrangement, String $storage_path, String $storage_filename)
    {
        $query = static::getWpRelatedQuery($cronId, $blogId);

        $query->add('blog_id', $blogId);
        $query->add('blog_url', $blogUrl);

        $query->add('post_id', $cronId);
        $query->add('post_type', 'video');

        $query->add(
            'post_meta',
            serialize(
                static::genMeta(
                    $blogId,
                    $storage_path,
                    $storage_filename,
                    $arrangement->getEierType()
                )
            )
        );

        $query->add('b_id', $innslag->getId());
        $query->add('b_kommune', $innslag->getKommune());
        $query->add('b_season', $innslag->getSesong());

        $query->add('pl_type', $arrangement->getEierType());

        error_log(' ==> ' . $query->debug());

        return $query->run();
    }

    /**
     * Skal den registreres som ny rad, eller oppdatere eksisterende?
     *
     * @param Int $cronId
     * @param Int $blogId
     * @return Update|Insert
     */
    public static function getWpRelatedQuery(Int $cronId, Int $blogId)
    {
        $query_existing = new Query(
            "SELECT `rel_id`
            FROM `ukmno_wp_related`
            WHERE `post_type` = 'video'
            AND `post_id` = '#cron_id'
            AND `blog_id` = '#blog_id'",
            [
                'cron_id' => $cronId,
                'blog_id' => $blogId
            ]
        );
        $rel_id = $query_existing->getField();

        if (!$rel_id) {
            error_log('Video finnes ikke i related-table fra tidligere');
            return new Insert('ukmno_wp_related');
        }

        error_log('Video finnes allerede (rel_id: ' . $rel_id . ')');
        return new Update(
            'ukmno_wp_related',
            [
                'rel_id' => $rel_id
            ]
        );
    }

    /**
     * Oppdater ukm_related_video-tabellen
     * 
     * Filmer må konverteres før de knyttes til innslaget
     * (så man ikke viser filmer i konverteringskø utad).
     * 
     * Inntil de er konvertert, er de kun registrert i ukm_related_video.
     * Opplasteren lister ut filmer fra denne tabellen (hvor `file` IS NULL-ish),
     * slik at man kan se konverteringskøen på et vis.
     *
     * @param Int $cronId
     * @param String $storage_path
     * @param String $storage_filename
     * @return Bool $query->run()
     */
    public static function updateUkmRelated(Int $cronId, String $storage_path, String $storage_filename)
    {
        $query = new Update(
            'ukm_related_video',
            [
                'cron_id' => $cronId
            ]
        );
        $query->add('file', static::getFileWithPath($storage_path, $storage_filename));

        error_log(' ==> ' . $query->debug());

        return $query->run();
    }


    /**
     * Genererer et array med metadata for filmen
     *
     * @param Int $blogId
     * @param String $storage_path
     * @param String $storage_filename
     * @param String $title
     * @return Array
     */
    public static function genMeta(Int $blogId, String $storage_path, String $storage_filename, String $title)
    {
        return [
            'file' => static::getFileWithPath($storage_path, $storage_filename),
            'nicename' => $blogId,
            'img' => str_replace('.mp4', '.jpg', static::getFileWithPath($storage_path, $storage_filename)),
            'title' => ucfirst($title)
        ];
    }

    /**
     * Hent ut postMeta
     *
     * @param String $serialized
     * @return Any
     */
    public static function getMeta($key = false)
    {
        if (!$key) {
            return $this->post_meta;
        }
        return isset($this->post_meta[$key]) ? $this->post_meta[$key] : false;
    }
}
