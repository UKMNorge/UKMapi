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
}