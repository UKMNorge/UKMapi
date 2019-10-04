<?php
       ## SCENE
        case 1:
              ## CHECK TITLES
			if($band['b_kategori'] == "Dans"||$band['b_kategori'] == 'dans'||$band['b_kategori']=='dance')
	            $test_6 = titles($band, array('t_name','t_coreography','t_time'), 'danser');			
			elseif($band['b_kategori'] == "litteratur"||$band['b_kategori'] == 'litterature')
	            $test_6 = titles($band, array('t_name','t_time'), 'titler');
			elseif($band['b_kategori'] == "teater"||$band['b_kategori'] == 'theatre')
	            $test_6 = titles($band, array('t_name','t_titleby','t_time'), 'stykker');
			elseif(strpos($band['b_kategori'],'annet') !== false)
				$test_6 = titles($band, array('t_name', 't_time'), 'titler');
			else 
	            $test_6 = titles($band, array('t_name','t_musicby','t_time'));
		## VIDEO
    	case 2: 
 			$test_6 = titles($band, array('t_v_title','t_v_format','t_v_time'));
		## EXHIBITION
    	case 3: 
             ## CHECK TITLES
            $test_6 = titles($band, array('t_e_title','t_e_type','t_e_technique'));
		## MATKULTUR
    	case 6: 
             ## CHECK TITLES
            $test_6 = titles($band, array('t_o_function','t_o_comments'));
		## OTHER ON SCENE
		case 7:
            ## CHECK TITLES
            $test_6 = titles($band, array('t_o_function','t_o_experience'));

###########################################################
########     TITLES							 ##############
###########################################################
function titles($b, $fields, $tittelnavn=false) {
	
	# FETCH ALL FIELDS
	$qry = new SQL("SELECT * FROM `#table` WHERE `b_id` = '#b_id'", 
					   array('table'=>$b['bt_form'], 'b_id'=>$b['the_real_b_id']));
	$res = $qry->run();

	# FIND TITLE KEY
	switch($b['bt_id']) {
		case 1:
			$titleKey = 't_name';
			if(!$tittelnavn)
			$tittelnavn = 'l&aring;ter';
			break;
		case 2:
			$titleKey = 't_v_title';
			if(!$tittelnavn)
			$tittelnavn = 'filmer';
			break;
		case 3:
			$titleKey = 't_e_title';
			if(!$tittelnavn)
			$tittelnavn = 'kunstverk';
			break;
		default:
			if(!$tittelnavn)
			$titleKey = 't_o_function';
			break;
	}

	if(!$tittelnavn)
		$tittelnavn = 'titler';
	
	$header = '<strong>'.ucfirst($tittelnavn).':</strong><br />';

	## IF NO TITLES, RETURN
	if(SQL::numRows($res)==0)
		return $header . ' Det er ikke lagt til noen '.$tittelnavn;

	$missing = '';
	
	## LOOP ALL TITLES
	while($title = SQL::fetch($res)) {
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
