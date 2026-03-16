<?php

namespace UKMNorge\Samtykkeskjema;

use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Insert;

use Exception;

require_once('UKM/Autoloader.php');

/**
 * Representerer et individuelt svar på et enkelt samtykke i et skjema.
 */
class SamtykkeSvar
{
    const TABLE = 'samtykke_samtykkeskjema_svar';

    protected $id;
    protected $versionId;
    protected $svar;
    protected $ipAddress;
    protected $user;
    protected $signedAt;
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
            throw new Exception('Kan kun opprette SamtykkeSvar med numerisk ID eller rad fra database.');
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
            throw new Exception("Fant ikke SamtykkeSvar med ID $id");
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
        $this->signedAt    = isset($row['created_at']) ? $row['created_at'] : null;
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

    public function getsignedAt()
    {
        return $this->signedAt;
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
     * Lag nytt SamtykkeSvar direkte
     * @param int $version_id
     * @param string $svar
     * @param string|null $ip_address
     * @param int|null $user
     * @param bool $is_signed
     * @param string|null $signed_method 'delta' eller 'arrsys'
     * @param bool $is_foresatt
     * @return static
     */
    public static function create(
        $version_id,
        $svar,
        $ip_address = null,
        $user = null,
        $is_signed = false,
        $signed_method = null,
        $is_foresatt = false
    ) {
        $obj = new self([
            'id'            => null,
            'version_id'    => $version_id,
            'svar'          => $svar,
            'ip_address'    => $ip_address,
            'user'          => $user,
            'created_at'    => null,
            'is_signed'     => $is_signed ? 1 : 0,
            'signed_method' => $signed_method,
            'is_foresatt'   => $is_foresatt ? 1 : 0
        ]);
        $obj->save();
        return $obj;
    }
}