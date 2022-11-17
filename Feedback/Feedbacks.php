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
     * 
     * @return void
     **/
    function getAllForUser(String $userId) {
        return -1;
    }

    /**
     * Last inn alle personer tilhørende innslaget
     * 
     * @return void
     **/
    public function _load()
    {
        $SQL = new Query(
            "SELECT * from Feedback"
        );

        $res = $SQL->run();

        if ($res === false) {
            throw new Exception("Feedback_collection: Klarte ikke hente feedbacks" . $SQL->debug());
        }

        // Legg til Feedback liste
        while ($r = Query::fetch($res)) {
            $id = $r['id'];
            $feedback = new Feedback($id, $this->loadResponses($id), $r['user_id']);
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
            "SELECT * from FeedbackResponse WHERE `feedback_id` = '#feedback_id'",
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
