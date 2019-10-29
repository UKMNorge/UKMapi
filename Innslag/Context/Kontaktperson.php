<?php

namespace UKMNorge\Innslag\Context;

require_once('UKM/Autoloader.php');

class Kontaktperson
{
    var $id = null;

    /**
     * Opprett kontaktperson-context
     *
     * @param Int $id
     */
    public function __construct(Int $id)
    {
        $this->id = $id;
    }

    /**
     * Sett kontaktperson-ID
     *
     * @param Int $id
     * @return self
     */
    public function setId(Int $id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Hent kontaktperson-ID
     *
     * @return Int $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sjekk at gitt objekt er gyldig Context\Kontaktperson-objekt
     *
     * @param Any $object
     * @return Bool
     */
    public static function validateClass($object)
    {
        return is_object($object) && get_class($object) == 'UKMNorge\Innslag\Context\Kontaktperson';
    }
}
