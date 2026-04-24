<?php

namespace UKMNorge\Filer;

use Exception;
use UKMNorge\Database\SQL\Query;


class PlaybackFile
{
    const TABLE = 'ukm_playback_file';

    private $id = null; // pbf_id
    private $arrangement_id = null; // pl_id
    private $navn = null; // pbf_name
    private $beskrivelse = null; // pbf_description
    private $fil = null; // pbf_file

    private $sprivate_id = null;
    private $samtykkeskjema_version_id = null;
    private $samtykkeskjema_id = null;

    private $file_extension = null;
    private $file_path = null;
    private $file_download = null;
    private $file_name = null;

    public static function getLoadQuery()
    {
        return "SELECT *
            FROM `". static::TABLE ."`";
    }

    public function __construct($data)
    {
        $this->id = (int) $data['pbf_id'];
        $this->arrangement_id = isset($data['pl_id']) ? (int) $data['pl_id'] : null;
        $this->navn = $data['pbf_name'] ?? null;
        $this->beskrivelse = $data['pbf_description'] ?? null;
        $this->fil = $data['pbf_file'] ?? null;

        $this->svar_id = isset($data['svar_id']) ? (int) $data['svar_id'] : null;
        $this->samtykkeskjema_version_id = isset($data['samtykkeskjema_version_id']) ? (int) $data['samtykkeskjema_version_id'] : null;
        $this->samtykkeskjema_id = isset($data['samtykkeskjema_id']) ? (int) $data['samtykkeskjema_id'] : null;
    }

    public static function getById($playbackFileId) : PlaybackFile {
        $sql = new Query(
            static::getLoadQuery() . "
                        WHERE `pbf_id` = '#playbackFileId'",
            [
                'playbackFileId' => $playbackFileId
            ]
        );
        
        $data = $sql->getArray();

        if($data) {
            return new static($data);
        }
        throw new Exception('Could not find playback file with id: '. $playbackFileId);
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
     * Hent faktisk filnavn som skal brukes
     */
    public function getFilnavn()
    {
        return static::sanitize( $this->getNavn() ) . '-'. $this->getFil();
    }

    /**
     * Er denne filen et bilde
     * 
     * @return bool 
     */
    public function erBilde() : bool {
        $extension = pathinfo($this->getFil(), PATHINFO_EXTENSION);
        return ($extension == 'jpg' || $extension == 'jpeg' || $extension == 'png') ? true : false;
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

    public function getSvarId() : ?int {
        return $this->svar_id;
    }

    public function setSvarId(?int $svar_id) : self {
        $this->svar_id = $svar_id;
        return $this;
    }

    public function getSamtykkeskjemaVersionId() : ?int {
        return $this->samtykkeskjema_version_id;
    }

    public function setSamtykkeskjemaVersionId(?int $samtykkeskjema_version_id) : self {
        $this->samtykkeskjema_version_id = $samtykkeskjema_version_id;
        return $this;
    }

    public function getSamtykkeskjemaId() : ?int {
        return $this->samtykkeskjema_id;
    }

    public function setSamtykkeskjemaId(?int $samtykkeskjema_id) : self {
        $this->samtykkeskjema_id = $samtykkeskjema_id;
        return $this;
    }
}
