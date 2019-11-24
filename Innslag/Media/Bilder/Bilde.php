<?php

namespace UKMNorge\Innslag\Media\Bilder;

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Innslag\Innslag;
use UKMNorge\Wordpress\User;
use Exception;

class Bilde
{
    var $id = null;
    var $rel_id = null;
    var $blog_id = null;
    var $blog_url = null;
    var $album_id = null;
    var $album = null;
    var $kommune_id = null;
    var $kommune = null;
    var $season = null;
    var $pl_id = null;
    var $monstring = null;
    var $pl_type = null;
    var $innslag_id = null;
    var $innslag = null;

    var $author_id = null;
    var $author = null;

    var $sizes = null;

    private $post_meta = null;    # PostMeta skal ikke aksesseres eksternt, men pakkes med getters and setters

    static $table = 'ukmno_wp_related';


    /**
     * Hent gitt bilde fra ukm_bilder::id
     *
     * @param Int ukm_bilder::id
     * @return Bilde
     */
    public static function getById(Int $id)
    {
        $SQL = new Query(
            static::getLoadQuery() . "
            WHERE `ukm_bilder`.`id` = '#id'
            ",
            [
                'id' => $id
            ]
        );
        $row = $SQL->getArray();
        return new Bilde($row);
    }

    /**
     * Hent starten på SQL-spørringen
     *
     * @return String
     */
    public static function getLoadQuery()
    {
        return "SELECT * 
            FROM `ukm_bilder`
            JOIN `ukmno_wp_related` 
                ON (`ukmno_wp_related`.`post_id` = `ukm_bilder`.`wp_post` 
                    AND `ukmno_wp_related`.`b_id` = `ukm_bilder`.`b_id`
                )
            ";
    }

    /**
     * Hent et innslagsbilde
     * 
     * @param $bilde as integer (ukm_bilder::id) or associative database row joined from ukm_bilder and wp_related)
     *
     **/
    public function __construct(array $bilde)
    {
        $this->setId($bilde['id']);
        $this->setRelId($bilde['rel_id']);
        $this->setBlogId($bilde['blog_id']);
        $this->setBlogUrl($bilde['blog_url']);
        $this->setPostId($bilde['post_id']);

        $this->setAlbumId($bilde['c_id']);
        $this->setKommuneId($bilde['b_kommune']);
        $this->setSesong($bilde['b_season']);
        $this->setPlId($bilde['pl_id']);
        $this->setMonstringType($bilde['pl_type']);
        $this->setInnslagId($bilde['b_id']);

        $this->post_meta    = unserialize($bilde['post_meta']);

        if (isset($this->post_meta['author'])) {
            $this->setAuthorId($this->post_meta['author']);
        }

        foreach (array('thumbnail', 'medium', 'large', 'lite') as $size) {
            if (isset($this->post_meta['sizes'][$size])) {
                $this->addSize($size, $this->post_meta['sizes'][$size]);
            }
        }
        $this->addSize('original', $this->post_meta['file']);
    }

    /**
     * Sett bilde-ID
     *
     * @param Int $id 
     *
     * @return self
     **/
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Hent bilde-ID
     *
     * @return Int $id
     **/
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sett relasjonsID (fra ukmno_wp_related)
     *
     * Tabellen joines alltid inn, både fra getBilder() og getBildeById
     * og både ukmno_wp_related.rel_id og ukm_bilder.id vil alltid være tilgjengelig.
     * Begge disse unike nøklene representerer kun ett bilde,
     * og vi har derfor to måter å finne samme bilde på.
     *
     * Videresendingsssystemet (og getValgtBilde()) bruker rel_id,
     * og kunne like gjerne brukt id. Men, ettersom de er det samme, bruker
     * vi rel_id, som det opprinnelig var kodet.
     *
     * @param Int $rel_id
     * @return self
     **/
    public function setRelId($rel_id)
    {
        $this->rel_id = $rel_id;
        return $this;
    }

    /**
     * Hent relasjonsID (fra ukmno_wp_related)
     *
     * @return int $rel_id
     **/
    public function getRelId()
    {
        return $this->rel_id;
    }

    /**
     * Sett Blogg-id (wordpress)
     *
     * @param Int $blog_id
     *
     * @return self
     **/
    public function setBlogId($blog_id)
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
    public function setBlogUrl($blog_url)
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
     * Sett Post-ID (wordpress)
     *
     * @param Int $post_id
     * @return self
     */
    public function setPostId($post_id)
    {
        $this->post_id = $post_id;
        return $this;
    }

    /**
     * Hent Post-ID (wordpress)
     *
     * @return Int $post_id
     */
    public function getPostId()
    {
        return $this->post_id;
    }

    /**
     * Sett album-id
     * Hvis bildet er lastet opp som en del av en forestilling, hent forestilling-ID
     *
     * @param Int album_id
     *
     * @return self;
     **/
    public function setAlbumId($album_id)
    {
        $this->album_id = $album_id;
        return $this;
    }

    /**
     * Hent album-id
     *
     * @return Int album_id
     **/
    public function getAlbumId()
    {
        return $this->album_id;
    }

    /**
     * Sett kommune
     *
     * @param Int kommune_id
     *
     * @return self
     **/
    public function setKommuneId($kommune_id)
    {
        $this->kommune_id = $kommune_id;
        return $this;
    }

    /**
     * Hent kommuneID
     *
     * @return Int kommune_id
     **/
    public function getKommuneId()
    {
        return $this->kommune_id;
    }

    /**
     * Hent kommune-objektet
     *
     * @return object kommune
     **/
    public function getKommune()
    {
        // Hvis kommune-objektet allerede er lastet inn
        if (null == $this->kommune) {
            if (null == $this->getKommuneId()) {
                throw new Exception(
                    'Beklager, ukjent kommune',
                    321003
                );
            }
            $this->kommune = new Kommune($this->getKommuneId());
        }
        return $this->kommune;
    }

    /**
     * Set sesong
     *
     * @param Int sesong
     *
     * @return this
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

    /**
     * Sett MønstringsID (PlId)
     *
     * @param Int pl_id
     *
     * @return self
     **/
    public function setPlId(Int $pl_id)
    {
        $this->pl_id = $pl_id;
        return $this;
    }

    /**
     * Hent MønstringsID (PlId)
     *
     * @return Int pl_id
     **/
    public function getPlId()
    {
        return $this->pl_id;
    }

    /**
     * Hent Arrangement
     *
     * @return Arrangement
     **/
    public function getMonstring()
    {
        // Mønstring er allerede lastet inn
        if (null == $this->monstring) {
            // Mønstring er ikke satt
            if (null == $this->getPlId()) {
                throw new Exception(
                    'Beklager, ukjent arrangement-ID',
                    321004
                );
            }
            $this->monstring = new Arrangement($this->getPlId());
        }
        return $this->monstring;
    }

    /**
     * Sett fotograf (WP Author)
     *
     * @param Int author_id
     * @return self;
     **/
    public function setAuthorId(Int $author_id)
    {
        $this->author_id = $author_id;
        return $this;
    }

    /**
     * Hent fotograf-ID
     *
     * @return Int author_id
     **/
    public function getAuthorId()
    {
        return $this->author_id;
    }

    /**
     * Hent fotograf
     *
     * @return User
     * @throws Exception ukjent Author
     **/
    public function getAuthor()
    {
        // Author-objektet er allerede lastet inn
        if (null == $this->author) {
            // Det er ikke satt author-id
            if (null == $this->getAuthorId()) {
                throw new Exception(
                    'Beklager, ukjent fotograf',
                    321005
                );
            }
            $this->author = User::loadById($this->getAuthorId());
        }

        return $this->author;
    }

    /** 
     * Sett InnslagId
     *
     * @param Int innslag_id
     *
     * @return self;
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
        if (null == $this->innslag) {
            // Innslag er ikke spesifisert (burde ikke gå an)
            if (null == $this->getInnslagId()) {
                throw new Exception(
                    'Beklager, fant ikke igjen innslaget for bilde ' . $this->getId(),
                    321001
                );
            }

            $this->innslag = new Innslag($this->getInnslagId());
        }
        return $this->innslag;
    }

    /**
     * Legg til bildestørrelse (URL til bildet i forskjellige størrelser)
     *
     * @param String $id (brukt for å hente ut bildet)
     * @param Array $data (bildedata: filnavn, bredde, høyde, mime-type)
     *
     * @return self
     **/
    public function addSize(String $id, array $data)
    {
        // Originalstørrelsen inneholder ikke størrelsesdata
        if ('original' == $id) {
            $file = $data;
            $data = array();
            $data['file'] = $file;
            $data['width'] = 0;
            $data['height'] = 0;
            $data['mime-type'] = false;
        }

        // Beregn paths	
        if (UKM_HOSTNAME == 'ukm.no') {
            $basefolder = 'wp-content/blogs.dir/' . $this->getBlogId() . '/files/';
        } else {
            $basefolder = 'wp-content/uploads/sites/' . $this->getBlogId() . '/';
        }
        $data['path_int'] = $basefolder;
        $data['path_ext'] = 'http://' . UKM_HOSTNAME . '/' . $basefolder;

        // Opprett bilde
        $this->sizes[$id] = new Storrelse($data);

        return $this;
    }

    /**
     * Hent en størrelse (eller original hvis størrelsen ikke finnes
     *
     * @param String $id størrelse
     *
     * @return Storrelse
     *
     **/
    public function getSize($id, $id2 = false)
    {
        if (isset($this->sizes[$id])) {
            return $this->sizes[$id];
        }

        if ($id2 != false && isset($this->sizes[$id2])) {
            return $this->sizes[$id2];
        }

        if (isset($this->sizes['original'])) {
            return $this->sizes['original'];
        }

        return false;
    }
}