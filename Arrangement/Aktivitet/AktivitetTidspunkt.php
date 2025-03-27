<?php

namespace UKMNorge\Arrangement\Aktivitet;

use ElementorPro\Modules\Forms\Fields\Date;
use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Arrangement\Program\Hendelse;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Nettverk\Administrator;
use UKMNorge\Tools\Sanitizer;

use DateTime;

class AktivitetTidspunkt {
    public const TABLE = 'aktivitet_tidspunkt';
    
    private int $tidspunktId;
    private string $sted;
    private DateTime $start;
    private int $varighetMinutter;
    private int $maksAntall;

    private SamlingDeltakere $deltakere;

    private int $aktivitetId; // Foreign key til Aktivitet
    private int|null $hendelseId; // Foreign key til Hendelse. Kan være null.


    public function __construct($id_or_row) {
        if (is_numeric($id_or_row)) {
            $this->_load_by_id($id_or_row);
        } elseif (is_array($id_or_row)) {
            $this->_load_by_row($id_or_row);
        } else {
            throw new Exception('AktivitetTidspunkt: Oppretting av objekt krever numerisk id eller databaserad');
        }
    }

    public function getId() {
        return $this->tidspunktId;
    }

    public function getSted() {
        return $this->sted;
    }

    public function getStart() {
        return $this->start;
    }

    public function getVarighetMinutter() {
        return $this->varighetMinutter;
    }

    public function getMaksAntall() {
        return $this->maksAntall;
    }

    public function getAktivitet() : Aktivitet {
        return new Aktivitet($this->aktivitetId);
    }

    public function getHendelse() : Hendelse {
        return new Hendelse($this->hendelseId);
    }

    /**
     * Hent alle deltakere for dette tidspunktet
     *
     * @return SamlingDeltakere
     */
    public function getDeltakere() {
        if($this->deltakere == null) {
            $this->deltakere = new SamlingDeltakere( $this->getId() );
        }
        return $this->deltakere;
    }

    public static function getLoadQry()
    {
        return "SELECT * FROM `aktivitet_tidspunkt` AS `aktivitet_tidspunkt`";
    }

    private function _load_by_id($id) {
        $qry = new Query(
            self::getLoadQry()
                . "WHERE `aktivitet_tidspunkt`.`tidspunkt_id` = '#id'",
            array('id' => $id)
        );

        $row = Query::fetch($qry->run());
        $this->_load_by_row($row);
    }

    private function _load_by_row($row)
    {
        if (!is_array($row)) {
            throw new Exception('AktivitetTidspunkt: _load_by_row krever dataarray!');
        }
        $this->tidspunktId = $row['tidspunkt_id'];
        $this->sted =  $row['sted'];
        $this->start = new DateTime($row['start']);
        $this->varighetMinutter = $row['varighet_min'];
        $this->maksAntall = $row['maksAntall'];

        $this->aktivitetId = $row['aktivitet_id'];
        $this->hendelseId = $row['c_id'];
    }


}