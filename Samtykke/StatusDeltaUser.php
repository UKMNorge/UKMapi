<?php
namespace UKMNorge\Samtykke;

class StatusDeltaUser extends Status {

    public function __construct( $id, $timestamp, $ip ) {
        parent::__construct( $id, $timestamp, $ip );
    }

}