<?php

namespace UKMNorge\Feedback;


class FeedbackResponse {
    private Int $id;
    private String $sporsmaal;
    private String $svar;

    /**
     * Opprett FeedbackResponse-objekt
     *
     * @param Int $id
     * @param String $sporsmaal
     * @param String $svar
     */
    public function __construct(Int $id, String $sporsmaal, String $svar) {
        $this->id = $id;
        $this->sporsmaal = $sporsmaal;
        $this->svar = $svar;
    }

    /**
     * Hent id
     *
     * @return Int
     */
    public function getId() : Int {
        return $this->id;
    }

    /**
     * Hent id
     *
     * @return String
     */
    public function getSporsmaal() : String {
        return $this->sporsmaal;
    }

    /**
     * Hent id
     *
     * @return String
     */
    public function getSvar() : String {
        return $this->svar;
    }

    /**
     * Hent id
     *
     * @return String
     */
    public function setSvar(String $svar) : String {
        return $this->svar = $svar;
    }

    
}