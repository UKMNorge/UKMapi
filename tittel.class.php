<?php
class tittel {
	
	public function videresend($videresendFra, $videresendTil) {
		if((int)$videresendFra==0 || (int)$videresendTil==0)
			return false;
		$test_fylkestep = new SQL("SELECT * FROM `smartukm_fylkestep`
								  WHERE `pl_id` = '#plid'
								  AND `pl_from` = '#pl_from'
								  AND `b_id` = '#bid'
								  AND `t_id` = '#t_id'",
								  array('plid'=>$videresendTil, 
								  		'bid'=>$this->b_id,
										'pl_from'=>$videresendFra,
										't_id'=>$this->t_id));
		$test_fylkestep = $test_fylkestep->run();

		if (mysql_num_rows($test_fylkestep)==0) {
			$videresend_innslag = new SQLins('smartukm_fylkestep');
			$videresend_innslag->add('pl_id', $videresendTil);
			$videresend_innslag->add('pl_from', $videresendFra);
			$videresend_innslag->add('b_id', $this->b_id);
			$videresend_innslag->add('t_id', $this->t_id);
			$videresend_innslag->run();
		}
		return true;
	} 
	
	public function avmeld($videresendFra, $videresendTil) {
		$slett_relasjon = new SQLdel('smartukm_fylkestep',
			array('pl_id'=>$videresendTil,
				  'pl_from'=>$videresendFra,
				  'b_id'=>$this->b_id,
				  't_id'=>$this->t_id));
		$slett_relasjon->run();
		return true;
	}

	public function update($field, $post_key=false) {
		if(!$post_key)
			$post_key = $field;
		if($_POST[$post_key] == $_POST['log_current_value_'.$post_key])
			return true;
				
		$qry = new SQLins($this->form, array('t_id'=>$this->t_id));
		$qry->add($this->tableFields[$this->form][$field], $_POST[$post_key]);
//		echo $qry->debug();
		$qry->run();
	}
	
	public function create($b_id) {
		$qry = new SQLins($this->form);
		$qry->add('b_id', $b_id);
//		var_dump($qry->debug());
		$qry->run();
		$this->t_id = $qry->insid();
		
		$this->__construct($this->t_id, $this->form);
	}
	
	public function delete() {
		$qry = new SQLdel($this->form, array('t_id'=>$this->t_id));
		return $qry->run();
	}
	
	function __construct($t_id,$form) {
		$this->form = $form;
		
		if ($t_id === false)
			return;
		
		$this->tableFields['smartukm_titles_scene']['tittel'] = 't_name';
		$this->tableFields['smartukm_titles_scene']['tekst_av'] = 't_titleby';
		$this->tableFields['smartukm_titles_scene']['melodi_av'] = 't_musicby';
		$this->tableFields['smartukm_titles_scene']['koreografi'] = 't_coreography';
		$this->tableFields['smartukm_titles_scene']['varighet'] = 't_time';
		
		$this->tableFields['smartukm_titles_exhibition']['tittel'] = 't_e_title';
		$this->tableFields['smartukm_titles_exhibition']['type'] = 't_e_type';
		$this->tableFields['smartukm_titles_exhibition']['beskrivelse'] = 't_e_comments';
		$this->tableFields['smartukm_titles_exhibition']['teknikk'] = 't_e_technique';
		
		$this->tableFields['smartukm_titles_video']['tittel'] = 't_v_title';
		$this->tableFields['smartukm_titles_video']['varighet'] = 't_v_time';	
		$this->tableFields['smartukm_titles_video']['format'] = 't_v_format';		
		$this->tableFields['smartukm_titles_video']['beskrivelse'] = 't_v_comments';

		
		$this->t_id = $t_id;
		
		$qry = new SQL("SELECT * FROM `#form` WHERE `t_id` = '#bid'",
					array('form'=>$form,'bid'=>$t_id));
		$r = $qry->run('array');

		switch($form) {
			case 'smartukm_titles_scene':
				$this->_scene($r);
				break;
			case 'smartukm_titles_exhibition':
				$this->_utstilling($r);
				break;
			case 'smartukm_titles_video':
				$this->_film($r);
				break;
			case 'smartukm_titles_other':
				$this->_annet($r);
				break;
		}
		$this->b_id = $r['b_id'];
		if(!empty($this->varighet))
			$this->tid = $this->_secondtominutes($this->varighet);
		if($this->parentes == '()')
			$this->parentes = '';
		$this->_detaljer();
	}
	
	private function _detaljer(){
		$this->detaljer = substr($this->parentes, 1, strlen($this->parentes)-2);
	}
	
	public function g($key) {
		return utf8_encode($this->$key);
	}
	
	private function _scene($r) {
		$this->tittel = $r['t_name'];
		$this->tekst_av = $r['t_titleby'];
		if($this->tekst_av=='instrumental')
			$this->tekst_av = '';
		$this->melodi_av = $r['t_musicby'];
		$this->koreografi = $r['t_coreography'];
		$this->varighet = (int) $r['t_time'];
		
		$this->parentes = '(';
		if($this->melodi_av == $this->tekst_av && !empty($this->melodi_av))
			$this->parentes .= 'Tekst og melodi: '.$this->tekst_av.'';
		else{
			if(!empty($this->tekst_av))
				$this->parentes .= 'Tekst: '. $this->tekst_av;
			if(!empty($this->melodi_av))
				$this->parentes .= (!empty($this->tekst_av) ? ' - ':''). 'Melodi: '. $this->melodi_av;
		}
		$this->parentes .= ')';		
	}
	
	private function _utstilling($r) {
		$this->tittel = $r['t_e_title'];
		$this->type = $r['t_e_type'];
		$this->teknikk = $r['t_e_technique'];
		$this->format = $r['t_e_format'];
		$this->beskrivelse = $r['t_e_comments'];
		$this->varighet = 0;

		$this->parentes = '(';
			if(!empty($this->type))
				$this->parentes .= 'Type: '. $this->type;
			if(!empty($this->tekst_av))
				$this->parentes .= 'Teknikk: '. $this->teknikk;
		$this->parentes .= ')';
	}
	
	private function _film($r) {
		$this->tittel = $r['t_v_title'];
		$this->format = $r['t_v_format'];
		$this->varighet = (int) $r['t_v_time'];
		$this->parentes = '('.$r['t_v_format'].')';
	}
	
	private function _annet($r) {
		$this->tittel = $r['t_o_function'];
		$this->erfaring = $r['t_o_experience'];
		$this->kommentar = $r['t_o_comments'];
		$this->varighet = 0;
		$this->parentes = '('.$r['t_o_comments'].')';
	}

	private function _secondtominutes($sec) {
		$q = floor($sec / 60);
		$r = $sec % 60;
		
		if ($q == 0)
			return $r.'s';
			
		if ($r == 0)
			return $q.' min';
		
		return $q.'m '.$r.'s';
	}

}
?>