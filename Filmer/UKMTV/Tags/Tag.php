<?php

namespace UKMNorge\Filmer\UKMTV\Tags;

class Tag {
    /**
     * Opprett tag-objekt
     *
     * @param String $id
     * @param Int|Array $value
     */
    public function __construct( String $id, $value ) {
        if( is_numeric( $value ) ) {
            $value = intval($value);
        }
        $this->id = $id;
        $this->value = $value;
    }

    /**
     * Hent tag'ens id / type / kategori
     *
     * @return String
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Hent tag'ens verdi / foreign key ID
     *
     * @return Int
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * Hvis bare dyttet ut i print, hent verdien da.
     *
     * @return Int verdi
     */
    public function __toString()
    {
        return $this->getValue();
    }
    
}