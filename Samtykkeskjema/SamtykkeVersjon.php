<?php

namespace UKMNorge\Samtykkeskjema;

use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Insert;

/**
 * SamtykkeVersjon representerer en versjon av et Samtykkeskjema.
 */
class SamtykkeVersjon
{
    const TABLE = 'samtykkeskjema_version';

    protected $id;
    protected $skjemaId;
    protected $versjonNr;
    protected $beskrivelse;
    protected $bodyText;
    protected $filePath;
    protected $createdAt;
    protected $arrSysUser;

    protected array $svarSamtykke = [];

    /**
     * Constructor
     * @param int|array $data - ID (int) eller database-rad (array)
     */
    public function __construct($data)
    {
        if (is_numeric($data)) {
            $this->_loadById((int) $data);
        } else {
            $this->_loadByRow($data);
        }
    }

    /**
     * Last inn fra ID
     * @param int $id
     * @throws \Exception
     */
    protected function _loadById(int $id): void
    {
        $sql = new Query(
            "SELECT * FROM `" . self::TABLE . "` WHERE `id` = '#id'",
            ['id' => $id]
        );
        $row = $sql->run('array');
        if (!$row) {
            throw new \Exception("Fant ikke SamtykkeVersjon med ID $id");
        }
        $this->_loadByRow($row);
    }

    /**
     * Last inn fra rad-array
     * @param array $row
     */
    protected function _loadByRow(array $row): void
    {
        $this->id         = $row['id'];
        $this->skjemaId   = $row['skjema_id'];
        $this->versjonNr  = $row['versjon_nr'];
        $this->beskrivelse = $row['beskrivelse'] ?? null;
        $this->bodyText   = $row['body_text'] ?? null;
        $this->filePath   = $row['file_path'] ?? null;
        $this->createdAt  = $row['created_at'] ?? null;
        $this->arrSysUser = $row['arr_sys_user'] ?? null;
    }

    /**
     * Opprett en ny versjon for et samtykkeskjema
     * @param int $skjemaId
     * @param string $versjonNr
     * @param string|null $beskrivelse
     * @param string|null $bodyText
     * @param string|null $filePath
     * @return static
     */
    public static function create(int $skjemaId, string $versjonNr, ?string $beskrivelse = null, ?string $bodyText = null, ?string $filePath = null): self
    {
        $sql = new Insert(self::TABLE);
        $sql->add('skjema_id', $skjemaId);
        $sql->add('versjon_nr', $versjonNr);
        if ($beskrivelse !== null) $sql->add('beskrivelse', $beskrivelse);
        if ($bodyText !== null) $sql->add('body_text', $bodyText);
        if ($filePath !== null) $sql->add('file_path', $filePath);

        var_dump($sql->debug());
        $id = $sql->run();
        return new self((int) $id);
    }

    /**
     * Lagre endringer på denne versjonen
     */
    public function save(): void
    {
        $sql = new Query(
            "UPDATE `" . self::TABLE . "`
             SET `versjon_nr` = '#versjon_nr',
                 `beskrivelse` = '#beskrivelse',
                 `body_text` = '#body_text',
                 `file_path` = '#file_path'
             WHERE `id` = '#id'",
            [
                'versjon_nr'  => $this->versjonNr,
                'beskrivelse' => $this->beskrivelse,
                'body_text'   => $this->bodyText,
                'file_path'   => $this->filePath,
                'id'          => $this->id,
            ]
        );
        $sql->run();
    }

    /**
     * Setters
     */
    public function setVersjonNr(string $versjonNr): void { $this->versjonNr = $versjonNr; }
    public function setBeskrivelse(?string $beskrivelse): void { $this->beskrivelse = $beskrivelse; }
    public function setBodyText(?string $bodyText): void { $this->bodyText = $bodyText; }
    public function setFilePath(?string $filePath): void { $this->filePath = $filePath; }

    public function getSvarSamtykke() {
        if( empty($this->svarSamtykke) ) {
            $this->loadSvarSamtykke();
        }
        return $this->svarSamtykke;
    }

    /**
     * Er samtykkeskjemaet godkjent for en gitt bruker?
     * @param int $userId
     * @return bool
     */
    public function isGodkjent($userId) : bool {
        $svar = $this->getSvarSamtykkeForBruker($userId);
        if (!$svar) {
            return false;
        }
        return $svar->isSigned();
    } 

    public function isAnswered($userId) : bool {
        $svar = $this->getSvarSamtykkeForBruker($userId);
        if (!$svar) {
            return false;
        }
        return $svar->getSvar() !== null || $svar->isSigned();

    }

    public function getSvarSamtykkeForBruker($userId) : SvarSamtykke | null {
        return array_values(array_filter($this->getSvarSamtykke(), function($svar) use ($userId) {
            return $svar->getUser() == $userId;
        }))[0] ?? null;
    }

    public function createSamtykkeForBruker($userId) {
        return SvarSamtykke::createNewSkjemaUserSvar($this->id, $userId, false);
    }

    public function createSamtykkeForForesatt($userId) {
        return SvarSamtykke::createNewSkjemaUserSvar($this->id, $userId, true);
    }

  

    /**
     * Laster inn SvarSamtykke for denne versjonen via rel_samtykkeskjema_version_svar.
     */
    private function loadSvarSamtykke() {
        $sql = new Query("
            SELECT s.*
            FROM `rel_samtykkeskjema_version_svar` AS r
            INNER JOIN `" . SvarSamtykke::TABLE . "` AS s
                ON r.`svar_id` = s.`id`
            WHERE r.`skjema_version_id` = '#id'", 
            [
            'id' => $this->id
            ]
        );
        $res = $sql->run();
        while($row = Query::fetch($res)) {
            $this->svarSamtykke[] = new SvarSamtykke($row);
        }
    }

    /**
     * Hent ID på denne versjonen
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
    public function getSamtykkeskjemaId()
    {
        return $this->skjemaId;
    }

    /**
     * Hent versjonsnummer
     * @return string
     */
    public function getVersjonNr()
    {
        return $this->versjonNr;
    }

    /**
     * Hent beskrivelse av versjonen
     * @return string|null
     */
    public function getBeskrivelse(): ?string
    {
        return $this->beskrivelse;
    }

    /**
     * Hent brødtekst / innhold i samtykkeskjemaet
     * @return string|null
     */
    public function getBodyText(): ?string
    {
        return $this->bodyText;
    }

    /**
     * Hent filsti til vedlagt dokument
     * @return string|null
     */
    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    /**
     * Hent opprettelsestidspunkt
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Hent ID til arr-sys-bruker som opprettet versjonen
     * @return int|null
     */
    public function getArrSysUser(): ?int
    {
        return $this->arrSysUser === null ? null : (int) $this->arrSysUser;
    }
}