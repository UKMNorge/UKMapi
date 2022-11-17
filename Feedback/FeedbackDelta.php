<?php

namespace UKMNorge\Feedback;


class FeedbackDelta extends Feedback {
    private Int $platform = 1;

    /**
     * Opprett FeedbackDelta-objekt
     *
     * @param Int $id
     * @param FeedbackResponse[] $responses
     */
    public function __construct( Int $id, array $responses, Int $deltaUserId) {
		parent::__construct($id, $responses, $deltaUserId);
    }

    /**
     * Returner plattformen
     * 
     * @return Int
     */
    function getPlatform() {
        return $this->platform;
    }

    /**
     * Lagre feedback i et innslag
     * @param Int $innslag_id
     * 
     * @return Int
     */
    public function saveMedInnslag(Int $innslag_id) {
        Write::saveFeedbackWithInnslag($this, $innslag_id);
    }
}