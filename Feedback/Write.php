<?php
    
namespace UKMNorge\Feedback;
use Exception;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Update;

require_once('UKM/Autoloader.php');

class Write {
		
	/**
     * Lagre Feedback og FeedbackResponses i Feedback-en
     *
     * Hvis Feedback har id -1, lagres det, ellers hvis feedback id finnes da oppdateres det
     * 
     * @param Feedback $feedback
     * @return int|false feedback_id eller false hvis det ikke gikk bra
     */
	public static function saveFeedback(Feedback $feedback ) {		
        // Sjekker om det er ny Feedback (-1) eller som finnes fra før (har en id fra før);
        if($feedback->getId() > -1) {
            // oppdater
            $feedback_id = static::updateFeedback($feedback);
        }
        else {
            // opprett
            $sql = new Insert('feedback');
            $sql->add('user_id', $feedback->getUserId() );
            $sql->add('platform', $feedback->getPlatform());		
            $feedback_id = $sql->run();
        }
        
        if(!$feedback_id) return false;

        // Lagre responses på Feedback
        $responses = static::saveResponses($feedback, $feedback_id);

        // Hvis responses er lagret, returner feedback_id, ellers false
        return $responses ? $feedback_id : false;
	}

    /**
     * Oppdater feedback
     * 
     * @param Feedback $feedback
     * @return Int feedback id
     **/
    private static function updateFeedback(Feedback $feedback) {
        $sql = new Update('feedback', ['id' => $feedback->getId(), 'user_id' => $feedback->getUserId(), 'platform' => $feedback->getPlatform()]);
        $res = $sql->run();
        
        return $feedback->getId();
    }

    /**
     * Opprette eller oppdatere response (FeedbackResponse) på en Feedback
     * 
     * @param Feedback $feedback
     * @param Int $feedback_id
     * 
     * @return Bool
     **/
    public static function saveResponses(Feedback $feedback, $feedback_id) {
        foreach($feedback->getResponses() as $response) {
            if($response->getId() > -1) {
                $sql = new Update('feedback_response', ['id' => $feedback->getId()]);
            }
            else {
                $sql = new Insert('feedback_response', []);
            }
            $sql->add('sporsmaal', $response->getSporsmaal());
            $sql->add('svar', $response->getSvar());
            $sql->add('feedback_id', $feedback_id);

            $response_id = $sql->run();

            if($response_id == false && $response_id != 0) return false;
        }
        return true;
    }
	
    /**
     * Lagre Feedback på et innslag
     * 
     * @param Feedback $feedback
     * @param Int $b_id
     * 
     * @return Bool
     **/
    public static function saveFeedbackWithInnslag(Feedback $feedback, Int $b_id) {
        // Lagre feedback og alle responses
        $feedbackId = static::saveFeedback($feedback);
        if(!$feedbackId) return false;

        // Lagre kobilingen med innslag
        $sql = new Insert('rel_innslag_feedback');
        $sql->add('b_id', $b_id );
        $sql->add('feedback_id', $feedbackId);		

        $rel_id = $sql->run();

        if($rel_id) return true;
        return false;
    }
}