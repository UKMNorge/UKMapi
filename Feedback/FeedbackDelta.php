<?php

namespace UKMNorge\Feedback;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Innslag\Innslag;
use Exception;

class FeedbackDelta extends Feedback {
    private Int $platform = 1;

    /**
     * Opprett FeedbackDelta-objekt
     *
     * @param Int $id
     * @param FeedbackResponse[] $responses
     */
    public function __construct(Int $id, array $responses, Int $deltaUserId) {
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

   /**
     * Get innslag i Feedbacken
     * 
     * HUSK: Denne kan returnere null hvis innslaget finnes ikke eller hvis det skjer noe feil
     * 
     * 
     * @return Innslag|null
     **/
    public function getInnslag() : Innslag {   
        $SQL = new Query(
            "SELECT rel_innslag_feedback.b_id
            FROM rel_innslag_feedback
            WHERE rel_innslag_feedback.feedback_id = '#feedback_id'
            LIMIT 1",
            [
                'feedback_id' => $this->id
            ]
        );

        try {
            $res = Query::fetch($SQL->run());
            if(!$res) return null;

            return Innslag::getById($res['b_id']);
        } catch(Exception $e) {
            return null;
        }
        
    }
}