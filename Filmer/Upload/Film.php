<?php

namespace UKMNorge\Filmer\Upload;

use UKMNorge\Database\SQL\Query;
use UKMNorge\Filmer\UKMTV\FilmInterface;
use UKMNorge\Filmer\UKMTV\Server\Server;
use UKMNorge\Filmer\UKMTV\Tags\Tags;

class Film implements FilmInterface
{
    private $tv_id;
    private $cron_id;
    private $arrangement_id;
    private $innslag_id;
    private $title;
    private $description;
    private $file_path;
    private $image_path;
    private $tags;
    private $season;
    private $converted = false;
    private $type;

    public function __construct(Int $cronId)
    {
        $this->cron_id = $cronId;

        $query = new Query(
            "SELECT *
            FROM `ukm_uploaded_video`
            WHERE `cron_id` = '#cronid'",
            [
                'cronid' => $cronId
            ]
        );
        $data = $query->getArray();
        $this->cron_id = intval($data['cron_id']);
        $this->converted = $data['converted'] == 'true';
        $this->tv_id = intval($data['tv_id']);
        $this->title = $data['title'];
        $this->description = $data['description'];
        $this->file_path = $data['file'];
        $this->arrangement_id = intval($data['arrangement_id']);
        $this->innslag_id = intval($data['innslag_id']);
        $this->title_id = intval($data['title_id']);
        $this->season = intval($data['season']);
        $this->type = $this->getInnslagId() > 0 ? 'innslag' : 'reportasje';
    }

    /**
     * Sett TV-ID
     *
     * @param Int $tv_id
     * @return self
     */
    public function setTvId(Int $tv_id)
    {
        $this->tv_id = $tv_id;
        return $this;
    }

    /**
     * Hent TV-ID
     *
     * @return Int
     */
    public function getTvId()
    {
        return $this->tv_id;
    }
    
    /**
     * Hent filmens ID (som er cronId)
     *
     * @return Int
     */
    public function getId() {
        return $this->getCronId();
    }

    /**
     * Get the value of cron_id
     * 
     * @return Int|null
     */
    public function getCronId()
    {
        return $this->cron_id;
    }

    /**
     * Set the value of cron_id
     *
     * @param Int $cron_id
     * @return self
     */
    public function setCronId(Int $cron_id)
    {
        $this->cron_id = $cron_id;

        return $this;
    }

    /**
     * Get the value of arrangement_id
     * 
     * @return Int|null
     */
    public function getArrangementId()
    {
        return $this->arrangement_id;
    }

    /**
     * Set the value of arrangement_id
     * 
     * @param Int $arrangement_id
     * @return self
     */
    public function setArrangementId(Int $arrangement_id)
    {
        $this->arrangement_id = $arrangement_id;

        return $this;
    }

    /**
     * Get the value of innslag_id
     * 
     * @return Int|null
     */
    public function getInnslagId()
    {
        return $this->innslag_id;
    }

    /**
     * Set the value of innslag_id
     *
     * @param Int $innslag_id
     * @return self
     */
    public function setInnslagId(Int $innslag_id)
    {
        $this->innslag_id = $innslag_id;

        return $this;
    }

    /**
     * Get the value of title
     * 
     * @return String
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the value of title
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
     * Get the value of description
     * 
     * @return String
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the value of description
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
     * Get the value of file_path
     * 
     * @return String $file_path
     */
    public function getFilePath()
    {
        return $this->file_path;
    }

    /**
     * Set the value of file_path
     *
     * @string
     * @return self
     */
    public function setFilePath($file_path)
    {
        $this->file_path = $file_path;

        return $this;
    }

    /**
     * Get the value of image_path
     * 
     * @return String
     */
    public function getImagePath()
    {
        if( null == $this->image_path ) {
            $this->image_path = $this->_finnBildeFraFil();
        }
        
        return $this->image_path;
    }

    /**
     * Set the value of image_path
     *
     * @param String $image_path
     * @return self
     */
    public function setImagePath(String $image_path)
    {
        $this->image_path = $image_path;

        return $this;
    }

    /**
     * Get the value of tags
     * 
     * @return Tags
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set the value of tags
     *
     * @param Tags $tagCollection
     * @return self
     */
    public function setTags(Tags $tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Get the value of season
     * 
     * @return Int season
     */
    public function getSeason()
    {
        return $this->season;
    }

    /**
     * Set the value of season
     *
     * @param Int $season
     * @return self
     */
    public function setSeason(Int $season)
    {
        $this->season = $season;

        return $this;
    }

    /**
     * Prøv å gjette oss frem til bilde-banen hvis lagret info er blank
     *
     * @return String url
     */
    private function _finnBildeFraFil()
    {
        $video = $this->getFilePath();
        $ext = strrpos($video, '.');
        $img = substr($video, 0, $ext) . '.jpg';
        if ($this->_img_exists($img)) {
            return $img;
        }
        return $video . '.jpg';
    }

    /**
     * Curl videoserver for å høre om bildet finnes
     *
     * @param String $url
     * @return Bool
     */
    private function _img_exists(String $url)
    {
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

    /**
     * Er filmen konvertert?
     *
     * @return Bool
     */
    public function erKonvertert() {
        return $this->getConverted();
    }

    /**
     * Get the value of converted
     */ 
    public function getConverted()
    {
        return $this->converted;
    }

    /**
     * Hvilken type film er dette?
     * 
     * @return String innslag|reportasje
     */ 
    public function getType()
    {
        return $this->type;
    }
}
