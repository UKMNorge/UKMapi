<?php

namespace UKMNorge\API\Mailchimp;

class MCList {

	private $id;
	private $name;
	private $permissionReminder;
	private $stats;
	var $addedSubscribers = [];
	var $removedSubscribers = [];
	
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

	// User is identified by email, right?
	public function deleteSubscriber($data) {
		if(!isset($data['email']) || $data['email'] == "") {
			throw new Exception("Mangler epostadresse å fjerne fra listen.");
		}
		// Add user to local list
		$removedSubscribers[] = $data;
	}

	public function addSubscriber($data) {
		// verify data:
		if(!isset($data['email']) || $data['email'] == "") {
			throw new Exception("Kan ikke legge til epostadresse - mangler epost.");
		}
		if(!isset($data['first_name']) || $data['first_name'] == "") {
			throw new Exception("Kan ikke legge til epostadresse - mangler fornavn.");
		}
		if(!isset($data['last_name']) || $data['last_name'] == "") {
			throw new Exception("Kan ikke legge til epostadresse - mangler etternavn.");
		}

		$addedSubscribers[] = $data;
	}

}