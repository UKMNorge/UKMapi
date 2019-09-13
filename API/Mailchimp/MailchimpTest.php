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

	}

	/**
	 * @depends testInit
	 */
	public function testEmptyResource( $mailchimp ) {
		//$this->assertThrows
	}

	/**
	 * @depends testInit
	 */
	public function testNonExistentList( $mailchimp ) {

	}
}