<?php

namespace UKMNorge\SearchArrangorsystemet;


class ClientObject {
    private $id;
    private $navn;
    private $type;
    private $siteUrl;
    private $beskrivelse;

    // Make constructor
    public function __construct(string $id, string $navn, string $type, string $siteUrl, string $beskrivelse='') {
        $this->id = $id;
        $this->navn = $navn;
        $this->type = $type;
        $this->siteUrl = $siteUrl;
        $this->beskrivelse = $beskrivelse;
    }

    public function toArray() {
        $retArr = array();
        $retArr = [
            'id' => $this->id,
            'navn' => $this->navn,
            'type' => $this->type,
            'siteUrl' => $this->siteUrl,
            'beskrivelse' => $this->beskrivelse
        ];

        return $retArr;
    }
}