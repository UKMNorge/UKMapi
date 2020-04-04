<?php

namespace UKMNorge\Filmer\UKMTV\Direkte;

use DateTime;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Geografi\Fylker;
use UKMNorge\Geografi\Kommune;

class Sending
{
    var $id;
    var $hendelse_id;
    var $start_offset;
    var $varighet;

    var $navn;
    var $sted;
    var $start;
    var $stop;

    var $link;
    var $embed;

    var $arrangement;
    var $arrangement_id;
    var $arrangement_navn;
    var $arrangement_lenke;

    var $eier_type;
    var $eier_kommune;
    var $eier_kommune_id;
    var $eier_fylke;
    var $eier_fylke_id;

    public function __construct(array $data)
    {
        $this->id = intval($data['id']);
        $this->hendelse_id = intval($data['hendelse_id']);
        $this->start_offset = intval($data['start_offset']);
        $this->varighet = intval($data['varighet']);

        $this->navn = $data['navn'];
        $this->sted = $data['sted'];
        $this->start = new DateTime($data['start']);
        $this->stopp = new DateTime($data['stop']);

        $this->arrangement_id = intval($data['arrangement_id']);
        $this->arrangement_navn = $data['arrangement_navn'];
        $this->arrangement_lenke = $data['arrangement_lenke'];

        $this->eier_type = $data['eier_type'];
        $this->eier_kommune_id = $data['eier_kommune_id'];
        $this->eier_fylke_id = $data['eier_fylke_id'];

        $this->link = json_decode($data['link']);
        $this->embed = json_decode($data['embed']);
    }

    /**
     * Hent sendinges id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sett sendinges id
     *
     * @param Int Id
     * @return self
     */
    public function setId(Int $id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Hent hendelse-id
     * 
     * @return Int
     */
    public function getHendelseId()
    {
        return $this->hendelse_id;
    }

    /**
     * Sett hendelse-id
     *
     * @param Int hendelseId
     * @return self
     */
    public function setHendelseId(Int $hendelse_id)
    {
        $this->hendelse_id = $hendelse_id;

        return $this;
    }

    /**
     * Hent start-offset (minutter)
     * 
     * @return Int
     */
    public function getStartOffset()
    {
        return $this->start_offset;
    }

    /**
     * Sett start-offset (minutter)
     *
     * @param Int start offset
     * @return self
     */
    public function setStartOffset(Int $start_offset)
    {
        $this->start_offset = $start_offset;

        return $this;
    }

    /**
     * Hent sendinges varighet (minutter)
     * 
     * @return Int
     */
    public function getVarighet()
    {
        return $this->varighet;
    }

    /**
     * Sett sendinges varighet (minutter)
     *
     * @param Int varighet
     * @return self
     */
    public function setVarighet(Int $varighet)
    {
        $this->varighet = $varighet;

        return $this;
    }

    /**
     * Sendingens navn (samme som hendelse)
     * 
     * @return String
     */
    public function getNavn()
    {
        return $this->navn;
    }

    /**
     * Sendingens sted (samme som hendelse)
     * 
     * @return String
     */
    public function getSted()
    {
        return $this->sted;
    }

    /**
     * Sendingens starttid (korrigert for start_offset!)
     * 
     * @return DateTime
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Sendingens sluttid (varighet + start korrigert for start_offset)
     * 
     * @return DateTime
     */
    public function getStop()
    {
        return $this->stop;
    }

    /**
     * Hent arrangementet
     * 
     * @return Arrangement
     */
    public function getArrangement()
    {
        if (is_null($this->arrangement)) {
            $this->arrangement = new Arrangement($this->getArrangementId());
        }
        return $this->arrangement;
    }

    /**
     * Hent arrangementets id
     * 
     * @return Int
     */
    public function getArrangementId()
    {
        return $this->arrangement_id;
    }

    /**
     * Hent arrangementets navn 
     * 
     * @return String
     */
    public function getArrangementNavn()
    {
        return $this->arrangement_navn;
    }

    /**
     * Hent arrangementets lenke
     * 
     * @return String full url
     */
    public function getArrangementLenke()
    {
        return 'https://' . UKM_HOSTNAME . '/' . $this->arrangement_lenke . '/';
    }

    /**
     * Hent eiertype
     * 
     * @return String
     */
    public function getEierType()
    {
        return $this->eier_type;
    }

    /**
     * Hent eventuell eierkommune
     * 
     * @return String
     */
    public function getEierKommune()
    {
        if (is_null($this->eier_kommune)) {
            $this->eier_kommune = new Kommune($this->getEierKommuneId());
        }
        return $this->eier_kommune;
    }

    /**
     * Hent eventuell eierkommunes id
     * 
     * @return Int
     */
    public function getEierKommuneId()
    {
        return $this->eier_kommune_id;
    }

    /**
     * Hent eierfylke
     * 
     * @return Fylke
     */
    public function getEierFylke()
    {
        if (is_null($this->eier_fylke)) {
            $this->eier_fylke = Fylker::getById($this->getEierFylkeId());
        }
        return $this->eier_fylke;
    }

    /**
     * Hent eierfylke-id
     * 
     * @return Int
     */
    public function getEierFylkeId()
    {
        return $this->eier_fylke_id;
    }

    /**
     * Hent lenke til livestream
     * 
     * @return String
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Hent embedkode
     * 
     * @return String
     */
    public function getEmbed()
    {
        return $this->embed;
    }
}
