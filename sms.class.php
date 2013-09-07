<?php
class SMS {
	public function __construct($message) {
		UKM_loader('api/sveve/send.php');
		$this->s('message',$message);
		$this->s('from','UKMNorge');
		$this->s('price',0);
		$this->uid();
	}
	
	public function to($number) {
		if(strpos($number,',')!==false)
			$this->s('recipients',substr_count($number,','));
		else
			$this->s('recipients',1);
		$this->s('to',$number);
	}

	public function from($from) {
		$this->s('from',$from);
	}
	
	public function identifier($id) {
		$this->s('senderID',$id);
	}

	public function send() {
		return array('status'=>svevesms_sendSMS('ukm',$this->message,$this->to,$this->from,$this->wpuid, $this->senderID), 'recipients'=>$this->recipients);
	}

	private function uid() {
		$current_user = wp_get_current_user();
		$this->s('wpuid',$current_user->ID);
		$this->s('senderID','SMSclass ukjent');
	}

	private function s($key, $val) {
		$this->$key = $val;
	}
}
?>