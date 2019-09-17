<?php

namespace UKMNorge\API\Mailchimp;

use Exception;

class MCList {

	private $id;
	private $name;
	private $permissionReminder;
	private $stats;
	private $updateExisting = false;
	private $changedSubscribers = array();

	
	public function __construct ( $list_id, $name, $permission_reminder, $stats ) {
		$this->id = $list_id;
		$this->name = $name;
		$this->permissionReminder = $permission_reminder;
		$this->stats = $stats;
	}

	public function getId() {
		return $this->id;
	}

	public function getName() {
		return $this->name;
	}

	public function getPermissionReminder() {
		return $this->permissionReminder;
	}

	public function getStats() {
		return $this->stats;
	}

	public function getChangedSubscribers() {
		return $this->changedSubscribers;
	}

	public function willUpdateExistingSubscribers() {
		return $this->updateExisting;
	}

	// User is identified by email, right?
	public function unsubscribePerson($data) {
		if(!isset($data['email_address']) || $data['email_address'] == "") {
			throw new Exception("Mangler epostadresse å fjerne fra listen.");
		}

		foreach($this->changedSubscribers as $change) {
			if($change['email_address'] == $data['email_address']) {
				throw new Exception("Can't add and then remove a subscriber in the same operation!");
			}
		}

		if(!isset($data['status'])) {
			$data['status'] = 'unsubscribed';
		}

		$this->updateExisting = true;

		// Add user to local list
		$this->changedSubscribers[] = $data;
	}

	public function addSubscriber($data) {
		// verify data:
		if(!isset($data['email_address']) || $data['email_address'] == "") {
			throw new Exception("Kan ikke legge til abonnent - mangler epost.");
		}
		if(!isset($data['first_name']) || $data['first_name'] == "") {
			throw new Exception("Kan ikke legge til abonnent - mangler fornavn.");
		}
		if(!isset($data['last_name']) || $data['last_name'] == "") {
			throw new Exception("Kan ikke legge til abonnent - mangler etternavn.");
		}

		if(!isset($data['email_type'])) {
			$data['email_type'] = 'html';
		}

		if(!isset($data['status'])) {
			$data['status'] = 'subscribed';
		}

		$this->changedSubscribers[] = $data;
	}

}