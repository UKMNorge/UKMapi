<?php
class tag {
	function __construct($postType, $postID) {
		$this->postType = $postType;
		$this->postID = $postID;
	}
	function set($bid) {
		if(empty($this->postType)
		|| empty($this->postID)
		|| empty($bid)
		|| $this->postType == ''
		|| $this->postID == 0
		|| $this->postID == '0'
		)
			return;
			
		if($this->postType == 'post') {
			## Delete all
			$sql = new SQLdel('smartukm_tags', array('postid'=>$this->postID));
			$res = $sql->run();
				
			$sqlIns = new SQLins('smartukm_tags');
			$sqlIns->add('postid', $this->postID);
			$sqlIns->add('b_id', $bid);
			$res = $sqlIns->run();
		}
	}
	
	function get() {
		if($this->postType == 'post') {
			$sql = new SQL("SELECT `b_id` FROM `smartukm_tags`
							WHERE `postid` = '#POSTID'",
							array('POSTID'=>$this->postID));
			return $sql->run('field','b_id');
		}
	}
}
?>