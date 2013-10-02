<?php
require_once('UKM/mail/class.phpmailer.php');

class UKMmail {
	public function __construct() {
	}
	
	
	public function text( $text ) {
		$this->message = $text;
	}
	
	public function message( $text ) {
		$this->text( $text );
	}
	
	public function to( $to ) {
		$this->recipients = $to;
	}
	
	public function subject( $subject ) {
		$this->subject = $subject;
	}
	
	public function ok() {
		if(empty($this->subject)) {
			$this->error = 'Missing subject!';
			return false;
		}
		if(empty($this->message)) {
			$this->error = 'Missing message body';
			return false;
		}
		if(empty($this->recipients)) {
			$this->error = 'Missing recipients';
			return false;
		}
			
		$mail = new PHPMailer(true);
		$mail->IsSMTP();
		try {
			$mail->SMTPAuth   = true; 
			$mail->SMTPSecure = "";
			$mail->Port		  = 25;
			$mail->Host       = UKM_MAIL_HOST;
			$mail->Username   = UKM_MAIL_USER;
			$mail->Password   = UKM_MAIL_PASS;
			$mail->AddReplyTo(UKM_MAIL_REPLY, UKM_MAIL_FROMNAME);
			$mail->SetFrom(UKM_MAIL_FROM, UKM_MAIL_FROMNAME);
	
			$mail->AddAddress($recipient);
			$mail->Subject = $subject;
	
			#  $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!'; 
			// optional - MsgHTML will create an alternate automatically
			$mail->MsgHTML($body);
			$mail->Send();
			return true;
		} catch (phpmailerException $e) {
			return $e->errorMessage(); //Pretty error messages from PHPMailer
		} catch (Exception $e) {
			return $e->getMessage(); //Boring error messages from anything else!
		}

	}
}