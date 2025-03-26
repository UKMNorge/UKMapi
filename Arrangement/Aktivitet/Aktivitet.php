<?php

namespace UKMNorge\Arrangement\Aktivitet;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Nettverk\Administrator;
use UKMNorge\Tools\Sanitizer;


class Aktivitet implements AktivitetInterface {
    public final static $table = 'aktivitet';
    
    private int $aktivitetId;
    private string $navn;
    private string $sted;
    private string $beskrivelse;
    private int $plId;

    private SamlingTidspunkter $tidspunkter = [];

    public function __construct(int $id_or_row) {
        if (is_numeric($id_or_row)) {
            $this->_load_by_id($id_or_row);
        } elseif (is_array($id_or_row)) {
            $this->_load_by_row($id_or_row);
        } else {
            throw new Exception('Aktivitet: Oppretting av objekt krever numerisk id eller databaserad');
        }
    }

    public function getId() {
        return $this->aktivitetId;
    }

    public function getNavn() {
        return $this->navn;
    }

    public function getSted() {
        return $this->sted;
    }

    public function getBeskrivelse() {
        return $this->beskrivelse;
    }

    public function getArrangement() : Arrangement {
        return new Arrangement($this->plId);
    }

    /**
     * Hent alle tidspunkter for denne aktiviteten
     *
     * @return SamlingTidspunkter
     */
    public function getTidspunkter() {
        if($this->tidspunkter == null) {
            $this->tidspunkter = new SamlingTidspunkter( $this->getId() );
        }
        return $this->tidspunkter;
    }

    public static function getLoadQry()
    {
        return "SELECT * FROM `aktivitet` AS `aktivitet`";
    }

    private function _load_by_id($id) {
        $qry = new Query(
            self::getLoadQry()
                . "WHERE `aktivitet`.`aktivitet_id` = '#id'",
            array('id' => $id)
        );
        $res = $qry->run('array');
        if ($res) {
            $this->_load_by_row($res);
        } else {
            throw new Exception('Aktivitet: Fant ikke aktivitet ' . $id);
        }
    }

    private function _load_by_row($row)
    {
        if (!is_array($row)) {
            throw new Exception('Aktivitet: _load_by_row krever dataarray!');
        }
        $this->aktivitetId = $row['aktivitet_id'];
        $this->navn =  $row['navn'];
        $this->sted = $row['sted'];
        $this->beskrivelse = $row['beskrivelse'];
        $this->plId = $row['pl_id'];
    }

}