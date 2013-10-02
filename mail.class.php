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
		$this->text( $text );
		return $this;
	}
	
	public function to( $to ) {
		$this->recipients = explode(',', $to);
		return $this;
	}
	
	public function subject( $subject ) {
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
}