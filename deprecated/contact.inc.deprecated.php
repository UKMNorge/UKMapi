<?php
die('KONTAKT UKM NORGE: FILEN M&Aring; BRUKE TOOLKIT, IKKE CONTACT!');
/*
function contact_sms($phone,$returnto='') {
	$returnName = urlencode($returnto);
	$returnUrl = urlencode($_SERVER['QUERY_STRING']);
	
	return '<input type="hidden" class="ukm_contact_sms" name="ukm_contact_sms[]" value="'.$phone.'" />'
	
		.'<a href="?page=UKMSMS_gui'
				   .'&UKMSMS_returnname='.$returnName
				   .'&UKMSMS_returnto='.$returnUrl
				   .'&UKMSMS_recipients='.$phone
				  .'">'.$phone.'</a>';
}
function contact_mail($mail,$nicename=false) {
	if(!$nicename)
		$nicename = $mail;
		
	return '<input type="hidden" class="ukm_contact_mail" name="ukm_contact_mail[]" value="'.$mail.'" />'
		. '<a href="mailto:'.$mail.'">'.$nicename.'</a>';
}
*/