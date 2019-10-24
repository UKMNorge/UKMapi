<?php

namespace UKMNorge\API\Mailchimp;

use Exception;
use stdClass;

class Subscriber
{
    var $id;
    var $email;
    var $first_name;
    var $last_name;
    var $subscribed;
    var $email_type = 'html';

    /**
     * Add an array of tags to this Subscriber
     * Tags don't need to exist, they are created on-the-fly if required.
     *
     * @param Array<Tag> $tag
     * @return Result
     */
    public function addTags( Array $tags )
    {
        $errors = [];
        foreach( $tags as $tag ) {
            try {
                $this->tag( $tag );
            } catch( Exception $e ) {
                $errors[] = $e;
            }
        }

        $data = new stdClass();
        $data->errors = $errors;

        return new Result( $data );
    }

    /**
     * Add a tag to this Subscriber
     * 
     * @param Tag $tag
     * @return Bool true
     * @throws Exception Mailchimp Result error
     */
    public function tag( Tag $tag )
    {
        $result = Mailchimp::sendPostRequest(
            '/lists/' . $tag->getAudienceId() . '/segments/' . $tag->getTagId() . '/members',
            [
                'email_address' => $this->getEmail()
            ]
        );

        if( $result->hasError() ) {
            throw new Exception(
                'Could not tag user '. $this->getName() .': '. $tag->getName() .'. '.
                'Mailchimp said: "'. $result->getError()[0]->message .'"'
            );
        }
        
        return true;
    }
    
    /**
     * Remove a tag from this Subscriber
     *
     * @param Tag $tag
     * @return void
     */
    public function unTag( Tag $tag ) {
        throw new Exception(
            'Could not untag user. Implementation missing'
        );
    }

    /**
     * Create an Subscriber instance from email
     *
     * @param String $email
     * @return Subscriber
     */
    public static function createFromEmail( String $email ) {
        $subscriber = new Subscriber($email);
        static::validateEmail( $subscriber );
        return $subscriber;
    }

    /**
     * Create an Subscriber instance from details
     *
     * @param String $email
     * @param String $first_name
     * @param String $last_name
     * @return Subscriber
     */
    public static function createFromDetails( String $email, String $first_name, String $last_name ) {
        $subscriber = new Subscriber($email);
        $subscriber->setFirstname($first_name);
        $subscriber->setLastname($last_name);
        static::validateDetails( $subscriber );
        return $subscriber;
    }

    /**
     * Validate that given Subscriber meets email criteria
     *
     * @param Subscriber $subscriber
     * @throws Exception if missing email
     * @return Bool true
     */
    public static function validateEmail(Subscriber $subscriber)
    {
        if (empty($subscriber->getEmail())) {
            throw new Exception(
                "Kan ikke endre abonnent - mangler epostadresse.",
                182003
            );
        }
        return true;
    }

    /**
     * Validate that given Subscriber has email, first- and last name
     *
     * @param Subscriber $subscriber
     * @throws Exception if missing any
     * @return Bool true
     */
    public static function validateDetails( Subscriber $subscriber ) {
        static::validateEmail( $subscriber );
        
		if( empty( $subscriber->getFirstname() ) ) {
			throw new Exception(
                "Kan ikke endre abonnent - mangler fornavn.",
                182004
            );
		}
		if( empty( $subscriber->getLastname() ) ) {
			throw new Exception(
                "Kan ikke endre abonnent - mangler etternavn.",
                182005
            );
		}
        return true;
    }

    /**
     * Get a subscriber representation for push to mailchimp
     *
     * @param Subscriber $subscriber
     * @return Array $HTTP_RAW_POST_DATA
     */
    public static function getMailchimpRepresentation( Subscriber $subscriber ) {
        return [
            'email_address' => $subscriber->getEmail(),
            'merge_fields' => [
                'FNAME' => $subscriber->getFirstname(),
                'LNAME' => $subscriber->getLastname()
            ],
            'email_type' => $subscriber->getEmailType(),
            'status' => $subscriber->getSubscribed() ? 'subscribed' : 'unsubscribed'
        ];
    }

       /**
     * Create Subscriber object
     *
     * @param String $email
     */
    public function __construct(String $email)
    {
        $this->email = $email;
    }

    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Get the value of first_name
     */
    public function getFirstname()
    {
        return $this->first_name;
    }

    /**
     * Get the value of last_name
     */
    public function getLastname()
    {
        return $this->last_name;
    }
    
    /**
     * Get name of the subscriber
     *
     * @return void
     */
    public function getName() {
        return $this->getFirstname() .' '. $this->getLastname();
    }

    /**
     * Set the value of email
     *
     * @param String $email
     * @return  self
     */
    public function setEmail(String $email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Set the value of first_name
     *
     * @param String $first_name
     * @return  self
     */
    public function setFirstname(String $first_name)
    {
        $this->first_name = $first_name;

        return $this;
    }

    /**
     * Set the value of last_name
     *
     * @param String $last_name
     * @return  self
     */
    public function setLastname(String $last_name)
    {
        $this->last_name = $last_name;

        return $this;
    }

    /**
     * Is the user subscribed to current list?
     * 
     * NOTE: this only supports one list per action, 
     * and could be implemented better using list_id
     * 
     * @return Bool
     */
    public function getSubscribed()
    {
        return $this->subscribed;
    }

    /**
     * Set user subscription status for current list
     *
     * NOTE: this only supports one list per action, 
     * and could be implemented better using list_id
     * 
     * @param Bool $subscriber
     * @return self
     */
    public function setSubscribed(Bool $subscribed)
    {
        $this->subscribed = $subscribed;

        return $this;
    }

    /**
     * Get subscribers preferred email type
     * 
     * Default 'html'
     * 
     * @return String $email_type
     */ 
    public function getEmailType()
    {
        return $this->email_type;
    }

    /**
     * Set the subscribers preferred email type
     *
     * @param String $email_type
     * @return self
     */ 
    public function setEmailType(String $email_type)
    {
        $this->email_type = $email_type;

        return $this;
    }
}
