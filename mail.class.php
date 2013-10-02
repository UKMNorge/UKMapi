<?php
require_once('UKM/mail/class.phpmailer.php');

class UKMmail {
	public function __construct() {
	}
	
	
	public function text( $text ) {
		$this->message = $text;
		return $this;
	}
	
	public function message( $text ) {
		if (!preg_match('!!u', $text))
			$text = utf8_encode($text);

		if(strlen($text) != strlen(strip_tags($text))) {
			nl2br($this->_find_links($text));
		}


		$this->text( $text );
		return $this;
	}
	
	public function to( $to ) {
		$this->recipients = explode(',', $to);
		return $this;
	}
	
	public function subject( $subject ) {
		if (!preg_match('!!u', $subject))
			$subject = utf8_encode($subject);
			
		$this->subject = $subject;
		return $this;
	}
	
	public function ok() {
		if(empty($this->subject))
			return 'Missing subject!';

		if(empty($this->message))
			return 'Missing message body';

		if(empty($this->recipients))
			return 'Missing recipients';
			
		$mail = new PHPMailer(true);
		$mail->IsSMTP();
		$mail->CharSet = 'UTF-8';
		try {
			$mail->SMTPAuth   = true; 
			$mail->SMTPSecure = "";
			$mail->Port		  = 25;
			$mail->Host       = UKM_MAIL_HOST;
			$mail->Username   = UKM_MAIL_USER;
			$mail->Password   = UKM_MAIL_PASS;
			$mail->AddReplyTo(UKM_MAIL_REPLY, UKM_MAIL_FROMNAME);
			$mail->SetFrom(UKM_MAIL_FROM, UKM_MAIL_FROMNAME);
	
			foreach($this->recipients as $recipient) 
				$mail->AddAddress($recipient);
				
			$mail->Subject = $this->subject;

			$mail->MsgHTML($this->message);
	
			#  $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!'; 
			// optional - MsgHTML will create an alternate automatically

			$mail->Send();
			
			return true;
		} catch (phpmailerException $e) {
			return 'Mailer: '. $e->errorMessage(); //Pretty error messages from PHPMailer
		} catch (Exception $e) {
			return 'Mailer: '. $e->getMessage(); //Boring error messages from anything else!
		}
	return true;
	}
	
	private function _find_links($text) {
		return  preg_replace(
			array(
			'/(?(?=<a[^>]*>.+<\/a>)
			     (?:<a[^>]*>.+<\/a>)
			     |
			     ([^="\']?)((?:https?|ftp|bf2|):\/\/[^<> \n\r]+)
			 )/iex',
			'/<a([^>]*)target="?[^"\']+"?/i',
			'/<a([^>]+)>/i',
			'/(^|\s)(www.[^<> \n\r]+)/iex',
			'/(([_A-Za-z0-9-]+)(\\.[_A-Za-z0-9-]+)*@([A-Za-z0-9-]+)
			(\\.[A-Za-z0-9-]+)*)/iex'
			),
			array(
			"stripslashes((strlen('\\2')>0?'\\1<a href=\"\\2\">\\2</a>\\3':'\\0'))",
			'<a\\1',
			'<a\\1 target="_blank">',
			"stripslashes((strlen('\\2')>0?'\\1<a href=\"http://\\2\">\\2</a>\\3':'\\0'))",
			"stripslashes((strlen('\\2')>0?'<a href=\"mailto:\\0\">\\0</a>':'\\0'))"
			),
			$text
		);
	}
}