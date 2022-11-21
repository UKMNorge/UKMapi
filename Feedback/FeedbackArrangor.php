<?php

namespace UKMNorge\Feedback;


class FeedbackArrangor extends Feedback {

    /**
     * Opprett FeedbackArrangor-objekt
     *
     * @param Int $id
     * @param FeedbackResponse[] $responses
     */
    public function __construct( Int $id, array $responses ) {
		die('Feedback for arrangører er ikke implementert ennå.');
        parent::__construct($id, $responses, -1);

    }

    public function getPlatform() {
        return -1; #plarform er ikke implementert.
    }
    
}