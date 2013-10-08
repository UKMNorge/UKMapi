<?php
/* 
Part of: UKM Norge core
Description: Form-klasse for standardiserte skjemaer.
Author: UKM Norge / M Mandal 
Version: 2.2
*/

class form {
	var $form;
	var $hidden;
	var $stylecorrection = 'style="margin-top: 2px; margin-bottom: 0px;"';
	var $directReturn = false;
	
	function directReturn() {
		$this->directReturn = true;
	}
	
	function epost($label, $fieldname, $value='', $help='') {
		$log = $this->logfield($fieldname, $value);
		$this->input($label, $fieldname, $value, $help);
	}

	function telefon($label, $fieldname, $value='', $help='') {
		$log = $this->logfield($fieldname, $value);
		$this->input($label, $fieldname, $value, $help);
	}
	
	public function bilde($label, $fieldname, $value, $help, $thisisDefault='') {
		$log = $this->logfield($fieldname, $value);
		$img = '<a href="#" class="upload_image_button">'
			.  '<img src="'.$value.'" style="border: 2px solid #505050;" class="upload_image_button" width="175" id="upload_image_img" />'
			.  '</a>';
		
		$changeAction = "javascript:uploadIMG();";
		
		if($value==$thisisDefault)
			$deleteAction = "javascript:alert('Beklager, dette er standardbildet som vil vises om du ikke velger et annet bilde');";
		else
			$deleteAction = "javascript:reSetImage();";
		
		$return = '<div class="form-field"'.$this->stylecorrection.'>'
					.  '<label for="'.$fieldname.'">'.$label.'</label>'
					.  '<div style="width: 179px;" align="right">'  
					.  $img
					.  '<br />'
					.  '<p style="margin-bottom: 0px;">'.$help.'</p>'
					.  '<a href="'.$deleteAction.'" style="float: right; margin-left: 10px; margin-top: 4px;">'.UKMN_icoButton('trash', 20,'slett bilde',10).'</a>'
					.  '<a href="#" class="upload_image_button" style="float: right; margin-top: 4px;">'
						.'<div class="upload_image_button">'
						.UKMN_icoButton('pencil', 20,'bytt bilde',10)
						.'</div>'
					.  '</a>'
					.	'<input id="upload_image" type="hidden" name="upload_image" value="'.$value.'" />'
					.   '<input id="upload_image_default" type="hidden" name="upload_image_default" value="'.$thisisDefault.'"  />'
					.  '</div>'
					.  '</div>';
					
		if($this->directReturn)
			return $return.$log;
		$this->form .= $return;
	
	}
	
	## ADDS A YES/NO-SELECTOR
	public function janei($label, $fieldname, $selected=true, $disabled=false, $onclickYes='', $onclickNo='') {
		$log = $this->logfield($fieldname, (string)$selected);

		// Should yes-button be checked?
		$yes = (
				(is_bool($selected)&&$selected) ||
				(is_int($selected)&&$selected===1) ||
				(is_string($selected)&&$selected==='true'))
				? true
				: false;
		
		// If checking is no real option
		$disabled = ($disabled) ? ' disabled="disabled"' : '';
		
		// Onclick
		$onclickYes = empty($onclickYes)
					 ? ''
					 : ' onclick="'.$onclickYes.'" ';
					 
		$onclickNo = empty($onclickNo)
					 ? ''
					 : ' onclick="'.$onclickNo.'" ';
					 
		// Yes-button			 		
		$true = '<label style="float:right; width:40px; margin-left: 10px;">'
				.'<input type="radio" value="true" '.($yes ? 'checked="checked"':'').' name="'.$fieldname.'" '.$onclickNo.' />'
				.__('Yes')
				.'</label>';
		// No-button
		$false ='<label style="float:right; width: 40px;">'
				.'<input type="radio" value="false" '.(!$yes ? 'checked="checked"':'').' name="'.$fieldname.'" '.$onclickNo.' />'
				.__('No')
				.'</label>'; 
		
		$return = '<div class="form-field"'.$this->stylecorrection.'>'
					.  $false
					.  $true
					.  '<label for="'.$fieldname.'" style="padding-right: 10px;">'.$label.'</label>'
					. '</div>';
		if($this->directReturn)
			return $return.$log;
		$this->form .= $return;
		
	}


	
	## RETURNS AN DEFAULT INPUT
	function input($label, $fieldname, $value='', $help='') {
		$log = $this->logfield($fieldname, $value);

		$return = '<div class="form-field form-required" '.$this->stylecorrection.'>'
					.  '<label for="'.$fieldname.'">'.$label.'</label>'
					.  '<input name="'.$fieldname.'" id="'.$fieldname.'" type="text" value="'.$value.'" size="40" aria-required="true" />'
					.  (!empty($help) ? '<p style="margin-bottom: 0px;">'.$help.'</p>' : '')
					.  '</div>';
		if($this->directReturn)
			return $return.$log;
		$this->form .= $return;
	}
	
	function postnummer($label, $fieldname, $value='', $help='') {
		$log = $this->logfield($fieldname, $value);
		$return = '<div class="form-field form-required" '.$this->stylecorrection.'>'
					.  '<label for="'.$fieldname.'">'.$label.'</label>'
					.  '<input name="'.$fieldname.'_nr" id="'.$fieldname.'_nr" type="text" style="width: 40px;" value="'.$value.'" size="4" />'
					.  ' '
					.  'Sted fylles ut automatisk etter du har lagret'
					.  (!empty($help) ? '<p style="margin-bottom: 0px;">'.$help.'</p>' : '')
					.  '</div>';
		if($this->directReturn)
			return $return.$log;
		$this->form .= $return;
	}	
	
	## RETURNS A ZIPCODE POSTOFFICE INPUT
	## !! ## !! ## !! ## !! ## !! ## 
	## SKAL IKKE BRUKES !!!!!!!
	## !! ## !! ## !! ## !! ## !! ##
	function postInput($label, $fieldname = array('postalcode' => 'postalcode','poststed' => 'poststed'),$value = array('postalcode' => '','poststed' => '')) {
		$log = $this->logfield($fieldname, $value);
		$return = '<div class="form-field form-required" '.$this->stylecorrection.'>'
					.  '<label for="'.$fieldname.'">'.$label.'</label>'
					.  '<input name="'.$fieldname['postalcode'].'" id="'.$fieldname['postalcode'].'" value="'.$value['postalcode'].'" type="text" size="6" style="width: 25%;margin-right: 3%;" aria-required="true" />'
					.  '<input name="'.$fieldname['poststed'].'" id="'.$fieldname['poststed'].'" value="'.$value['poststed'].'" type="text" size="30" style="width: 67%;margin-left: 0;" aria-required="true" />'
					.  '</div>';
		if($this->directReturn)
			return $return.$log;
		$this->form .= $return;
	}
	
	## RETURNS AN DISABLED INPUT - JUST FLASH THE VALUE FOR THE USER
	function noe($label, $value, $help='') {
		$return = '<div class="form-field form-required" '.$this->stylecorrection.'>'
					.  '<label for="empty">'.$label.'</label>'
					.  '<input name="empty" id="empty" type="text" value="'.$value.'" size="40" disabled="disabled" />'
					.  (!empty($help) ? '<p style="margin-bottom: 0px;">'.$help.'</p>' : '')
					.  '</div>';
		if($this->directReturn)
			return $return.$log;
		$this->form .= $return;
	}
	
	## INSERT JUST AN EMPTY LINE FOR THE SPACING
	function skillelinje($label) {
		$this->form .= '<p>'.$label.'</p>';	
	}
	
	## GIVES AN DEFAULT SELECT
	function select($label, $fieldname, $selects, $value='', $help='') {
		$log = $this->logfield($fieldname, $value);
		if(is_array($selects)) {
			$textSelect = '';
			foreach($selects as $key => $val)
				if($value == $key)
					$textSelect .= '<option value="'.$key.'" selected="selected">'.$val.'</option>';
				else	
					$textSelect .= '<option value="'.$key.'">'.$val.'</option>';
		} else
			$textSelect = $selects;
				
		$return = '<div class="form-field form-required" '.$this->stylecorrection.'>'
					.  '<label for="'.$fieldname.'">'.$label.'</label>'
					.  '<select name="'.$fieldname.'" id="'.$fieldname.'" style="width: 95%;" aria-required="true">'
					.  $textSelect
					.  '</select>'
					.  (!empty($help) ? '<p style="margin-bottom: 0px;">'.$help.'</p>' : '')
					.  '</div>';
		if($this->directReturn)
			return $return.$log;
		$this->form .= $return;
	}

	## FUNCTION TO ADD A SUBMITBUTTON
	function submit($text='Lagre') {
		$return = '<p class="submit"><input type="submit" class="button" name="submit" id="submit" value="'.$text.'" /></p>';
		if($this->directReturn)
			return $return.$log;
		$this->form .= $return;
	}
	
	
	## FUNCTION TO SHOW THE DATE AND TIME SELECTOR
	function datoTid($label, $fieldname, $value=null, $yearFrom=null, $yearTo=null, $help='') {
		$log = $this->logfield($fieldname, $value);
		$hour = date('G',$value);
		$min = date('i',$value);
		## DATEPICKER ELEMENT FOR DAY MONTH YEAR
		$day = '<input class="datepicker" type="text" style="width: 25%;margin-right: 3%;" name="'.$fieldname.'_datepicker" value="'.date('d',$value).'.'.date('m',$value).'.'.date('Y',$value).'"/>';
		## SELECT-LIST FOR HOURS
		$hours = '<select name="'.$fieldname.'_time">';
		for($i=0; $i<24; $i++)
			$hours .= '<option value="'.$i.'" '.($i==$hour?'selected="selected"':'').'>'.($i<10?'0'.$i:$i).'</option>';
		$hours .= '</select>';
		
		## SELECT-LIST FOR MINUTES
		$mins = '<select name="'.$fieldname.'_min">';
		for($i=0; $i<60; $i+=5)
			$mins .= '<option value="'.$i.'" '.($i==$min?'selected="selected"':'').'>'.($i<10?'0'.$i:$i).'</option>';
		$mins .= '</select>';
		$return = '<div class="form-field form-required" '.$this->stylecorrection.'>'
					.  '<label for="'.$fieldname.'">'.$label.'</label>'
					.  $day . ' kl. ' . $hours . ':' . $mins
					#.  '<input name="'.$fieldname.'" id="'.$fieldname.'" type="text" value="'.$value.'" size="20" aria-required="true" />'
					.  (!empty($help) ? '<p style="margin-bottom: 0px;">'.$help.'</p>' : '')
					.  '</div>';
		if($this->directReturn)
			return $return.$log;
		$this->form .= $return;
		
	}
	function datoTidOld($label, $fieldname, $value=null, $yearFrom=null, $yearTo=null, $help='') {
		$log = $this->logfield($fieldname, $value);
		## IF THE FROM-YEAR IS NOT SET, THIS YEAR MINUS 5
		if($yearFrom == null)
			$yearFrom = date("Y") - 5;
		## IF THE TO-YEAR IS NOT SET, THIS YEAR PLUS 5
		if($yearTo == null) 
			$yearTo = date("Y") + 5;
		## IF NOT SELECTED AN VALUE, SELECT NOW
		if($value == null)
			$value = time();
		
		## CALCULATE SELECT VALUES
		$day	= date("d", $value);
		$month	= date("m", $value);
		$year 	= date("Y", $value);
		
		$hour	= date("H", $value);
		$min	= date("i", $value);
		
		## SELECT-LIST FOR DAYS
		$days = '<select name="'.$fieldname.'_dag">';
		for($i=1; $i<31; $i++)
			$days .= '<option value="'.$i.'" '.($i==$day?'selected="selected"':'').'>'.$i.'</option>';
		$days .= '</select>';
		
		## SELECT-LIST FOR MONTHS
		$all_months = array('januar','februar','mars','april','mai','juni',
							'juli','august','september','oktober','november','desember');
		$months = '<select name="'.$fieldname.'_mnd">';
		for($i=1; $i<13; $i++)
			$months .= '<option value="'.$i.'" '.($i==$month?'selected="selected"':'').'>'.$all_months[$i-1].'</option>';
		$months .= '</select>';
		
		## SELECT-LIST FOR YEAR
		$years = '<select name="'.$fieldname.'_ar">';
		for($i=$yearFrom; $i<$yearTo; $i++)
			$years .= '<option name="'.$fieldname.'_year" '.($i==$year?'selected="selected"':'').'>'.$i.'</option>';
		if($yearFrom == $yearTo)
			$years .= '<option name="'.$fieldname.'_year" selected="selected">'.$yearTo.'</option>';
		$years .= '</select>';
		
		## SELECT-LIST FOR HOURS
		$hours = '<select name="'.$fieldname.'_time">';
		for($i=0; $i<24; $i++)
			$hours .= '<option value="'.$i.'" '.($i==$hour?'selected="selected"':'').'>'.($i<10?'0'.$i:$i).'</option>';
		$hours .= '</select>';
		
		## SELECT-LIST FOR MINUTES
		$mins = '<select name="'.$fieldname.'_min">';
		for($i=0; $i<60; $i+=5)
			$mins .= '<option value="'.$i.'" '.($i==$min?'selected="selected"':'').'>'.($i<10?'0'.$i:$i).'</option>';
		$mins .= '</select>';
		
		$return = '<div class="form-field form-required" '.$this->stylecorrection.'>'
					.  '<label for="'.$fieldname.'">'.$label.'</label>'
					.  $days . ' ' . $months . ' ' .$years .' kl. ' . $hours . ':' . $mins
					#.  '<input name="'.$fieldname.'" id="'.$fieldname.'" type="text" value="'.$value.'" size="20" aria-required="true" />'
					.  (!empty($help) ? '<p style="margin-bottom: 0px;">'.$help.'</p>' : '')
					.  '</div>';
		if($this->directReturn)
			return $return.$log;
		$this->form .= $return;
	}
	
	## INSERTS A HIDDEN FIELD AT THE END OF THE FORM
	function hidden($fieldname, $value) {
		$log = $this->logfield($fieldname, $value);
		
		$return = '<input name="'.$fieldname.'" type="hidden" value="'.$value.'" id="'.$fieldname.'" />';
		
		if($this->directReturn)
			return $return.$log;
		$this->hidden .= $return;
	}

	## RETURNS AN DEFAULT TEXTAREA
	function textarea($label, $fieldname, $value='', $help='') {
		$log = $this->logfield($fieldname, $value);
		$return = '<div class="form-field form-required" '.$this->stylecorrection.'>'
					.  '<label for="'.$fieldname.'">'.$label.'</label>'
					.  '<textarea name="'.$fieldname.'" id="'.$fieldname.'" aria-required="true" style="width: 450px; height:200px;">'.$value.'</textarea>'
					.  (!empty($help) ? '<p style="margin-bottom: 0px;">'.$help.'</p>' : '')
					.  '</div>';
		if($this->directReturn)
			return $return.$log;
		$this->form .= $return;
	}
	
	## FUNCTION TO SHOW THE DATE AND TIME SELECTOR
	function dato($label, $fieldname, $value=null, $yearFrom=null, $yearTo=null, $help='') {
		$log = $this->logfield($fieldname, $value);
		## IF THE FROM-YEAR IS NOT SET, THIS YEAR MINUS 5
		if($yearFrom == null)
			$yearFrom = date("Y") - 5;
		## IF THE TO-YEAR IS NOT SET, THIS YEAR PLUS 5
		if($yearTo == null) 
			$yearTo = date("Y") + 5;
		## IF NOT SELECTED AN VALUE, SELECT NOW
		if($value == null)
			$value = time();
		
		## CALCULATE SELECT VALUES
		$day	= date("d", $value);
		$month	= date("m", $value);
		$year 	= date("Y", $value);
		
		## SELECT-LIST FOR DAYS
		$days = '<select name="'.$fieldname.'_dag">';
		for($i=1; $i<31; $i++)
			$days .= '<option value="'.$i.'" '.($i==$day?'selected="selected"':'').'>'.$i.'</option>';
		$days .= '</select>';
		
		## SELECT-LIST FOR MONTHS
		$all_months = array('januar','februar','mars','april','mai','juni',
							'juli','august','september','oktober','november','desember');
		$months = '<select name="'.$fieldname.'_mnd">';
		for($i=1; $i<13; $i++)
			$months .= '<option value="'.$i.'" '.($i==$month?'selected="selected"':'').'>'.$all_months[$i-1].'</option>';
		$months .= '</select>';
		
		## SELECT-LIST FOR YEAR
		$years = '<select name="'.$fieldname.'_ar">';
		for($i=$yearFrom; $i<$yearTo; $i++)
			$years .= '<option name="'.$fieldname.'_year" '.($i==$year?'selected="selected"':'').'>'.$i.'</option>';
		if($yearFrom == $yearTo)
			$years .= '<option name="'.$fieldname.'_year" selected="selected">'.$yearTo.'</option>';
		$years .= '</select>';
				
		$return = '<div class="form-field form-required" '.$this->stylecorrection.'>'
					.  '<label for="'.$fieldname.'">'.$label.'</label>'
					.  $days . ' ' . $months . ' ' .$years
					#.  '<input name="'.$fieldname.'" id="'.$fieldname.'" type="text" value="'.$value.'" size="20" aria-required="true" />'
					.  (!empty($help) ? '<p style="margin-bottom: 0px;">'.$help.'</p>' : '')
					.  '</div>';
		if($this->directReturn)
			return $return.$log;
		$this->form .= $return;
	}
	
	###############################################################
	## CONSTRUCTOR, INIT, RUN AND END
	
	function logfield($fieldname, $value) {
		$return = '<input name="log_current_value_'.$fieldname.'" type="hidden" value="'.$value.'" id="log_current_value_'.$fieldname.'" />';
		if($this->directReturn)
			return $return.$log;
		$this->hidden .= $return;
	}
		
	## CLASS CONSTRUCTOR
	function form($id) {
		$this->init();
	}
	
	## FORM TAG INIT
	function init() {
		$this->form = '<div class="form-wrap" style="width: 350px;">';
	}
	
	## END THE FORM TAG AND WRAPPER
	function end() {
		$this->form .= $this->hidden . '</div>';	
	}
	
	## RETURNS THE INFO GATHERED TOTALLY
	function run() {
		$this->end();
		return $this->form;
	}
}
?>