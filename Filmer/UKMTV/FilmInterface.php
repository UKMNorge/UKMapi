<?php

namespace UKMNorge\Filmer\UKMTV;

use UKMNorge\Filmer\UKMTV\Tags\Tags;

interface FilmInterface
{
    /**
     * Sett TV-ID
     *
     * @param Int $tv_id
     * @return self
     */
    public function setTvId(Int $tv_id);

    /**
     * Hent filmens TVid (hvis vi vet at den finnes i UKM-TV)
     *
     * @return Int|null
     */
    public function getTvId();

    /**
     * Hent hvilken cronId converteren ga filmen
     * 
     * Brukes kun av filmer som har vært gjennom videoconverter.ukm.no
     *
     * @return Int|null
     */
    public function getCronId();

    /**
     * Hent hvilket arrangement som lastet opp filmen
     * 
     * @return Int|null
     */
    public function getArrangementId();

    /**
     * Hent filmens tittel
     *
     * @return String
     */
    public function getTitle();

    /**
     * Hent filmens beskrivelse
     *
     * @return String
     */
    public function getDescription();

    /**
     * Hent filmens path (inkl filnavn) på videostorage (path, ikke URL)
     *
     * @return String
     */
    public function getFilePath();

    /**
     * Hent preview-bildets path på videostorage (path, ikke URL)
     *
     * @return String
     */
    public function getImagePath();

    /**
     * Hent filmens tags
     *
     * @return Tags
     */
    public function getTags();

    /**
     * Hvilken sesong ble filmen lastet opp
     * 
     * UKM-sesongen følger skoleåret.
     *
     * @return Int
     */
    public function getSeason();

    /**
     * Hvilket innslag er dette? 0 = reportasje
     *
     * @return Int|null
     */
    public function getInnslagId();
}
