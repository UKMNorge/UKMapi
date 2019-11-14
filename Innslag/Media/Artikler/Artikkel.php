<?php

namespace UKMNorge\Innslag\Media\Artikler;

use UKMNorge\Innslag\Innslag;
use UKMNorge\Database\SQL\Query;
use Exception;

class Artikkel
{
    var $id = null;
    var $pl_type = null;
    var $sesong    = null;
    var $blog_id = null;
    var $blog_url = null;
    var $innslag_id = null;
    var $innslag = null;
    var $tittel = null;
    var $link = null;

    /**
     * Opprett artikkel-objekt
     *
     * @param Array $row
     */
    public function __construct(array $row)
    {
        $this->setId((int) $row['post_id']);
        $this->setBlogId((int) $row['blog_id']);
        $this->setBlogUrl($row['blog_url']);
        $this->setInnslagId((int) $row['b_id']);
        $this->setSesong((int) $row['b_season']);

        $post_meta = unserialize($row['post_meta']);
        $this->setTittel(base64_decode($post_meta['title']));
        $this->setLink($post_meta['link']);
    }

    /**
     * Hent gitt artikkel fra ID
     *
     * @param Int $id
     * @return Artikkel
     */
    public static function getById(Int $id)
    {
        $query = new Query(
            static::getLoadQuery() . "
            WHERE `id` = '#id'",
            [
                'id' => $id
            ]
        );
        $res = $query->getArray();
        if (!$res) {
            throw new Exception(
                'Fant ikke artikkel ' . $id,
                311003
            );
        }
        return new Artikkel($res);
    }

    /**
     * Hent starten på SQL-spørringen
     *
     * @return String
     */
    public static function getLoadQuery()
    {
        return "SELECT * FROM `ukmno_wp_related`";
    }

    /**
     * Sett post-ID
     *
     * @param Int $id 
     *
     * @return $this
     **/
    public function setId(Int $id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Hent post-ID
     *
     * @return Int $id
     **/
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sett Blogg-id (wordpress)
     *
     * @param integer $blog_id
     *
     * @return $this
     **/
    public function setBlogId(Int $blog_id)
    {
        $this->blog_id = $blog_id;
        return $this;
    }

    /**
     * Hent Blogg-id (wordpress)
     *
     * @return Int $blog_id
     **/
    public function getBlogId()
    {
        return $this->blog_id;
    }

    /**
     * Sett Blogg-url (wordpress)
     *
     * @param String $blog_url
     *
     * @return self
     **/
    public function setBlogUrl(String $blog_url)
    {
        $this->blog_url = $blog_url;
        return $this;
    }

    /**
     * Hent Blogg-url (wordpress)
     *
     * @return String $blog_url
     **/
    public function getBlogUrl()
    {
        return $this->blog_url;
    }

    /**
     * Sett tittel
     *
     * @param String $tittel
     * @return self
     **/
    public function setTittel(String $tittel)
    {
        $this->tittel = $tittel;
        return $this;
    }

    /**
     * Hent tittel
     *
     * @return String $tittel
     *
     **/
    public function getTittel()
    {
        return $this->tittel;
    }

    /**
     * Sett link
     *
     * @param String $link
     * @return self
     **/
    public function setLink(String  $link)
    {
        $this->link = $link;
        return $this;
    }
    /**
     * Hent link
     *
     * @return String $link
     **/
    public function getLink()
    {
        return $this->link;
    }

    /** 
     * Sett InnslagId
     *
     * @param Int innslag_id
     * @return $this;
     **/
    public function setInnslagId(Int $innslag_id)
    {
        $this->innslag_id = $innslag_id;
        return $this;
    }

    /**
     * Hent InnslagId
     *
     * @return Int $innslag_id
     **/
    public function getInnslagId()
    {
        return $this->innslag_id;
    }

    /**
     * Hent Innslag
     *
     * @return Innslag
     * @throws Exception
     **/
    public function getInnslag()
    {
        // Innslaget er allerede lastet
        if (null !== $this->innslag) {
            return $this->innslag;
        }

        // Innslag er ikke spesifisert (burde ikke gå an)
        if (null == $this->getInnslagId()) {
            throw new Exception(
                'Beklager, klarte ikke å finne innslaget for artikkel ' . $this->getId(),
                311002
            );
        }

        $innslag = new Innslag($this->getInnslagId());

        // Innslaget finnes ikke
        if (null == $innslag->getId()) {
            throw new Exception(
                'Beklager, klarte ikke å finne innslaget som tilhører artikkel ' . $this->getId(),
                311001
            );
        }

        return $innslag;
    }

    /**
     * Set sesong
     *
     * @param Int sesong
     *
     * @return self
     **/
    public function setSesong(Int $sesong)
    {
        $this->sesong = $sesong;
        return $this;
    }

    /**
     * Hent sesong
     *
     * @return Int sesong
     **/
    public function getSesong()
    {
        return $this->sesong;
    }
}
