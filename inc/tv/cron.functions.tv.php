<?php
global $looped_videos;
$looped_videos = array();

function tv_update($data) {
	global $looped_videos;
	if(is_array($data) && !in_array($data['file'], $looped_videos)) {
		$test = new SQL("SELECT `tv_id`
						 FROM `ukm_tv_files`
						 WHERE `tv_file` = '#file'",
						 array('file' => $data['file']));
#		echo $test->debug();
		$tv_id = $test->run('field', 'tv_id');
		
		if($tv_id && is_numeric($tv_id) )
			$ins = new SQLins('ukm_tv_files', array('tv_id' => $tv_id));
		else
			$ins = new SQLins('ukm_tv_files');
		
		tv_clean_description($data['description']);
		
		$ins->add('tv_title', str_replace("'", "\'", $data['title']));
		$ins->add('tv_file', $data['file']);
		$ins->add('tv_img', $data['img']);
		$ins->add('tv_category', $data['category']);
		$ins->add('tv_tags', $data['tags']);
		$ins->add('tv_description', $data['description']);
		$ins->add('b_id', $data['b_id']);	
		$res = $ins->run();
		
		if(!isset($tv_id)||!is_numeric($tv_id))
			$tv_id = $ins->insid();
	
		tv_category_update($data['category']);
		tv_person_update($data['tags'], $tv_id);
		$looped_videos[] = $data['file'];
	}
}

function tv_clean_description(&$desc) {
	if(strpos($desc, '(') == 0 && strrpos($desc, ')') == strlen($desc)-1)
		$desc = substr($desc, 1, strlen($desc)-2);
}

function tv_person_update($tags, $tv_id) {
	$tags = '|'.$tags.'|';
	$tags = explode('||', $tags);
		
	foreach($tags as $tag) {
		if(strpos($tag, 'p_') !== 0)
			continue;
			
			
		$p_id = str_replace('p_', '', $tag);
		$p = new person($p_id);
		
		$sqltest = new SQL("SELECT `tv_p_id` FROM `ukm_tv_persons`
							WHERE `tv_id` = '#tvid'
							AND `p_id` = '#pid'",
							array('tvid' => $tv_id,
								  'pid' => $p_id));
		$sqltest = $sqltest->run('field','tv_p_id');
		
		if(is_numeric($sqltest))
			$sql = new SQLins('ukm_tv_persons', array('tv_id' => $tv_id, 'p_id' => $p_id));
		else
			$sql = new SQLins('ukm_tv_persons');
		
		$sql->add('tv_id', $tv_id);
		$sql->add('p_id', $p_id);
		$sql->add('p_name', $p->g('name'));
		$sql->run();
	}
}

function tv_category_update($category) {
	$qry = new SQLins('ukm_tv_categories');
	$qry->add('c_name', $category);

	if(strpos($category, 'UKM-F') !== false)
		$qry->add('f_id', 6);
	elseif(strpos($category, 'Fylkesm') !== false)
		$qry->add('f_id', 3);
	elseif(strpos($category, 'URG') !== false) 
		$qry->add('f_id', 4);

	$res = $qry->run();
}


function video_calc_data($algorithm, $res) {
	switch($algorithm) {
		case 'standalone_video':
			$data['img'] = $res['video_image'];
			$data['file'] = $res['video_file'];
			$data['category'] = utf8_encode($res['video_category']);
			$data['title'] = utf8_encode($res['video_name']);
			$data['b_id'] = 0;
			$data['tags'] = video_calc_tag_standalone( $res );
			$data['description'] = $res['video_description'];
			return $data;
		case 'wp_related':
			$inn = new innslag($res['b_id']);
			$monstring = video_calc_monstring($res['b_id'], $res['pl_type'], $res['b_kommune'], $res['b_season']);
			$pl = $monstring['pl'];
			$kategori = $monstring['kategori'];
			$titler = $inn->titler($pl->get('pl_id'));
			if(!isset($titler[0]))
				continue;
			$tittel = $titler[0]->g('tittel');
			$post_meta = unserialize($res['post_meta']);
			
			if(empty($post_meta['file']))
				continue;
			
			$data['description']= $titler[0]->g('parentes');
			$data['img']		= video_calc_img($post_meta);
			$data['file']		= $post_meta['file'];
			$data['category']	= utf8_encode($kategori);
			$data['title']		= utf8_encode($inn->g('b_name')) .' - '. utf8_encode($titler[0]->g('tittel'));
			$data['b_id']		= $inn->g('b_id');
			$data['tags']		= video_calc_tag($inn, $res['pl_type'], $pl->g('pl_id'));
			#echo '<h2>'. utf8_decode($inn->g('b_name')).'</h2>';
			#echo '<pre>'; var_dump($data); echo '</pre>';
			return $data;
			
		case 'smartukm_tag':
			$inn = new innslag($res['b_id'],true);
			$b_id = $inn->g('b_id');
			if(empty($b_id))
				return false;
			
			$kommune = $inn->g('b_kommune');
			$season = $inn->g('b_season');
			if($kommune != 0 && $season != 0) {
				$kommuneQ = new SQL("SELECT `pl_id`
									 FROM `smartukm_rel_pl_k`
									 WHERE `k_id` = '#kid'
									 AND `season` = '#season'",
									 array('kid' => $kommune, 'season' => $season));
				$pl_id = $kommuneQ->run('field','pl_id');
			} else {
				$geo = new SQL("SELECT `smartukm_place`.`pl_id`,
										 `smartukm_rel_pl_k`.`k_id`,
										 `smartukm_place`.`season`
									 FROM `smartukm_rel_pl_b`
									 JOIN `smartukm_place` 
									 	ON (`smartukm_rel_pl_b`.`pl_id` = `smartukm_place`.`pl_id`)
									 JOIN `smartukm_rel_pl_k`
									 	ON (`smartukm_rel_pl_k`.`pl_id` = `smartukm_place`.`pl_id`)
									 WHERE `smartukm_rel_pl_b`.`b_id` = '#bid'
									 AND `smartukm_place`.`pl_fylke` = 0
									 AND `smartukm_place`.`pl_kommune` > 50",
									 array( 'bid' => $inn->g('b_id') ));
				$geo = $geo->run('array');
			}
			$type = false;
			
			$kommune = $geo['k_id'];
			
			$ss3u = strrpos($res['file'],'/')+1;
			$ss3u = substr($res['file'], $ss3u);
			
			$slash = strrpos($res['file'],'/');
			if(!$slash || $ss3u == 'public')
				return false;
				
			$season = str_replace('ukmno/videos/','', $res['file']);
			$season = substr($season, 0, 4);
			
			switch($season) {
				case '2009':
					switch($ss3u) {
						#### MÅ FINNES
						case 6:
							return false;


						case 13:
						case 16:
							$pl_id = $ss3u;
							$type = 'fylke';
							break;
						case 1:
						case 509:
							$pl_id = 394;
							$type = 'land';
							break;
					}
					if(!$type) {
						echo 'USER: '. $ss3u 
								   . ' SEASON: '. $season 
								   .' => <a href="http://video.ukm.no/'.$res['file'].'/'.$res['id'].'.flv">'.$res['id'].'</a>'
								   .'<br /><br />';
						die();
					}
					break;
				
				case '2010':
					switch($ss3u) {
						#### MÅ FINNES
						#case 6:
						#	return false;
						case 65:
							$pl_id = 907;
							$type = 'kommune';
							break;
						case 500:
							$pl_id = 521;
							$type = 'kommune';
							break;
						case 294:
							$pl_id = 558;
							$type = 'kommune';
							break;
						case 510:
							$pl_id = 804;
							$type = 'fylke';
							break;
						case 468:
							$pl_id = 811;
							$type = 'fylke';
							break;
						case 462:
							$pl_id = 429;
							$type = 'fylke';
							break;
						case 564:
							$pl_id = 416;
							$type = 'fylke';
							break;
						case 499:
							$pl_id = 425;
							$type = 'fylke';
							break;
						case 551:
							$pl_id = 421;
							$type = 'fylke';
							break;
						case 508:
							$pl_id = 419;
							$type = 'fylke';
							break;
						case 1:
						case 496:
							$pl_id = 758;
							$type = 'land';
							#echo 'landsmønstring 2010<br />';
							break;
					}
					if(!$type) {
						echo 'USER: '. $ss3u 
								   . ' SEASON: '. $season 
								   .' => <a href="http://video.ukm.no/'.$res['file'].'/'.$res['id'].'.flv">'.$res['id'].'</a>'
								   .'<br /><br />';
						die();
					}
					break;					
				case '2011':
					switch($ss3u) {
						#### MÅ FINNES
						#case 6:
						#	return false;
						case 294:
						case 510:
							$pl_id = 804;
							$type = 'fylke';
							break;
						case 551:
							$pl_id = 807;
							$type = 'fylke';
							break;
						case 471:
							$pl_id = 809;
							$type = 'fylke';
							break;
						case 518:
							$pl_id = 1171;
							$type = 'fylke';
							break;
						case 468:
							$pl_id = 811;
							$type = 'fylke';
							break;
						case 456:
							$pl_id = 808;
							$type = 'fylke';
							break;
						case 520:
						case 564:
							$pl_id = 802;
							$type = 'fylke';
							break;
						case 508:
						case 1:
							$pl_id = 1131;
							$type = 'land';
							break;
					}
					if(!$type) {
						echo 'USER: '. $ss3u 
								   . ' SEASON: '. $season 
								   .' => <a href="http://video.ukm.no/'.$res['file'].'/'.$res['id'].'.flv">'.$res['id'].'</a>'
								   .'<br /><br />';
						die();
					}
					break;
				default:
					echo 'MANGLER SESONG USER: '. $ss3u 
								   . ' SEASON: '. $season 
								   .' => <a href="http://video.ukm.no/'.$res['file'].'/'.$res['id'].'.flv">'.$res['id'].'</a>'
								   .'<br /><br />';
						die();
			}
			
			if(!$type) {
				$season = $geo['season'];
				$pl = new monstring($geo['pl_id']);
				$type = $pl->g('type');
			} else {
				$pl = new monstring($pl_id);
			}
			
			$monstring = video_calc_monstring($inn->g('b_id'), $type, $kommune, $season);

			$kategori = $monstring['kategori'];
			$titler = $inn->titler($pl->get('pl_id'),false,true);
			if(!isset($titler[0])) {
				echo '<h3>'. $inn->g('b_name') .' ('.$inn->g('b_id').')</h3>'
					.$pl->g('pl_name') .' ('.$pl->g('type').')'
					.'<br />';
				continue;	
			}
			$tittel = $titler[0]->g('tittel');
			
			$fylkeid = new SQL("SELECT `idfylke` FROM `smartukm_kommune`
								WHERE `id` = '#kommune'",
								array('kommune' => $geo['k_id']));
			$fylkeid = $fylkeid->run('field', 'idfylke');

			$data['description']= $titler[0]->g('parentes');
			$data['img']		= $res['file'].'/'.$res['id'].'.jpg';
			$data['file']		= $res['file'].'/'.$res['id'].'.flv';
			$data['category']	= utf8_encode($kategori);
			$data['title']		= utf8_encode($inn->g('b_name')) .' - '. utf8_encode($titler[0]->g('tittel'));
			$data['b_id']		= $inn->g('b_id');
			#$data['tags']		= video_calc_tag($inn, $pl->g('type'), $pl->g('pl_id'));
			$data['tags']		= video_calc_tag_smartukm_tag($inn, $pl->g('type'), $pl->g('pl_id'), $geo['k_id'], $fylkeid, $geo['season']);

			#echo '<h2>'. utf8_decode($inn->g('b_name')).'</h2>';
			#echo '<pre>'; var_dump($data); echo '</pre>';
			return $data;
	}
}

function video_calc_img($post_meta) {
	if(empty($post_meta['img'])) {
		$video = $post_meta['file'];
		$ext = strrpos($video, '.');
		$img = substr($video, 0, $ext).'.jpg';
		$test = img_exists('http://video.ukm.no/'.$img);
		if(!$test)
			return $video.'.jpg';
		
		return $img;
	}
	return $post_meta['img'];
}

function img_exists($url, $timeout=5) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	
	curl_setopt($ch, CURLOPT_REFERER, $_SERVER['PHP_SELF']);
	curl_setopt($ch, CURLOPT_USERAGENT, "UKMNorge API");
	
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	
	$output = curl_exec($ch);
	$hd_curl_info = curl_getinfo($ch);

	curl_close($ch);
	return $hd_curl_info['content_type'] == 'image/jpeg';
}

function video_calc_monstring($b_id, $pl_type, $kommune, $season) {
	switch($pl_type) {
		case 'fylke': 
			$fylke = new SQL("SELECT `fylke`.`id`, `fylke`.`name`
							  FROM `smartukm_kommune` AS `kommune`
							  JOIN `smartukm_fylke` AS `fylke` ON (`kommune`.`idfylke` = `fylke`.`id`)
							  WHERE `kommune`.`id` = '#kommune'
							  ",
							  array('kommune' => $kommune));
			$fylke = $fylke->run('array');
			$fm = new fylke_monstring($fylke['id'], $season);
			return array('pl' => $fm->monstring_get(),
						 'kategori' => ('Fylkesmønstringen i '). utf8_encode($fylke['name']).' '.$season);
		case 'land':
			$land = new landsmonstring($season);
			return array('pl' => $land->monstring_get(),
						 'kategori' => 'UKM-Festivalen '.$season);
		default:
			$kommune = new SQL("SELECT `kommune`.`name`
							  FROM `smartukm_kommune` AS `kommune`
							  WHERE `kommune`.`id` = '#kommune'
							  ",
							  array('kommune' => $kommune));
#			echo $kommune->debug();
			$kommune = $kommune->run('array');
			$monstring = new kommune_monstring($kommune, $season);
			return array('pl' => $monstring->monstring_get(),
						 'kategori' => utf8_encode($kommune['name']).' '.$season);
	}
}
function video_calc_tag_standalone($res) {
	$place = new monstring($res['pl_id']);
	$type = $place->g('type');
	$tags = '|pl_'.$res['pl_id'].'|'
		   .'|t_'.$place->g('type').'|'
		   .'|s_'.$place->g('season').'|';
		   
	if($type == 'kommune') {
		$kommuner = $place->g('kommuner');
		foreach($kommuner as $k_info) {
			$tags .= '|k_'.$k_info['id'].'|';
		}
	}
	$tags .= '|f_'.$place->g('fylke_id').'|';
	
	return $tags;
}

function video_calc_tag($inn, $pl_type, $pl_id) {
	$inn->loadGeo();
	$personer = $inn->personer();
	
	$tags .= '|b_'.$inn->g('b_id').'|'
			.'|k_'.$inn->g('kommuneID').'|'
			.'|f_'.$inn->g('fylkeID').'|'
			.'|s_'.$inn->g('b_season').'|'
			.'|t_'.$pl_type.'|'
			.'|pl_'.$pl_id.'|';
	foreach($personer as $p) 
		$tags .= '|p_'.$p['p_id'].'|';
		
	return $tags;
}
function video_calc_tag_smartukm_tag($inn, $pl_type, $pl_id, $kommune, $fylke, $season) {
	$inn->loadGeo();
	$personer = $inn->personer();
	
	$tags .= '|b_'.$inn->g('b_id').'|'
			.'|k_'.$kommune.'|'
			.'|f_'.$fylke.'|'
			.'|s_'.$season.'|'
			.'|t_'.$pl_type.'|'
			.'|pl_'.$pl_id.'|';
	foreach($personer as $p) 
		$tags .= '|p_'.$p['p_id'].'|';
		
	return $tags;
}
?>