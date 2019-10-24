<?php

// No autoloading here, unfortunately
require_once("Mailchimp.php");
require_once("MCList.php");

use PHPUnit\Framework\TestCase;
use UKMNorge\API\Mailchimp;
use UKMNorge\API\Mailchimp\MCList;


class MCListTest extends TestCase {
	public function testInit () {
		$mailchimp = new Mailchimp();
		$list = $mailchimp->getList('ca20f97cda');
		$this->assertInstanceOf(MCList::class, $list);
		return $list;
	}

	/**
	 * @depends testInit
	 */
	public function testGetName($list) {
		$this->assertSame($list->getName(), "UKM Media Norge");
	}

	/**
	 * @depends testInit
	 */
	public function testAddPersonWithAllData($list) {
		$data = array();
		$data['email_address'] = "asgeirsh@ukmmedia.no";
		$data['email_type'] = "html";
		$data['status'] = "subscribed";
		$data['first_name'] = "Asgeir";
		$data['last_name'] = "Hustad";
		$list->addSubscriber($data);

		// Assert that the only element in the changed list matches our data.
		$changes = $list->getChangedSubscribers();
		$this->assertTrue($changes[0] == $data);
	}

	/**
	 * @depends testInit
	 */
	public function testAddPersonWithBareBones($list) {
		$data = array();
		$data['email_address'] = "asgeirsh@ukmmedia.no";
		$data['first_name'] = "Asgeir";
		$data['last_name'] = "Hustad";
		$list->addSubscriber($data);

		$changes = $list->getChangedSubscribers();
		$this->assertTrue($changes[0]['email_address'] == $data['email_address']);

		$this->assertTrue($changes[0]['email_type'] == "html");
		$this->assertTrue($changes[0]['status'] == "subscribed");
	}

	public function testRemovePersonWithEmailOnly() {
		$this->markTestIncomplete("Can't unsubscribe this email, it's used for testing tags etc in MailchimpTest.php");
		$mailchimp = new Mailchimp();
		$list = $mailchimp->getList('ca20f97cda');
		
		$data = array();
		$data['email_address'] = "asgeirsh@ukmmedia.no";
		$list->unsubscribePerson($data);

		$changes = $list->getChangedSubscribers();
		$this->assertSame($changes[0]['email_address'], $data['email_address'], "Epost matcher ikke");
		$this->assertSame($changes[0]['status'], "unsubscribed", "Status er ikke unsubscribe");
	}

	public function testUnsubscribePerson() {

		$this->markTestIncomplete("Can't unsubscribe this email, it's used for testing tags etc in MailchimpTest.php");
		
		$mailchimp = new Mailchimp();
		$list = $mailchimp->getList('ca20f97cda');

		$data = array();
		$data['email_address'] = "asgeirsh@ukmmedia.no";
		$list->unsubscribePerson($data);

		$this->assertTrue($mailchimp->saveListChanges($list));
	}

	/**
	 * @depends testUnsubscribePerson
	 */
	public function testActuallyAddPerson() {

		$mailchimp = new Mailchimp();
		$list = $mailchimp->getList('ca20f97cda');
		
		$data = array();
		$data['email_address'] = "asgeirsh@ukmmedia.no";
		$data['first_name'] = "Asgeir";
		$data['last_name'] = "Hustad";
		$list->addSubscriber($data);

		// This will fail if the user is already in the list, which means we really should be using a trashable dummy-list. Or a mocked service.
		$saved = $mailchimp->saveListChanges($list);
		if($saved) {
			$this->assertTrue($saved, "Saved new person");	
		} else {
			$this->assertSame($mailchimp->getFailedUpdates()[0]->error, "asgeirsh@ukmmedia.no is already a list member, do you want to update? please provide update_existing:true in the request body", "Email is member, can't re-add");
		}
	}
}