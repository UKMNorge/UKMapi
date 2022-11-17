<?php

namespace UKMNorge\Feedback;


class FeedbackDelta extends Feedback {
    private Int $deltaUserId;

    /**
     * Opprett FeedbackDelta-objekt
     *
     * @param Int $id
     * @param FeedbackResponse[] $responses
     */
    public function __construct( Int $id, array $responses, Int $deltaUserId) {
		parent::__construct($id, $responses);
        $this->deltaUserId = $deltaUserId;
    }


    /**
     * Hent delta deltaker id
     *
     * @return String
     */
    public function hentDeltaUserId() {
        return $this->deltaUserId;
    }
}