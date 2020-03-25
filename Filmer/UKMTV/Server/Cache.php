<?php

namespace UKMNorge\Filmer\UKMTV\Server;

use DateTime;

class Cache {
    var $ip;
    var $status;
    var $last_heartbeat;
    var $space_total;
    var $space_used;
    var $active;

    public function __construct( Array $row ) {
        $this->id = intval($row['id']);
        $this->ip = $row['ip'];
        $this->status = $row['status'];
        $this->last_heartbeat = $row['last_heartbeat'];
        $this->space_total = $row['space_total'];
        $this->space_used = $row['space_used'];
        $this->active = $row['deactivated'] == '0';
    }

    /**
     * Cachens globale ID
     *
     * @return Int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Hent IP-adressen
     * 
     * @return String
     */ 
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Hent status
     * 
     * @return String
     */ 
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Hvor mye serverplass finnes totalt sett?
     *
     * @return Float
     */
    public function getTotalSpace() {
        return $this->space_total;
    }

    /**
     * Hvor mye serverplass er brukt?
     *
     * @return Float
     */
    public function getUsedSpace() {
        return $this->space_used;
    }

    /**
     * Er cache-serveren per definisjon aktiv?
     *
     * @return Bool
     */
    public function erAktiv() {
        return $this->active;
    }

    /**
     * Når var sist serveren sa noe?
     * 
     * @return DateTime
     */ 
    public function getLast_heartbeat()
    {
        var_dump($this);
        if( is_null($this->last_heartbeat_datetime)) {
            $this->last_heartbeat_datetime = new DateTime($this->last_heartbeat);
        }
        return $this->last_heartbeat_datetime;
    }
}