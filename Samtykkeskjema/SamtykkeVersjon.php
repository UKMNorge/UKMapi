<?php

namespace UKMNorge\Samtykkeskjema;

use UKMNorge\Database\SQL\Query;

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

    protected $samtykkeSvar = [];

    /**
     * Constructor
     * @param array $data - En database-rad med info om versjon
     */
    public function __construct($data)
    {
        $this->id         = $data['id'];
        $this->skjemaId   = $data['skjema_id'];
        $this->versjonNr  = $data['versjon_nr'];
        $this->beskrivelse = $data['beskrivelse'] ?? null;
        $this->bodyText   = $data['body_text'] ?? null;
        $this->filePath   = $data['file_path'] ?? null;
        $this->createdAt  = $data['created_at'];
        $this->arrSysUser = $data['arr_sys_user'] ?? null;
    }

    public function getSamtykkeSvar() {
        if( empty($this->samtykkeSvar) ) {
            $this->loadSamtykkeSvar();
        }
        return $this->samtykkeSvar;
    }

    public function getSamtykkeSvarForBruker($userId) : SamtykkeSvar | null {
        return array_values(array_filter($this->getSamtykkeSvar(), function($svar) use ($userId) {
            return $svar->getUser() == $userId;
        }))[0] ?? null;
    }

    public function createSamtykkeForBruker($userId) {
        return SamtykkeSvar::createNewSamtykkeSvar($this->id, $userId, false);
    }

    public function createSamtykkeForForesatt($userId) {
        return SamtykkeSvar::createNewSamtykkeSvar($this->id, $userId, true);
    }

  

    /**
     * Laster inn SamtykkeSvar for denne versjonen via rel_samtykkeskjema_version_svar.
     */
    private function loadSamtykkeSvar() {
        $sql = new Query("
            SELECT s.*
            FROM `rel_samtykkeskjema_version_svar` AS r
            INNER JOIN `" . SamtykkeSvar::TABLE . "` AS s
                ON r.`svar_id` = s.`id`
            WHERE r.`skjema_version_id` = '#id'", 
            [
            'id' => $this->id
            ]
        );
        $res = $sql->run();
        while($row = Query::fetch($res)) {
            $this->samtykkeSvar[] = new SamtykkeSvar($row);
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