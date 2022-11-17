<?php

namespace UKMNorge\Feedback;
use Exception;


abstract class Feedback {
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
     * Opprett og returner rikit instanse basert på platform
     *
     * @param Int $id
     * @param FeedbackResponse[] $responses
     * @param Int $userId
     * @param Int $platform
     * @return FeedbackDelta|FeedbackArrangor
     */
    public static function opprettRiktigInstanse(Int $id, array $responses, Int $userId, Int $platform) {
        // Delta (påmeldingssystemet) er platform 1
        if($platform == 1) {
            return new FeedbackDelta($id, $responses, $userId);
        }

        // Platformen er ikke definert i systemet.
        throw new Exception('Feedback platform ' . $platform . ' er ikke definert i systemet enda!.');
    }

    /**
     * Lagre Feedback-en
     *
     * @return Int
     */
    public function save() {
        return Write::saveFeedback($this);
    }

    /**
     * Legg til response
     * @param FeedbackResponse $feedbackResponse
     * @return void
     */
    public function leggTilResponse(FeedbackResponse $feedbackResponse) {
        $this->responses[] = $feedbackResponse;
    }

    /**
     * Returner plattformen
     * Alle subklasser skal implementere det og returnere plattform id
     * 
     * @return Int
     */
    abstract function getPlatform();

    /**
     * Hent id
     *
     * @return Int
     */
    public function getId() : Int {
        return $this->id;
    }

    /**
     * Hent user id
     *
     * @return Int
     */
    public function getUserId() : Int {
        return $this->userId;
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