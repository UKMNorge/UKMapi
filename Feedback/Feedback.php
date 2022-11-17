<?php

namespace UKMNorge\Feedback;


class Feedback {
    protected Int $id;
    protected array $responses; // Array
    protected Int $userId;


    /**
     * Opprett Feedback-objekt
     *
     * @param Int $id
     * @param FeedbackResponse[] $responses
     * @param Int $userId
     */
    public function __construct( Int $id, array $responses, Int $userId) {
        $this->id = $id;
        $this->responses = $responses;
        $this->userId = $userId;
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
     * Hent value
     *
     * @return FeedbackResponse
     */
    public function getResponses() : array {
        return $this->value;
    }

    /**
     * Hvis bare dyttet ut i print, hent verdien da.
     *
     * @return String verdi
     */
    public function __toString()
    {
        $ret = "";
        foreach($this->responses as $response) {
            $ret = $ret + ' ' + $response;
        }
        return $ret;
    }
    
}