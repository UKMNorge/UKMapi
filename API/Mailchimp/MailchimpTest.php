<?php

// No auto-loading.
require_once("Mailchimp.php");
require_once("MCList.php");

use PHPUnit\Framework\TestCase;
use UKMNorge\API\Mailchimp;
use UKMNorge\API\Mailchimp\MCList;

class MailchimpTest extends TestCase {

	public function testInit() {
		$mailchimp = new Mailchimp();
		$this->assertInstanceOf(Mailchimp::class, $mailchimp);
		return $mailchimp;
	}

	/**
	 * @depends testInit
	 */
	public function testPing( $mailchimp ) {
		$this->assertTrue($mailchimp->ping());
	}

	/**
	 * @depends testInit
	 */
	public function testGetLists( $mailchimp ) {
		$lists = $mailchimp->getLists();
		$this->assertIsArray($lists);
		$this->assertInstanceOf(stdClass::class, $lists[0]);
	}

	/**
	 * @depends testInit
	 */
	public function testKnownGoodList( $mailchimp ) {
		$list = $mailchimp->getList("ca20f97cda");
		$this->assertInstanceOf(MCList::class, $list);
		$this->assertIsString($list->getName());
	}

	/**
	 * @depends testInit
	 */
	public function testNonExistentList( $mailchimp ) {
		$this->expectException(Exception::class);
		try {
			$mailchimp->getList("ikke-eksisterende-id");	
		} finally {
			$this->assertTrue(true);
		}
	}
}