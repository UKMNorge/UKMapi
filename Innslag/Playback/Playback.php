<?php

namespace UKMNorge\Innslag\Playback;

class Playback
{
    const TABLE = 'ukm_playback';
    var $base_url = 'https://playback.' . UKM_HOSTNAME . '/';

    var $id = null;
    var $arrangement_id = null;
    var $innslag_id = null;
    var $navn = null;
    var $beskrivelse = null;
    var $fil = null;
    var $sesong = null;

    var $file_extension = null;
    var $file_path = null;
    var $file_download = null;
    var $file_name = null;


    public static function getLoadQuery()
    {
        return "SELECT *
            FROM `". static::TABLE ."`";
    }

    public function __construct($data)
    {
        $this->id = (int) $data['pb_id'];
        $this->arrangement_id = (int) $data['pl_id'];
        $this->innslag_id = (int) $data['b_id'];
        $this->navn = $data['pb_name'];
        $this->beskrivelse = $data['pb_description'];
        $this->fil = $data['pb_file'];
        $this->sesong = $data['pb_season'];

        $this->file_path = 'upload/data/' . $this->sesong . '/' . $this->arrangement_id . '/';
        $this->url = $this->base_url . $this->arrangement_id . '/' . $this->id . '/';
    }

    /**
     * Sett playbackfilens ID
     * 
     * @return Int $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Hent playbackfilens ID
     *
     * @param Int $id
     * @return  self
     */
    public function setId(Int $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Hent hvilket arrangement denne ble lastet opp fra
     * 
     * @return Int $arrangement_id
     */
    public function getArrangementId()
    {
        return $this->arrangement_id;
    }

    /**
     * Sett hvilket arrangement denne ble lastet opp fra
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
     * Hent innslagID
     * 
     * @return Int $innslagId
     */
    public function getInnslagId()
    {
        return $this->innslag_id;
    }

    /**
     * Sett hvilket innslag denne tilhører
     *
     * @param Int $innslag_id
     * @return  self
     */
    public function setInnslagId(Int $innslag_id)
    {
        $this->innslag_id = $innslag_id;

        return $this;
    }

    /**
     * Hent navn på filen
     * 
     * @return String $navn
     */
    public function getNavn()
    {
        return $this->navn;
    }

    /**
     * Sett navn på filen
     *
     * @param String $navn
     * @return  self
     */
    public function setNavn(String $navn)
    {
        $this->navn = $navn;

        return $this;
    }

    /**
     * Hent filens beskrivelse
     * 
     * @return String $beskrivelse
     */
    public function getBeskrivelse()
    {
        return $this->beskrivelse;
    }

    /**
     * Sett filens beskrivelse
     * 
     * @param String $beskrivelse
     * @return  self
     */
    public function setBeskrivelse(String $beskrivelse)
    {
        $this->beskrivelse = $beskrivelse;
        return $this;
    }

    /**
     * Hent filnavnet som det er på serveren
     */
    public function getFil()
    {
        return $this->fil;
    }

    /**
     * Sett filnavn på serveren (faktisk filnavn)
     *
     * @param String $filnavn
     * @return  self
     */
    public function setFil(String $fil)
    {
        $this->fil = $fil;

        return $this;
    }

    /**
     * Hent filens sesong
     * 
     * @return Int $sesong
     */
    public function getSesong()
    {
        return $this->sesong;
    }

    /**
     * Sett hvilken sesong filen er lastet opp for
     *
     * @param Int $sesong
     * @return  self
     */
    public function setSesong(Int $sesong)
    {
        $this->sesong = $sesong;
        return $this;
    }

    /**
     * Hent filens filending
     */
    public function getExtension()
    {
        if (null == $this->file_extension) {
            $this->file_extension = substr(
                $this->getFil(),
                strrpos(
                    $this->getFil(),
                    '.'
                )
            );
        }
        return $this->file_extension;
    }

    /**
     * Hent filbane for filen på playback-server
     */
    public function getPath()
    {
        return $this->file_path;
    }

    /**
     * Hent URL for nedlasting
     */
    public function getUrl()
    {
        return $this->url;// . $this->fil;
    }

    /**
     * Hent faktisk filnavn som skal brukes
     */
    public function getFilnavn()
    {
        return static::sanitize( $this->getNavn() ) . '-'. $this->getFil();
    }

    /**
     * Gjør et playback-navn trygt som filnavn
     *
     * @param String $sanitize
     * @return String $sanitized
     */
    public static function sanitize(String $sanitize)
    {
        return preg_replace(
            '/[^a-zA-Z0-9]/',
            '',
            str_replace(
                [' - ', ' '],
                ['-', '_'],
                $sanitize
            )
        );
    }
}
