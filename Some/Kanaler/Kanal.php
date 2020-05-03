<?php

namespace UKMNorge\Some\Kanaler;

class Kanal
{

    const TABLE = 'some_kanal';

    public $id;
    public $navn;
    public $handlebar;
    public $url;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->navn = $data['navn'];
        $this->handlebar = $data['handlebar'];
        $this->url = $data['url'];
    }

    /**
     * Hent kanalens Id
     *
     * @return String
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Hent kanalens navn (brand name, ikke side-navn)
     *
     * @return String
     */
    public function getNavn()
    {
        return $this->navn;
    }

    /**
     * Hent kanalens handlebar (@ukmnorge)
     *
     * @return String
     */
    public function getHandlebar()
    {
        return $this->handlebar;
    }

    /**
     * Hent kanalens url
     *
     * @return String
     */
    public function getUrl()
    {
        return $this->url;
    }
}
