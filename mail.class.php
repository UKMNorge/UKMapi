<?php
require_once('lib/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Misd\Linkify\Linkify;

class UKMmail {
	var $reply_to = null;
	var $send_from = null;
	public function __construct() {
		$this->reply_to = new stdClass();
		$this->reply_to->mail = UKM_MAIL_REPLY;
		$this->reply_to->name = UKM_MAIL_FROMNAME;

		$this->send_from = new stdClass();
		$this->send_from->mail = UKM_MAIL_FROM;
		$this->send_from->name = UKM_MAIL_FROMNAME;
	}
	
	public function setFrom( $mail, $name ) {
		$this->send_from->mail = $mail;
		$this->send_from->name = $name;
		return $this;
	}
	public function setReplyTo( $mail, $name ) {
		$this->reply_to->mail = $mail;
		$this->reply_to->name = $name;
		return $this;
	}
	
	public function text( $text ) {
	
		if (!preg_match('!!u', $text))
			$text = utf8_encode($text);

		if(strlen($text) == strlen(strip_tags($text))) {
			$linkify = new Linkify();
			$text = nl2br( $linkify->process( $text ) );
		}
	
		$this->message = $text;
		return $this;
	}
	
	public function message( $text ) {
		return $this->text( $text );
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
			$mail->SetFrom( $this->send_from->mail, $this->send_from->name );
	
			$supportIsRecipient = false;
			foreach($this->recipients as $recipient) {
				// Hvis support er mottaker, må svar-til (og avsender?) ikke være
				// support, da freshdesk nekter å motta den da...
				if( $recipient == UKM_MAIL_REPLY && $this->reply_to->mail == UKM_MAIL_REPLY ) {
					$supportIsRecipient = true;
				}
				$mail->AddAddress($recipient);
			}
			
			if( $supportIsRecipient ) {
				$mail->AddReplyTo( $this->send_from->mail, $this->send_from->name );
			} else {
				$mail->AddReplyTo( $this->reply_to->mail, $this->reply_to->name );
			}

			$mail->Subject = $this->subject;

			$mail->MsgHTML($this->message);
	
			#  $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!'; 
			// optional - MsgHTML will create an alternate automatically

			$res = $mail->send();
			return $res;
		} catch (phpmailerException $e) {
			return 'Mailer: '. $e->errorMessage(); //Pretty error messages from PHPMailer
		} catch (Exception $e) {
			return 'Mailer: '. $e->getMessage(); //Boring error messages from anything else!
		}
	error_log('mail.class.php: Failed to return or catch exception!');
	return true;
	}
}