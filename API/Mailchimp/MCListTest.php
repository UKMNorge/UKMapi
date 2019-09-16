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
		$this->assertIsString($list->getName());
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

	}

}