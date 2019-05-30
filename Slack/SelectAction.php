<?php
	
namespace UKMNorge\Slack;

class SelectAction extends Action {
	
	public function __construct( $id, $name ) {
		parent::__construct( $id, $name );
		$this->setType('select');
	}
}
