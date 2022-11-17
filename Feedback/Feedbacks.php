<?php

namespace UKMNorge\Feedback;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Innslag\Context\Context;
use UKMNorge\Innslag\Typer\Type;

require_once('UKM/Autoloader.php');

class Feedbacks extends Collection
{
    var $context = null;

    /**
     * Opprett en ny collection
     *
     * @param Context $context
     */
    public function __construct() {
        
    }

    /**
     * Get alle for user
     * 
     * @param String $userId
     * @param Int $innslag_id
     * 
     * @return array
     **/
    function getAllForUserOnInnslag(Int $userId, Int $innslag_id) {
        $feedbacks = [];
        
        $SQL = new Query(
            "SELECT *
            FROM feedback
            INNER JOIN rel_innslag_feedback ON rel_innslag_feedback.b_id = '#b_id'
            WHERE feedback.user_id = '#feedback_id'",
            [
                'b_id' => $innslag_id,
                'feedback_id' => $userId
            ]
        );

        $res = $SQL->run();

        if ($res === false) {
            throw new Exception("Feedback_collection: Klarte ikke hente feedbacks");
        }

        // Legg til Feedback liste
        while ($r = Query::fetch($res)) {
            $id = $r['id'];
            $feedback = Feedback::opprettRiktigInstanse($id, $this->loadResponses($id), $r['user_id'], $r['platform']);
            $feedbacks[] = $feedback;
        }

        return $feedbacks;
    }

    /**
     * Last inn alle personer tilhørende innslaget
     * 
     * @return void
     **/
    public function _load()
    {
        $SQL = new Query(
            "SELECT * from feedback"
        );

        $res = $SQL->run();

        if ($res === false) {
            throw new Exception("Feedback_collection: Klarte ikke hente feedbacks");
        }

        // Legg til Feedback liste
        while ($r = Query::fetch($res)) {
            $id = $r['id'];
            $feedback = Feedback::opprettRiktigInstanse($id, $this->loadResponses($id), $r['user_id'], $r['platform']);
            $this->add($feedback);
        }
    }

    /**
     * Last inn alle FeedbackResponse
     * 
     * @return array FeedbackResponse
     **/
    private function loadResponses($feedbackId) : array {
        $responses = array();
        $SQL = new Query(
            "SELECT * from feedback_response WHERE `feedback_id` = '#feedback_id'",
            [
                'feedback_id' => $feedbackId
            ]
        );
        
        $res = $SQL->run();

        while ($r = Query::fetch($res)) {
            $id = $r['id'];
            $sporsmaal = $r['sporsmaal'];
            $svar = $r['svar'];
            $responses[] = new FeedbackResponse($id, $sporsmaal, $svar);
        }
        return $responses;
    }
}
