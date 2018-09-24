<?php
class wp_author {
	var $id = null;
	var $email = null;
	var $loginname = null;
	var $nicename = null;
	var $url = null;
	var $status = null;
	var $deleted = null;
	var $displayname = null;
	var $registered = null;
	
	public function __construct( $id ) {
		$sql = new SQL(
			"SELECT * FROM `wpms2012_users` WHERE `id` = '#id'",
			[
				'id' => (int) $id 
			],
			'wordpress'
		);

		$row = $sql->run('array');
		$this->setId( $row['ID'] );
		$this->setLoginName( $row['user_login'] );
		$this->setNiceName( $row['user_nicename'] );
		$this->setEmail( $row['user_email'] );
		$this->setUrl( $row['user_url'] );	
		$this->setRegistered( $row['user_registered'] );
		$this->setDisplayName( $row['display_name'] );
	}
	
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function setEmail( $email ) {
		$this->email = $email;
		return $this;
	}
	public function getEmail() {
		return $this->email;
	}
	
	public function setLoginName( $loginname ) {
		$this->loginname = $loginname;
		return $this;
	}
	public function getLoginName() {
		return $this->loginname;
	}
	
	public function setNiceName( $nicename ) {
		$this->nicename = $nicename;
		return $this;
	}
	public function getNiceName() {
		return $this->nicename;
	}
	
	public function setUrl( $url ) {
		$this->url = $url;
		return $this;
	}
	public function getUrl() {
		return $this->url;
	}
	
	public function setStatus( $status ) {
		$this->status = $status;
		return $this;
	}
	public function getStatus() {
		return $this->status;
	}
	
	public function setDeleted( $deleted ) {
		$this->deleted = $deleted;
		return $this;
	}
	public function getDeleted() {
		return $this->deleted;
	}
	
	public function setRegistered( $registered ) {
		$this->registered = DateTime::createFromFormat('Y-m-d H:i:s', $registered );
		return $this;
	}
	public function getRegistered() {
		return $this->registered;
	}
	
	public function setDisplayName( $displayname ) {
		$this->displayname = $displayname;
		return $this;
	}
	public function getDisplayName() {
		return $this->displayname;
	}
}