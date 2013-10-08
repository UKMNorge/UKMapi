<?php
function validateBand($bid) {
	global $SEASON;

    $band = new SQL("SELECT *, `smartukm_band`.`b_id` AS `the_real_b_id` FROM `smartukm_band` 
                         JOIN `smartukm_participant` ON (`smartukm_participant`.`p_id` = `smartukm_band`.`b_contact`)
                         LEFT JOIN `smartukm_band_type` ON (`smartukm_band_type`.`bt_id` = `smartukm_band`.`bt_id`)
                         LEFT JOIN `smartukm_technical` ON (`smartukm_technical`.`b_id` = `smartukm_band`.`b_id`)
                         WHERE `smartukm_band`.`b_id` = '#b_id'",
                                array('b_id'=>$bid));

    $band = $band->run('array');
    
    switch($band['bt_id']) {
        ## SCENE
        case 1:
            ## CHECK NAME AND SJANGER
            $test_2 = nameAndSjanger($band);       
            ## CHECK DESCRIPTION
            $test_3 = description($band);
            ## CHECK CONTACT PERSON
            $test_4 = contact_person($band);
            ## CHECK PARTICIPANTS
            $test_5 = participants($band);
            ## CHECK TITLES
			if($band['b_kategori'] == "Dans"||$band['b_kategori'] == 'dans'||$band['b_kategori']=='dance')
	            $test_6 = titles($band, array('t_name','t_coreography','t_time'));			
			elseif($band['b_kategori'] == "litteratur"||$band['b_kategori'] == 'litterature')
	            $test_6 = titles($band, array('t_name','t_time'));
			elseif($band['b_kategori'] == "teater"||$band['b_kategori'] == 'theatre')
	            $test_6 = titles($band, array('t_name','t_titleby','t_time'));
			elseif(strpos($band['b_kategori'],'annet') !== false)
				$test_6 = titles($band, array('t_name', 't_time'));
			else 
	            $test_6 = titles($band, array('t_name','t_musicby','t_time'));
			## CHECK TECHNICAL DEMANDS
			$test_1 = technical($band);
            break;
		## VIDEO
    	case 2: 
            ## CHECK NAME AND SJANGER
            $test_2 = nameAndSjanger($band);       
            ## CHECK DESCRIPTION
            $test_3 = description($band);
            ## CHECK CONTACT PERSON
            $test_4 = contact_person($band);
            ## CHECK PARTICIPANTS
            $test_5 = participants($band);
            ## CHECK TITLES
			$test_6 = titles($band, array('t_v_title','t_v_format','t_v_time'));
			## CHECK TECNICAL
			$test_1 = true;
			break;
		## EXHIBITION
    	case 3: 
            ## CHECK NAME AND SJANGER
            $test_2 = name($band);       
            ## CHECK DESCRIPTION
            $test_3 = description($band);
            ## CHECK CONTACT PERSON
            $test_4 = contact_person($band);
            ## CHECK PARTICIPANTS
            $test_5 = participants($band);
            ## CHECK TITLES
            $test_6 = titles($band, array('t_e_title','t_e_type','t_e_technique'));
			## CHECK TECHNICAL DEMANDS
			$test_1 = true;
			break;
		## MATKULTUR
    	case 6: 
            ## CHECK NAME
            $test_2 = name($band);       
            ## CHECK DESCRIPTION
            $test_3 = description($band);
            ## CHECK CONTACT PERSON
            $test_4 = contact_person($band);
            ## CHECK PARTICIPANTS
            $test_5 = participants($band);
            ## CHECK TITLES
            $test_6 = titles($band, array('t_o_function','t_o_comments'));
			## CHECK TECHNICAL DEMANDS
			$test_1 = true;
			break;
		## OTHER ON SCENE
		case 7:
		    ## CHECK NAME AND SJANGER
            $test_2 = name($band);       
            ## CHECK DESCRIPTION
            $test_3 = description($band);
            ## CHECK CONTACT PERSON
            $test_4 = contact_person($band);
            ## CHECK PARTICIPANTS
            $test_5 = participants($band);
            ## CHECK TITLES
            $test_6 = titles($band, array('t_o_function','t_o_experience'));
			## CHECK TECHNICAL DEMANDS
			$test_1 = technical($band);
			break;
    }
    
	if($band['bt_id'] == (1 || 2 || 3 || 6 || 7)) {
		$textFeedback = '';
		$status = 1;
		for($i=1; $i<8; $i++) {
			$check = 'test_'.$i;
			if(!isset($$check)) {
				$status++;
				continue;
			}
			if($$check !== true) $textFeedback .= str_replace('<br />', "\r\n", $$check) . "\r\n";
			else $status++;
		}
	} else {
		$status = 8;
	}
	
	## CHECK THE VALIDATION OF THE BAND
	$updated = new SQL("UPDATE `smartukm_band` 
					   SET `b_status` = '#status',
					   `b_status_text` = '#text'
					   WHERE `b_id` = '#b_id'",
					   array('status'=>($status>$band['b_status']?$status:$band['b_status']),
							 'text'=>$textFeedback,
							 'b_id'=>$bid
							 )
					   );
	$updated = $updated->run();
	if($status == 8 && (int)$band['b_status'] < 8) {
		if(function_exists('logIt')) { 
			logIt($bid, 22, (int)$band['b_status']);
		}
	}
	else {
		if(function_exists('logIt')) { 
			logIt($bid, 21, (int)$band['b_status'] .' => '.(int)$status);
		}
	}


	return array($status, (empty($textFeedback) ? 'Alt er OK!' : '<h2>F&oslash;lgende obligatoriske felt er ikke utfylt:</h2>'.$textFeedback)); 
}


###########################################################
########     TITLES							 ##############
###########################################################
function titles($b, $fields) {
	$header = '<strong>Titler:</strong><br />';
	
	# FETCH ALL FIELDS
	$qry = new SQL("SELECT * FROM `#table` WHERE `b_id` = '#b_id'", 
					   array('table'=>$b['bt_form'], 'b_id'=>$b['the_real_b_id']));
	$res = $qry->run();
	## IF NO TITLES, RETURN
	if(mysql_num_rows($res)==0)
		return $header . ' Det er ikke lagt til noen titler';

	$missing = '';
	# FIND TITLE KEY
	switch($b['bt_id']) {
		case 1:		$titleKey = 't_name';		break;
		case 2:		$titleKey = 't_v_title';	break;
		case 3: 	$titleKey = 't_e_title';	break;
		default:	$titleKey = 't_o_function';	break;
	}
	
	## LOOP ALL TITLES
	while($title = mysql_fetch_assoc($res)) {
		for($i=0; $i<sizeof($fields); $i++) {
			if(empty($title[$fields[$i]])) {
				## IF DANCE AND NOT MANDATORY FIELD
				if($b['b_kategori']=='dans' && in_array($fields[$i],array('t_musicby','t_titleby')))
					continue;
				## IF THEATRE AND NOT MANDATORY FIELD
				elseif($b['b_kategori']=='teater' && in_array($fields[$i],array('t_musicby','t_titleby','t_coreography')))
					continue;
				## IF THEATRE AND NOT MANDATORY FIELD
				elseif($b['b_kategori']=='annet' && in_array($fields[$i],array('t_musicby','t_titleby','t_coreography')))
					continue;
	
				$missing .= '<br /><strong> - '. $title[$titleKey] . '</strong><br /> &nbsp;- Ikke alle felter er fylt ut '.$fields[$i];
				break;
			}
		}
	}
	## IF NOTHING WRONG, RETURN TRUE
	if(empty($missing)) return true;
	
	return $header . $missing;
}	

###########################################################
########     LOOP AND CHECK PARTICIPANTS	 ##############
###########################################################
function participants($band) {
	$header = '<strong>Deltakere:</strong><br />';
	global $SEASON;
	$whatwrong = '';
	$participants = new SQL("SELECT * FROM `smartukm_participant`
    							JOIN `smartukm_rel_b_p` ON (`smartukm_rel_b_p`.`p_id` = `smartukm_participant`.`p_id`)
                                WHERE `smartukm_rel_b_p`.`b_id` = '#bid'
								GROUP BY `smartukm_participant`.`p_id`", 
                                array('bid'=>$band['the_real_b_id'], 'season'=>$SEASON));
    $participants = $participants->run();
	## IF NO PARTICIPANTS
	if(mysql_num_rows($participants)==0)
		return $header. ' Det er ingen deltakere i innslaget';

	## LOOP FOR PARTICIPANTS
	while($p = mysql_fetch_assoc($participants)) {
    	$test = participant($p);
        if($test !== true) $whatwrong .= $test;
	}
    
    if(empty($whatwrong)) return true;
	
    return $header.$whatwrong;
}

###########################################################
########     CHECK ONE PARTICIPANT			 ##############
###########################################################
function participant($p) {
	$whatmissing = '';
	if(empty($p['p_firstname']) && strlen($p['p_firstname']) < 3)
    							 							$whatmissing .= ' &nbsp;- Fornavn mangler<br />';
	if(empty($p['p_lastname']) && strlen($p['p_lastname']) < 3)
    							 							$whatmissing .= ' &nbsp;- Etternavn mangler<br />';
#	if(empty($p['p_email']))	
 #   														$whatmissing .= ' &nbsp;- E-postadresse mangler<br />';
    if(empty($p['p_phone']) || strlen($p['p_phone'])!==8)
    														$whatmissing .= ' &nbsp;- Telefonnummer best&aring;r ikke av 8 siffer<br />';
    if(empty($p['instrument']))
    														$whatmissing .= ' &nbsp;- Funksjon/rolle mangler<br />';
  	
    if(empty($whatmissing)) return true;
    
    return '- <strong> '. $p['p_firstname'] .' '. $p['p_lastname'] . ':</strong><br />'.$whatmissing;
}

###########################################################
########     CHECK NAME OF BAND				 ##############
###########################################################
function name($band) {
  	if(empty($band['b_name'])) 								return '- Innslaget m&aring; ha et navn';
	
    return true;
}

###########################################################
########     CHECK NAME AND SJANGER FOR BAND ##############
###########################################################
function nameAndSjanger($band) {
   	if(empty($band['b_name']) && empty($band['b_sjanger']))	return '- Innslaget m&aring; ha et navn<br />- Sjanger m&aring; v&aelig;re oppgitt';
    elseif(empty($band['b_name'])) 							return '- Innslaget m&aring; ha et navn';
    elseif(empty($band['b_sjanger']))						return '- Sjanger m&aring; v&aelig;re oppgitt';				
	
    return true;
}

###########################################################
########     CHECK DESCRIPTION				 ##############
###########################################################
function description($band) {
	if(empty($band['td_konferansier']))						return '- Beskrivelse mangler';
    elseif(strlen($band['td_konferansier']) < 20)			return '- Beskrivelsen m&aring; v&aelig;re mer enn 20 tegn lang';
   	elseif($band['td_konferansier'] == 'Skriv en beskrivelse lengre enn 20 tegn her..') return '- Beskrivelse mangler';
    return true;
}

###########################################################
########     CHECK TECHNICAL DEMANDS		 ##############
###########################################################
function technical($band) {
	global $lang;
	if(empty($band['td_demand']))							return '- Tekniske behov mangler';
    elseif(strlen($band['td_demand']) < 5)					return '- Tekniske behov m&aring; v&aelig;re mer enn 5 tegn lang';
	
    return true;
}

###########################################################
########    CHECK CONTACT PERSON			 ##############
###########################################################
function contact_person($b) {
	$whatmissing = '';
	if(empty($b['p_firstname']) && strlen($b['p_firstname']) < 3)
    							 							$whatmissing .= ' - Fornavn mangler<br />';
	if(empty($b['p_lastname']) && strlen($b['p_lastname']) < 3)
    							 							$whatmissing .= ' - Etternavn mangler<br />';
	if(empty($b['p_email']) || !validEmail($b['p_email']))	
    														$whatmissing .= ' - E-post mangler eller er ikke en godkjent e-postadresse<br />';
    if(empty($b['p_phone']) || strlen($b['p_phone'])!==8)
    														$whatmissing .= ' - Telefonnummer best&aring;r ikke av 8 siffer<br />';
	if($b['p_phone'] == '12345678' 
	|| $b['p_phone'] == '00000000' 
	|| $b['p_phone'] == '11111111' 
	|| $b['p_phone'] == '22222222' 
	|| $b['p_phone'] == '33333333' 
	|| $b['p_phone'] == '44444444' 
	|| $b['p_phone'] == '55555555' 
	|| $b['p_phone'] == '66666666' 
	|| $b['p_phone'] == '77777777' 
	|| $b['p_phone'] == '88888888' 
	|| $b['p_phone'] == '99999999' 
	|| $b['p_phone'] == '12341234' 
	|| $b['p_phone'] == '87654321' 
	|| $b['p_phone'] == '23456789' 
	|| $b['p_phone'] == '98765432')
    														$whatmissing .= ' - Telefonnummer er ikke gyldig<br />';

    if(empty($b['p_adress']) || strlen($b['p_adress']) < 3)
    														$whatmissing .= ' - Adresse mangler';
    if( empty($b['p_postnumber']) || (strlen($b['p_postnumber']) !==4 || strlen($b['p_postnumber']) === 3 && $b['p_postnumber'] < 200))
    														$whatmissing .= ' - Postnummeret best&aring;r ikke av 4 sifre<br />';
  	
    if(empty($whatmissing)) return true;
    
    return '<br /><strong>Kontaktperson:</strong><br />' . $whatmissing .'Dette redigerer du fra Din side';
}

###########################################################
########     EMAIL-VALIDATIONS				 ##############
###########################################################
function validEmail($email) {
   $isValid = true;
   $atIndex = strrpos($email, "@");
   if (is_bool($atIndex) && !$atIndex)
   {
      $isValid = false;
   }
   else
   {
      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64)
      {
         // local part length exceeded
         $isValid = false;
      }
      else if ($domainLen < 1 || $domainLen > 255)
      {
         // domain part length exceeded
         $isValid = false;
      }
      else if ($local[0] == '.' || $local[$localLen-1] == '.')
      {
         // local part starts or ends with '.'
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $local))
      {
         // local part has two consecutive dots
         $isValid = false;
      }
      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
      {
         // character not valid in domain part
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $domain))
      {
         // domain part has two consecutive dots
         $isValid = false;
      }
      else if
(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                 str_replace("\\\\","",$local)))
      {
         // character not valid in local part unless 
         // local part is quoted
         if (!preg_match('/^"(\\\\"|[^"])+"$/',
             str_replace("\\\\","",$local)))
         {
            $isValid = false;
         }
      }
      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
      {
         // domain not found in DNS
         $isValid = false;
      }
   }
   return $isValid;
}

?>
