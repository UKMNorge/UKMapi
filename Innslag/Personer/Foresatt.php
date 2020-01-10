<?php

namespace UKMNorge\Innslag\Personer;

use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;

require_once('UKM/Autoloader.php');

class Foresatt {

  var $foresatt_id = null;
  var $p_id = null;
  var $foresatt_navn = null;
  var $foresatt_mobil = null;

  public function __construct(Int $p_id)
  {
    $sql = new Query("SELECT * from `ukm_foresatte_info` WHERE `p_id` = '$p_id'");
    $res = $sql->getArray();
    if(!$res['p_id'])
    {
      throw new \Exception("Klarte ikke finne foresatt for $p_id", 1);
    }
      $this->foresatt_id = $res['id'];
      $this->p_id = $res['p_id'];
      $this->foresatt_navn = $res['foresatte_navn'];
      $this->foresatt_mobil = $res['foresatte_mobil'];
  }

  public static function oppdaterTid($p_id)
  {
    $sql = new Update('smartukm_participant', ['p_id' => $p_id]);
    $sql->add('p_sistsendt', date("Y-m-d H:i:s"));
    $result = $sql->run();
  }

  public function getNavn()
  {
    return $this->foresatt_navn;
  }

  public function getMobil()
  {
    return $this->foresatt_mobil;
  }
}