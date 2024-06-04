<?php

namespace UKMNorge\Feedback;


class FeedbackArrangor extends Feedback {
    private $platform = 2;
    private $campaignId;

    /**
     * Opprett FeedbackArrangor-objekt
     *
     * @param Int $id
     * @param FeedbackResponse[] $responses
     */
    public function __construct( Int $id, array $responses, Int $userId, Int $campaignId) {
        parent::__construct($id, $responses, $userId);
        $this->campaignId = $campaignId;

    }

    /**
     * Hent kampanje ID
     *
     * @return Int
     */
    public function getCampaignId() {
        return $this->campaignId;
    }

    public function save() {
        Write::saveFeedback($this);
    }

    /**
     * Hent platform
     *
     * @return Int
     */
    public function getPlatform() {
        return $this->platform;
    }
    
}