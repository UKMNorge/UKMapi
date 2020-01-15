<?php

namespace UKMNorge\Filmer\Kategori;

use UKMNorge\Filmer\Server\Server;

class Kategori {

    var $id = null;
    var $navn = null;
    var $url = null;
    var $type = null;

    /**
     * Opprett kategori-objekt
     *
     * @param Array $data
     * @return Kategori
     */
    public function __construct( Array $data ) {
        $this->id = $data['id'];
        $this->navn = $data['title'];
        $this->parent = $data['parent'];
        $this->type = Type::getByV1( intval($data['type_id']), $data['type_title'] );
    }

    /**
     * Hent kategoriens tittel
     *
     * @return String tittel
     */
    public function getNavn() {
        return $this->navn;
    }

    /**
     * Hent kategoriens URL
     *
     * @return String Url
     */
    public function getUrl() {
        return Server::getTvUrl().'kategorier/'. $this->getNavn();
    }

    /**
     * Hvilken type kategori er dette?
     *
     * @return Type
     */
    public function getType(){
        return $this->type;
    }

    /**
     * Opprett kategori på UKMTV V1-måten
     *
     * @param Int $type_id
     * @param String $type_navn
     * @param Int $id
     * @param String $kategori_navn
     * @param Int $parent_id
     * @return Kategori
     */
    public static function getByV1( Int $type_id, String $type_title, Int $id, String $kategori_navn, Int $parent_id ) {
        return new Kategori(
            [
                'id' => $id,
                'title' => $kategori_navn,
                'parent' => $parent_id,
                'type_id' => $type_id,
                'type_title' => $type_title
            ]
        );
    }
}