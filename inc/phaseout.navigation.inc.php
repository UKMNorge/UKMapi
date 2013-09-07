<?php
/* 
Part of: UKM Norge core
Description: GUI-klasse for Œ generere splash-screens.
Author: UKM Norge / M Mandal 
Version: 2.0 
*/

class nav {
	var $gui = '';
	var $cells = array();
	
	public function nav($title='Velkommen!', $description='Denne siden kan sikkert hjelpe deg...', $size=120, $header='h4') {
		$this->gui = '<h2>'.$title.'</h2>'
			 . $description
			 . '<br /><br />'
			 . '<div id="ukmn_nav_container">'
			 . '#BOXES'
			 .'</div>';
		$this->head = $header;
		$this->size = $size;
	}
	public function run() {
		for($i=0; $i<sizeof($this->cells); $i++) {
			$this->gui = str_replace('#BOXES',
									$this->cells[$i] . '#BOXES',
									$this->gui);
		}
		return str_replace('#BOXES', '', $this->gui);
	}
	
	public function add($object) {
		$i = sizeof($this->cells);
		$this->cells[] = $object->run($i, $this->head, $this->size);
	}
}

class navCell {
	var $cell = '';
	var $links = array();
	var $icon = '';
	
	## CREATE A NEW MENU CELL
	public function navCell($name, $icon, $description='') {
		$this->cell = '<div id="ukmn_nav_box_#BOXNO" style="float:left; width:#DIVWIDTHpx; margin: 4px; display: block; -moz-border-radius:8px;-khtml-border-radius:8px;-webkit-border-radius:8px;border-radius:8px;border-width:1px;border-style:solid;padding:8px 12px 12px;border-color:#ccc;background:#efefef;">'
					. '<div align="center">'
					.  '#ICON'
					.  '</div>'
					.  '<#HEADER>'.$name.'</#HEADER>'
					.  '<br />'
					. '#LINKS'
					. '</div>';
		$this->icon = $icon;
	}
	
	### ADD LINKS
	public function link($link, $text, $target='_self') {
		$this->links[] = array('link'=>$link, 'text'=>$text, 'target'=>$target);
	}
	
	
	public function run($boxno, $header, $size) {
		## LOOP ALL LINKS AND ADD
		foreach($this->links as $i => $link) {
			$this->cell = str_replace('#LINKS',
									  '<div style="line-height: 22px;">'
									  .'<a href="'.$link['link'].'" target="'.$link['target'].'">'
									  .$link['text']
									  .'</a>'
									  .'</div>'
									  #.'<br />'
									  . '#LINKS',
									  $this->cell);	
		}

		## FIX MAIN IMAGE
		if(sizeof($this->links) == 1)
			$icon = '<a href="'.$link['link'].'" target="'.$link['target'].'">'.UKMN_ico($this->icon, $size).'</a>';
		else
			$icon = UKMN_ico($this->icon, $size);

		return str_replace(array('#BOXNO','#LINKS', '#HEADER', '#ICON', '#DIVWIDTH'), array($boxno, '', $header, $icon, ($size*1.35)), $this->cell);
	}
}
?>