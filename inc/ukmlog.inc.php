<?php
function UKMlog($table, $field, $post_key, $object_id = false) {
	global $current_user;
    get_currentuserinfo();   
    $user_id = $current_user->ID;

    $pl_id = get_option('pl_id');
	if (!$object_id)
		$object_id = $pl_id;
    
#    $ip = $_SERVER['REMOTE_ADDR'];

	$actionQ = new SQL("SELECT `log_action_id`
						FROM `log_actions` 
						WHERE `log_action_identifier` = '#identifier'",
						array('identifier'=>$table.'|'.$field));
	$action = $actionQ->run('field','log_action_id');
    
    if(empty($action))
    	die('Handling feilet, kontakt UKM Norge!'.var_dump($actionQ));
	    
	$object_code = substr($action, 0, (strlen($action)-2));
    
    $qry = new SQLins('log_log');
    $qry->add('log_u_id', $user_id);
    $qry->add('log_action', $action);
    $qry->add('log_object', $object_code);
    $qry->add('log_the_object_id', $object_id);
    $qry->add('log_pl_id', $pl_id);
	
	$res = $qry->run();
	$id = $qry->insid();
	
	if(strtolower($_POST[$post_key])==='false')
		$_POST[$post_key] = 0;
	elseif(strtolower($_POST[$post_key])==='true')
		$_POST[$post_key] = 1;
	
	UKMlog_value($id, $_POST[$post_key]);
#	echo UKMlog_read($id);
	
	return $res;
}

function UKMlog_value($row_id, $value) {
	$sql = new SQLins('log_value');
	$sql->add('log_id', $row_id);
	$sql->add('log_value', $value);
	$sql->run();
}

function UKMlog_read($row) {
	$qry = new SQL("SELECT * 
			FROM `log_log` AS `l`
			JOIN `log_actions` AS `a` ON (`l`.`log_action` = `a`.`log_action_id`)
			JOIN `log_objects` AS `o` ON (`o`.`log_object_id` = `l`.`log_object`)
			JOIN `log_value` AS `v` ON (`v`.`log_id` = `l`.`log_id`)
			WHERE `l`.`log_id` = '#logid'",
			array('logid'=>$row));
	$r = $qry->run('array');
	
	#if($r['log_action_datatype']=='bool') var_dump($r['log_value'].' => '.(bool)$r['log_value']);
	return $r['log_time'].' '
		.  ' '. UKMlog_user($r['log_u_id'])
		.  ' '. $r['log_action_verb']
		.  ($r['log_action_datatype']=='bool'&&(bool)$r['log_value']==0 ? ' ikke ' : '')
		.  ($r['log_action_printobject'] ? ' '. $r['log_object_name'] : '')
		.  ' '. $r['log_action_element']
		.  ($r['log_action_datatype']=='bool' ? '' : ' til ' . UKMlog_formatvalue($r['log_action_datatype'], $r['log_value']))
		. ' ('.$r['log_object_table_idcol'].'='.$r['log_the_object_id'].')'
		. '<br />'
		;
}

function UKMlog_formatvalue($type, $value) {
	switch($type) {
		case 'datetime':
			return date('d.m.Y H:i', $value);
		case 'date':
			return date('d.m.Y', $value);
		case 'sec':
			return ($value/60).' min';
	}
	return $value;
}

function UKMlog_user($id) {
	global $loaded_users;
	
	if(!is_array($loaded_users))
		$loaded_users = array();
		
	if(!in_array($id, $loaded_users)) {
		$user_info = get_userdata($id);
		$loaded_users[$id] = $user_info;
	}
	return $loaded_users[$id]->user_login;
}