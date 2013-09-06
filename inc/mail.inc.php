<?php
require_once('PHPMailer/class.phpmailer.php');
require_once('UKMconfig.inc.php');

function sendUKMmail($recipient, $subject, $body) {
	$mail = new PHPMailer(true);
	$mail->IsSMTP();
	try {
		$mail->SMTPAuth   = true;                  // enable SMTP authentication
		$mail->SMTPSecure = "";                 // sets the prefix to the servier
		$mail->Port		  = 25;
#		$mail->Port		  = 587;
		$mail->Host       = UKM_MAIL_HOST;// SMTP server
		$mail->Username   = UKM_MAIL_USER; // username
		$mail->Password   = UKM_MAIL_PASS;      // password
		$mail->AddReplyTo('support@ukm.no', 'UKM Norge support');
		$mail->SetFrom('post@support.ukm.no', 'UKM Norge support');


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
?>