<?php

namespace UKMNorge\API\Mailchimp;

use Exception;

class Audience
{

    private $id;
    private $name;
    private $permission_reminder;
    private $stats;
    private $mailchimp_data;
    private $tags;
    private $update_existing = true;
    private $update_subscribers = [];

    /**
     * Create Audience-instance from API-data
     *
     * @param [type] $data
     * @return void
     */
    public static function createFromAPIdata($data)
    {
        $audience = new Audience($data->id);
        $audience->setName($data->name);
        $audience->setPermissionReminder($data->permission_reminder);
        $audience->setStats($data->stats);
        $audience->mailchimp_data = $data;
        return $audience;
    }

    /**
     * Create Audience-instance
     *
     * @param String $list_id
     */
    public function __construct(String $list_id)
    {
        $this->id = $list_id;
    }

    /**
     * Get mailchimp audience ID
     *
     * @return String
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get audience name
     *
     * @return String $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the audience name
     *
     * @param String $name
     * @return  self
     */
    public function setName(String $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get permission reminder 
     * @todo @asgeirsh documentation?
     *
     * @return void
     */
    public function getPermissionReminder()
    {
        return $this->permissionReminder;
    }

    /**
     * Set the permission_reminder
     *
     * @param String $permission_reminder
     * @return self
     */
    public function setPermissionReminder(String $permission_reminder)
    {
        $this->permission_reminder = $permission_reminder;

        return $this;
    }

    /**
     * Get statistics
     * @todo @asgeirsh documentation?
     * 
     * @return void
     */
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * Set stats
     *
     * @param $stats
     * @return  self
     */
    public function setStats($stats)
    {
        $this->stats = $stats;

        return $this;
    }

    /**
     * Get subscribers requested updated
     *
     * @return Array<Subscriber>
     */
    public function getUpdateSubscribers()
    {
        return $this->update_subscribers;
    }

    /**
     * Get the raw data when fetched from mailchimp
     */
    public function getMailchimpData()
    {
        if (null == $this->mailchimp_data) {
            throw new Exception(
                'Mailchimp data is only accessible when instance is fetched from API',
                582002
            );
        }
        return $this->mailchimp_data;
    }

    /**
     * Should mailchimp update existing subscribers?
     */
    public function getUpdateExisting()
    {
        return $this->update_existing;
    }

    /**
     * Should mailchimp update existing subscribers?
     *
     * @param Bool update
     * @return  self
     */
    public function setUpdateExisting(Bool $update_existing)
    {
        $this->update_existing = $update_existing;

        return $this;
    }


    /**
     * Unubscribe user from list
     * Does not persist to mailchimp!
     * 
     * @see persist()
     *
     * @param Subscriber $subscriber
     * @return void
     */
    // User is identified by email, right?
    public function unsubscribe(Subscriber $subscriber)
    {
        Subscriber::validateEmail($subscriber);

        // Add to queue
        $subscriber->setSubscribed(false);
        $this->update_subscribers[$subscriber->getEmail()] = $subscriber;
        $this->setUpdateExisting(true); // To unsubscribe you have to update?
    }

    /**
     * Subscribe user to list
     * Does not persist to mailchimp!
     * 
     * @see persist()
     *
     * @param Subscriber $subscriber
     * @return void
     */
    public function subscribe(Subscriber $subscriber)
    {
        Subscriber::validateDetails($subscriber);

        $subscriber->setSubscribed(true);

        if ($subscriber->getEmailType() == null) {
            $subscriber->setEmailType('html');
        }
        $this->update_subscribers[$subscriber->getEmail()] = $subscriber;
    }

    /** 
     * Returns all tags that exist.
     * Note: Tags belong to a list - but we'll only ever be using one list.
     *
     * @return Tags
     */
    public function getTags()
    {
        if (null == $this->tags) {
            $this->tags = new Tags();
            $this->tags->setAudienceId($this->getId());
        }
        return $this->tags;
    }

    /**
     * Push all changes to Mailchimp
     * 
     * @return Result
     * @throws Exception too many members or could not persist.
     */
    public function persist()
    {
        // You can add up to 500 members for each API call
        $limit = 500;
        $data = [
            'members' => static::convertSubscribersToMailchimpData($this->getUpdateSubscribers()),
            'update_existing' => $this->getUpdateExisting()
        ];

        if (count($data['members']) > $limit) {
            throw new Exception(
                "Can only apply changes to 500 subscribers per API-call",
                182007
            );
        }

        $result = Mailchimp::sendPostRequest("lists/" . $this->getId(), $data);

        if ($result->hasError()) {
            throw new Exception(
                'Could not persist audience changes to Mailchimp',
                182006
            );
        }
        return $result;
    }

    /**
     * Convert an array of subscribers to an array of mailchimp-data
     * 
     * @param Array $subscribers
     * @return Array $subscriber_data
     */
    public static function convertSubscribersToMailchimpData(array $subscribers)
    {
        $data = [];
        foreach ($subscribers as $subscriber) {
            $data[] = Subscriber::getMailchimpRepresentation($subscriber);
        }
        return $data;
    }
}
