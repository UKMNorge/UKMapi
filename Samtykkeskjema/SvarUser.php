<?php

namespace UKMNorge\Samtykkeskjema;

use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Insert;

use Exception;

require_once('UKM/Autoloader.php');

/**
 * Representerer et individuelt brukersvar på et skjema.
 * Subklasser: SvarSamtykke og SvarSporreskjema.
 */
class SvarUser
{
    const TABLE = 'skjema_svar';

    protected $id;
    protected $versionId;
    protected $svar;
    protected $ipAddress;
    protected $user;
    protected $createdAt;
    protected $isSigned;
    protected $signedMethod;
    protected $isForesatt;

    /**
     * Opprett fra ID eller rad-array
     * @param int|array $data
     * @throws Exception
     */
    public function __construct($data)
    {
        if (is_numeric($data)) {
            $this->_loadById($data);
        } elseif (is_array($data)) {
            $this->_loadByRow($data);
        } else {
            throw new Exception('Kan kun opprette SkjemaUserSvar med numerisk ID eller rad fra database.');
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
            throw new Exception("Fant ikke SkjemaUserSvar med ID $id");
        }
        $this->_loadByRow($row);
    }

    /**
     * Last inn fra rad-array
     * @param array $row
     */
    protected function _loadByRow($row)
    {
        $this->id           = $row['id'];
        $this->versionId    = $row['version_id'];
        $this->svar         = $row['svar'];
        $this->ipAddress    = isset($row['ip_address']) ? $row['ip_address'] : null;
        $this->user         = isset($row['user']) ? $row['user'] : null;
        $this->createdAt = isset($row['created_at']) ? $row['created_at'] : null;
        $this->isSigned     = isset($row['is_signed']) ? (bool)$row['is_signed'] : false;
        $this->signedMethod = isset($row['signed_method']) ? $row['signed_method'] : null;
        $this->isForesatt   = isset($row['is_foresatt']) ? (bool)$row['is_foresatt'] : false;
    }

    // Gettere

    public function getId()
    {
        return $this->id;
    }

    public function getVersionId()
    {
        return $this->versionId;
    }

    public function getSvar()
    {
        return $this->svar;
    }

    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function isSigned()
    {
        return $this->isSigned;
    }

    /**
     * @return string|null 'delta' eller 'arrsys'
     */
    public function getSignedMethod()
    {
        return $this->signedMethod;
    }

    public function isForesatt()
    {
        return $this->isForesatt;
    }

    /**
     * Lagre (sett at denne klassen har allerede data fylt inn!)
     * Lagres som ny rad hvis id mangler.
     */
    public function save()
    {
        if ($this->id) {
            $sql = new Query("
                UPDATE `" . self::TABLE . "`
                SET 
                    `version_id` = '#version_id',
                    `svar` = '#svar',
                    `ip_address` = '#ip_address',
                    `user` = '#user',
                    `is_signed` = '#is_signed',
                    `signed_method` = '#signed_method',
                    `is_foresatt` = '#is_foresatt'
                WHERE `id` = '#id'",
                [
                    'version_id'    => $this->versionId,
                    'svar'          => $this->svar,
                    'ip_address'    => $this->ipAddress,
                    'user'          => $this->user,
                    'is_signed'     => (int)$this->isSigned,
                    'signed_method' => $this->signedMethod,
                    'is_foresatt'   => (int)$this->isForesatt,
                    'id'            => $this->id
                ]
            );
            $sql->run();
        } else {
            $sql = new Insert(self::TABLE);
            $sql->add('version_id', $this->versionId);
            $sql->add('svar', $this->svar);
            $sql->add('ip_address', $this->ipAddress);
            $sql->add('user', $this->user);
            $sql->add('is_signed', (int)$this->isSigned);
            $sql->add('signed_method', $this->signedMethod);
            $sql->add('is_foresatt', (int)$this->isForesatt);

            $this->id = $sql->run();

            $rel = new Insert('rel_samtykkeskjema_version_svar');
            $rel->add('svar_id', $this->id);
            $rel->add('skjema_version_id', $this->versionId);
            $rel->run();
        }
    }

    /**
     * Opprett nytt SkjemaUserSvar
     * SvarUser representerer et individuelt svar på et samtykkeskjema uten å være signert. Signering kan skje senere.
     * @param int $versionId
     * @param int|null $userId
     * @param bool $isForesatt
     * @return static
     */
    public static function createNewSkjemaUserSvar($versionId, $userId, $isForesatt = false) {
        $obj = new self([
            'id'            => null,
            'version_id'    => $versionId,
            'user'          => $userId,
            'is_foresatt'   => $isForesatt ? 1 : 0
        ]);
        $obj->save();
        return $obj;
    }

    /**
     * Registrer brukerens svar på samtykket.
     * Kalles etter at SkjemaUserSvar er opprettet med createNewSkjemaUserSvar().
     * Kan kun gjøres én gang — svaret kan ikke overskrives etter at det er satt.
     *
     * @param string $svar         Brukerens svar, f.eks. 'ja' eller 'nei'
     * @param int $userId         Brukerens ID
     * @param string|null $ipAddress IP-adressen til brukeren (valgfritt)
     * @param string|null $signedMethod Metoden som brukes for å signere samtykket (valgfritt)
     * @throws Exception hvis svaret ikke er lagret, eller allerede har et registrert svar
     */
    public function samtykk(string $svar, int $userId, ?string $ipAddress = null, string $signedMethod = 'delta') : SvarUser
    {
        if (!$this->id) {
            throw new Exception('Kan ikke gi samtykke på et SvarUser som ikke er lagret.');
        }

        if (!empty($this->svar)) {
            throw new Exception('SvarUser med ID ' . $this->id . ' har allerede et registrert svar.');
        }

        $sql = new Query("
            UPDATE `" . self::TABLE . "`
            SET
                `svar` = '#svar',
                `ip_address` = '#ip_address',
                `user` = '#user',
                `is_signed` = 1,
                `signed_method` = '#signed_method',
                `signed_at` = NOW()
            WHERE `id` = '#id' AND `user` = '#user'",
            [
                'svar'       => $svar,
                'ip_address' => $ipAddress,
                'user'       => $userId,
                'id'         => $this->id,
                'signed_method' => $signedMethod,
            ]
        );
        $sql->run();

        new self($this->id);
        return $this;
    }

    /**
     * Registrer at brukeren avslår samtykket.
     * Kan kun gjøres én gang — svaret kan ikke overskrives etter at det er satt.
     *
     * @param int $userId            Brukerens ID
     * @param string|null $ipAddress IP-adressen til brukeren (valgfritt)
     * @throws Exception hvis svaret ikke er lagret, eller allerede har et registrert svar
     */
    public function avsla(int $userId, ?string $ipAddress = null) : SvarUser
    {
        if (!$this->id) {
            throw new Exception('Kan ikke avslå samtykke på et SvarUser som ikke er lagret.');
        }

        if (!empty($this->svar)) {
            throw new Exception('SvarUser med ID ' . $this->id . ' har allerede et registrert svar.');
        }

        $sql = new Query("
            UPDATE `" . self::TABLE . "`
            SET
                `svar` = 'nei',
                `ip_address` = '#ip_address',
                `user` = '#user'
            WHERE `id` = '#id'",
            [
                'ip_address' => $ipAddress,
                'user'       => $userId,
                'id'         => $this->id,
            ]
        );
        $sql->run();

        new self($this->id);
        return $this;
    }
}