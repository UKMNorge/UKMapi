<?php
class flickr_album {
	var $id = false;
	var $flickr_id = false;
	var $flickr_album_name = false;
	
	public function __construct( $type, $id ) {
		$this->type = $type;
		$this->id = $id;
		
		$sql = new SQL("SELECT *
						FROM `flickr_albums`
						WHERE `object_type` = '#type'
						AND `object_id` = '#id'",
					array('type'=>$type, 'id'=>$id)
					);
		$res = $sql->run('array');
		
		$this->flickr_album_name = $res['flickr_album_name'];
		$this->flickr_id = $res['flickr_album_id'];
	}
	
	public function getFlickrId() {
		return $this->flickr_id;
	}
	
	public function setFlickrId( $flickr_id ) {
		if( false == $this->id ) {
			throw new Exception('Cannot set Flickr ID of album without ID. Maybe run create?');
		}
		$sql = new SQLins('flickr_albums', array('object_type'=>$this->type, 'object_id'=>$this->id) );
		$sql->add('flickr_id', $flickr_id);
		$sql->run();
		
		return true;
	}
	
	public function setFlickrAlbumName( $album_name ) {
		if( false == $this->id ) {
			throw new Exception('Cannot set Flickr ID of album without ID. Maybe run create?');
		}
		$this->flickr_album_name = $album_name;

		$sql = new SQLins('flickr_albums', array('object_type'=>$this->type, 'object_id'=>$this->id) );
		$sql->add('flickr_album_name', $album_name);
		$sql->run();
	}
	
	public function getFlickrAlbumName() {
		return $this->flickr_album_name;
	}
	
	public function create( $object_type, $object_id, $flickr_album_id, $flickr_album_name ) {
		$sql = new SQLins('flickr_albums');
		$sql->add('object_type', $object_type);
		$sql->add('object_id', $object_id);
		$sql->add('flickr_album_id', $flickr_album_id);
		$sql->add('flickr_album_name', $flickr_album_name);
		$insert_id = $sql->run();
		
		$this->flickr_album_name = $album_name;
		$this->flickr_id = $flickr_album_id;
		$this->object_type = $object_type;
		$this->object_id = $object_id;		
		$this->id = $insert_id;
		
		return $this->id;
	}
}
