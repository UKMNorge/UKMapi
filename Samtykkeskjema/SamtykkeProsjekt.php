<?php

namespace UKMNorge\Samtykkeskjema;

use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Insert;
use Exception;

require_once('UKM/Autoloader.php');

/**
 * SamtykkeProsjekt representerer et prosjekt knyttet til et Samtykkeskjema.
 */
class SamtykkeProsjekt
{
    const TABLE = 'samtykkeskjema_prosjekt';

    protected $id;
    protected $skjemaId;
    protected $arrangementId;
    protected $navn;
    protected $beskrivelse;

    /**
     * Constructor
     * @param int|array $data - Kan være ID (int) eller en database-rad (array)
     * @throws Exception
     */
    public function __construct($data)
    {
        if (is_numeric($data)) {
            $this->_loadById($data);
        } elseif (is_array($data)) {
            $this->_loadByRow($data);
        } else {
            throw new Exception('Kan kun opprette SamtykkeProsjekt med numerisk ID eller rad fra database.');
        }
    }

    /**
     * Last inn fra ID
     * @param int $id
     * @throws Exception
     */
    protected function _loadById($id)
    {
        $sql = new Query("
            SELECT *
            FROM `" . self::TABLE . "`
            WHERE `id` = '#id'",
            [
                'id' => $id
            ]
        );
        $row = $sql->run('array');
        if (!$row) {
            throw new Exception("Fant ikke SamtykkeProsjekt med ID $id");
        }
        $this->_loadByRow($row);
    }

    /**
     * Last inn fra rad-array
     * @param array $row
     */
    protected function _loadByRow($row)
    {
        $this->id            = $row['id'];
        $this->skjemaId      = $row['skjema_id'];
        $this->arrangementId = isset($row['arrangement_id']) ? (int) $row['arrangement_id'] : null;
        $this->navn          = $row['navn'];
        $this->beskrivelse   = $row['beskrivelse'] ?? null;
    }

    /**
     * Lagre prosjektet. Oppretter ny rad hvis id mangler, ellers oppdaterer.
     */
    public function save()
    {
        if ($this->id) {
            $sql = new Query("
                UPDATE `" . self::TABLE . "`
                SET
                    `skjema_id` = '#skjema_id',
                    `arrangement_id` = '#arrangement_id',
                    `navn` = '#navn',
                    `beskrivelse` = '#beskrivelse'
                WHERE `id` = '#id'",
                [
                    'skjema_id'      => $this->skjemaId,
                    'arrangement_id' => $this->arrangementId,
                    'navn'           => $this->navn,
                    'beskrivelse'    => $this->beskrivelse,
                    'id'             => $this->id,
                ]
            );
            $sql->run();
        } else {
            $sql = new Insert(self::TABLE);
            $sql->add('skjema_id', $this->skjemaId);
            $sql->add('arrangement_id', $this->arrangementId);
            $sql->add('navn', $this->navn);
            $sql->add('beskrivelse', $this->beskrivelse);

            $this->id = $sql->run();
        }
    }

    /**
     * Opprett og lagre et nytt SamtykkeProsjekt
     * @param int $skjema_id
     * @param string $navn
     * @param string|null $beskrivelse
     * @param int|null $arrangement_id
     * @return static
     */
    public static function create($skjema_id, $navn, $beskrivelse = null, $arrangement_id = null)
    {
        $obj = new self([
            'id'             => null,
            'skjema_id'      => $skjema_id,
            'arrangement_id' => $arrangement_id,
            'navn'           => $navn,
            'beskrivelse'    => $beskrivelse,
        ]);
        $obj->save();
        return $obj;
    }

    /**
     * Hent ID
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Hent Samtykkeskjema-ID
     * @return int
     */
    public function getSkjemaId()
    {
        return $this->skjemaId;
    }

    /**
     * Hent arrangement-ID (referanse til smartukm_place.pl_id), eller null
     * @return int|null
     */
    public function getArrangementId(): ?int
    {
        return $this->arrangementId;
    }

    /**
     * Sett arrangement-ID
     * @param int|null $arrangementId
     */
    public function setArrangementId(?int $arrangementId)
    {
        $this->arrangementId = $arrangementId;
    }

    /**
     * Hent navn på prosjektet
     * @return string
     */
    public function getNavn(): string
    {
        return $this->navn;
    }

    /**
     * Sett navn på prosjektet
     * @param string $navn
     */
    public function setNavn(string $navn)
    {
        $this->navn = $navn;
    }

    /**
     * Hent beskrivelse av prosjektet
     * @return string|null
     */
    public function getBeskrivelse(): ?string
    {
        return $this->beskrivelse;
    }

    /**
     * Sett beskrivelse av prosjektet
     * @param string|null $beskrivelse
     */
    public function setBeskrivelse(?string $beskrivelse)
    {
        $this->beskrivelse = $beskrivelse;
    }
}
