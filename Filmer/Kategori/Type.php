<?php

namespace UKMNorge\Filmer\Kategori;

class Type {
    var $id = null;
    var $name = null;

    public function __construct( Array $data ) {
        $this->id = intval($data['id']);
        $this->name = $data['name'];
    }

    /**
     * Hent typens navn
     *
     * @return String navn
     */
    public function getNavn() {
        return $this->name;
    }

    /**
     * Hent typens ID
     *
     * @return Int $id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Opprett type fra V1-data
     *
     * @param Int $id
     * @param String $navn
     * @return Type
     */
    public static function getByV1( Int $id, String $navn ) {
        return new Type(
            [
                'id' => $id,
                'name' => $navn
            ]
        );
    }

}