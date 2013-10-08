<?php

class kontakt {
	var $info = array();
		
		public function kontakt($id,$rel_id=false){/* legger en place inn i $info */
			$qry	= new SQL("SELECT `smartukm_contacts`.*,
									  `smartukm_kommune`.`name` AS `kommunenavn`,
									  `smartukm_contacts`.`adress` AS `address`
							   FROM `smartukm_contacts`
							   LEFT JOIN `smartukm_kommune` ON (`smartukm_kommune`.`id`=`smartukm_contacts`.`kommune`)
							   WHERE `smartukm_contacts`.`id` = '#id'",
							   array('id'=>$id));
			$this->info = $qry->run('array');
			$this->info['rel_id'] = $rel_id;
			$this->image();
		}
		
		public function set($key, $value){
			$this->info[$key] = $value;
		}
		
		private function image() {
			if(empty($this->info['picture']) || is_numeric($this->info['picture']))
				$this->info['image'] = 'http://grafikk.ukm.no/placeholder/person-300.png';
			else 
				$this->info['image'] = $this->info['picture'];
		}
		
		public function defaultImage() {
			return 'http://ico.ukm.no/placeholder_person_300.png';
				
		}
		
		public function get($key) {
			return utf8_encode($this->info[$key]);
		}
		
		public function g($key) {
			return $this->get($key);
		}
		
		public function mobil(){
			$phone = $this->g('tlf');
			if (strlen($phone) == 8 && (substr($phone, 0, 1) == 9 || substr($phone, 0, 1) == 4))
				return $phone;
			return false;
		}
	
		public function info(){
			return $this->info;
		}
		
		public function html() {
			$tittel = $this->g('title');
			$kommune = $this->g('kommunenavn');
			$face = str_replace(' ','',$this->get('facebook'));


			return '<img src="'.$this->g('image').'" width="120" style="float: left; margin-top: 2px; margin-right: 5px; border: 2px solid #1c4a45; margin-bottom: 2px;" />'
					.  '<strong>'.$this->g('name') . '</strong><br />'
					.  '<span style="font-size: 12px;">'
					.  (!empty($tittel) ? '<strong>'. $this->g('title') . '</strong><br />' : '')
					.  (!empty($kommune) ? ''. $this->g('kommunenavn') . '<br />' : '')
					.  UKMN_ico('mobile',16). '' . $this->g('tlf') . ' | '
					.  '<a href="mailto:'.$this->g('email').'">'.UKMN_ico('mail',16).'send en e-post</a>'
					.  '<br />'
					.  (!empty($face)&&strpos($face,'http')!==false ? 
							UKMN_ico('face',16).'<a href="'.$face.'" target="_blank">p&aring; facebook</a>'
							:'')
					.  '<div style="height: 10px; width: 10px;"></div>'
					;






			return '<img src="'.$this->g('image').'" width="186" style="float: left; margin-top: 2px; border: 2px solid #1c4a45; 	margin-bottom: 2px;" />'
					.  '<strong>'.$this->g('name') . '</strong><br />'
					.  '<span style="font-size: 12px;">'
					.  (!empty($tittel) ? '<strong>'. $this->g('title') . '</strong><br />' : '')
					.  (!empty($kommune) ? ''. $this->g('kommunenavn') . '<br />' : '')
					.  UKMN_ico('mobile',16). '' . $this->g('tlf') . ' | '
					.  '<a href="mailto:'.$this->g('email').'">'.UKMN_ico('mail',16).'send en e-post</a>'
					.  '<br />'
					.  (!empty($face)&&strpos($face,'http')!==false ? 
							UKMN_ico('face',16).'<a href="'.$face.'" target="_blank">p&aring; facebook</a>'
							:'')
					.  '<div style="height: 10px; width: 10px;"></div>'
					;
		}
}

?>
