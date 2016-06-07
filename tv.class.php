<?php
require_once('UKMconfig.inc.php');
require_once('UKM/curl.class.php');
require_once('UKM/sql.class.php');

class tv {
	var $tvurl	 	= '';
	var $embedurl 	= '';
	
	var $storageurl = '';
	var $storageIP	= '212.125.231.33';
	
	public function __construct($tv_id,$cron_id=false) {
		$this->tvurl = 'http://tv.'. UKM_HOSTNAME .'/';
		$this->embedurl = 'http://embed.'. UKM_HOSTNAME .'/';
		$this->storageurl = 'http://video.'. UKM_HOSTNAME .'/';

		// If created by a cron_id ($tv_id = false)
		if($cron_id) {
			// Videoreportasje
			$qry = new SQL("SELECT `video_file` 
							FROM `ukm_standalone_video` 
							WHERE `cron_id` = '#id'",
							array('id' => $cron_id));
			$tv_id = $qry->run('field','video_file');
			// Related video
			if(empty($tv_id)){
				$qry = new SQL("SELECT `file` 
								FROM `ukm_related_video` 
								WHERE `cron_id` = '#id'",
								array('id' => $cron_id));
				$tv_id = $qry->run('field','file');				
			}
		}

		// Fetch video data (included set and category infos)
		$qry = new SQL("SELECT `file`.*,
							   `cat`.`c_id` AS `set_id`,
							   `cat`.`c_name` AS `set`,
							   `fold`.`f_id` AS `category_id`,
							   `fold`.`f_name` AS `category`,
							   `fold`.`f_parent` AS `category_parent_id`
						FROM `ukm_tv_files` AS `file`
						JOIN `ukm_tv_categories` AS `cat` ON (`file`.`tv_category` = `cat`.`c_name`)
						JOIN `ukm_tv_category_folders` AS `fold` ON (`cat`.`f_id` = `fold`.`f_id`)
						WHERE ".(is_numeric($tv_id) 
								? "`tv_id` = '#tvid'" 
								: "`tv_file` LIKE '%#tvid'"
								) ."
						AND `tv_deleted` = 'false'",
					array('tvid' => $tv_id ));
		$res = $qry->run('array');

		$this->cron_id = $cron_id;		
		
		if(!$res || ($cron_id && $tv_id == null)) {
			$this->id = false;
			return false;
		}
		
		foreach($res as $key => $val) {
			$key = str_replace('tv_','', $key);
			$this->$key = $val;
		}
		
		if(!$cron_id && strpos($this->file, 'cronid_') !== false) {
			$cron_start = strpos($this->file, 'cronid_');
			$cron_stop = strpos($this->file, '.', $cron_start);
			
			$this->cron_id = substr($this->file, $cron_start+7, ($cron_stop-($cron_start+7)));
		}
		
		$this->_UKMTVbandwidthmode();

		// Calculate all urls (file, set, embed+++)
		$this->_url();
		$this->_meta();
		$this->_ext();
	}
	
	private function _UKMTVbandwidthmode() {
		$SQL = new SQL("SELECT `conf_value`
						FROM `ukm_tv_config`
						WHERE `conf_name` = 'bandwidth_mode'");
		$mode = $SQL->run('field','conf_value');
		$this->bandwidthmode = $mode == 'low' ? 'low' : 'normal';
	}
	
	public function tag( $tag ) {
		if( !isset( $this->tagObject ) ) {
			$this->_loadTags();
		}
		if( isset( $this->tagObject->$tag ) ) {
			return $this->tagObject->$tag;
		}
		return false;
	}
	private function _loadTags() {
		$this->tagObject = new stdClass();
		$tags = str_replace('||','|', $this->tags);
		$tags = explode('|', $tags);
		foreach( $tags as $string ) {
			if( strpos($string,'_') === false ) {
				continue;
			}
			$tag = explode('_', $string);
			$this->tagObject->$tag[0] = $tag[1];
		}
	}
	public function getCacheIP() {
		$sql = new SQL("SELECT `ip`
						FROM `ukm_tv_caches_caches`
						WHERE `last_heartbeat` >= NOW() - INTERVAL 3 MINUTE
							AND `status` = 'ok' AND `deactivated` = 0
						ORDER BY RAND()
						LIMIT 1");
		return $sql->run('field', 'ip');
	}
	
	public function delete() {
		$this->delete = true;
		$sql = new SQLins('ukm_tv_files', array('tv_id' => $this->id));
		$sql->add('tv_deleted','true');
		return $sql->run();
	}
	
	public function json($extras=array()) {
		foreach($extras as $key => $val) {
			if(!isset($this->$key))
				$this->$key = $val;
		}
		return json_encode($this);
	}
	
	public function setTitle($title){
		$this->title = $title;
	}
	
	public function play() {
		$ins = new SQLins('ukm_tv_plays');
		$ins->add('tv_id', $this->id);
		$ins->add('interval', 0);
		$ins->add('ip', $_SERVER['REMOTE_ADDR']);
		$ins->run();
	}
	
	public function size() {
		/*list($width, $height) = @getimagesize($this->image_url);
		if(!is_numeric($width) || !is_numeric($height))
			return 'Beklager, en feil har oppstått';
		$this->ratio = $width / $height;*/
		// Manuell 16:9 for ytelse
		$this->ratio = 1.77777777777778;
		$width = 1280;
		$height = 720;
		
		if($width > 930) {
			$width = 930;
			$height = $width / $this->ratio;
		}
		$this->width = round($width);
		$this->height = round($height);
	}

	public function listView($class='span2') {
		return '<div class="'.$class.' video" id="'.$this->id.'">
					<a href="'.$this->full_url.'">
						<img src="'.$this->image_url.'" />
						<h5>'.$this->title.'</h5>
					</a>
					<a href="'.$this->set_url.'">
						<div class="kat">'.$this->set.'</div>
					</a>
				</div>';
	}

	// Get the storage server file infos
	public function videofile(){
		global $UKMCURL;
		$UKMCURL = new UKMCURL();
		$lastslash = strrpos($this->file, '/');
		$this->file_path = substr($this->file, 0, $lastslash);
		$this->file_name = substr($this->file, $lastslash+1);

		$this->file_orig 	= $this->file_path .'/'. $this->file_name;
		$this->file_720p 	= str_replace('.mp4', '_720p.mp4', $this->file_orig);
		$this->file_mobile 	= str_replace('.mp4', '_mobile.mp4', $this->file_orig);
		if( $this->file_exists_720p ) {
			$this->file = $this->file_720p;			
		} else {
			$UKMCURL->request($this->storageurl
							.'find.php'
							.'?file='.$this->file_name
							.'&path='.urlencode($this->file_path));
			
			$this->file = $UKMCURL->data->filepath;
			
			if( strpos( $this->file, '720p' ) !== false ) {
				$SQL = new SQLins('ukm_tv_files', array('tv_id' => $this->id ) );
				$SQL->add('file_exists_720p', 1);
				$SQL->run();
			}
		}
		
		// Hvis UKM-TV er i low bandwidth-mode og vi har etablert at valgt videofil
		// inneholder 720p (altså at det finnes både 720p og mobile-utgaver)
		// velges mobil-filen i stedet
		if( $this->bandwidthmode == 'low' && strpos( $this->file, '720p' ) !== false ) {
			$this->file = $this->file_mobile;
			$this->file_exists_smil = false;
		}
	}
	
	public function iframe($width='920px') {
		return $this->embedcode($width);
	}

	public function embedcode($width='920px') {
		$this->size();
		if(!is_numeric($this->ratio))
			return 'Beklager, en feil har oppstått';
		if(strpos($width, '%') !== false) {
			$sizetype = '%';
		} elseif(strpos($width, 'px') !== false) {
			$sizetype = 'px';
		} else {
			$sizetype = 'px';
			$width .= 'px';
		}
		$width = (int) ceil(str_replace($sizetype, '', $width));
		$height = (int) floor($width / $this->ratio);
		
		return '<iframe src="'. $this->embed_url .'" style="width:'.$width.$sizetype.'; height:'.$height.$sizetype.';" class="ukmtv" border="0" frameborder="0" mozallowfullscreen="true" webkitallowfullscreen="true" allowfullscreen="true"></iframe>';
	}

	
	private function _ext() {
		$this->ext = substr($this->file, strrpos($this->file, '.'));
	}

	private function _meta() {
		$this->meta = '<meta property="fb:app_id" content="141016739323676">'
					. '<meta property="og:type" content="video.other">'
					. '<meta property="og:url" content="'.$this->full_url.'">'
					. '<meta property="og:image" content="'.$this->image_url.'">'
					. '<meta property="og:title" content="'.$this->title.'">'
					. '<meta property="og:description" content="Fra '. $this->set .'">'
					. '<meta property="video:actor" content="http://facebook.com/UKMNorge">'
					. '<meta property="video:tag" content="UKM-TV UKM UKM Norge">'
					.'<link rel="alternate" type="application/json+oembed"'
						.' href="http://oembed.'. UKM_HOSTNAME .'/?url='.urlencode($this->full_url).'" title="UKM-TV oEmbed" />';
	}
	
	private function _url() {
		$dashpos = strpos($this->title, ' - ');
		if(!$dashpos)
			$dashpos = strlen($this->title);
		
		$this->title_urlsafe = $this->_safeURL(substr($this->title, 0, $dashpos));
		if( empty( $this->title_urlsafe ) ) {
                	$this->title_urlsafe = 'Ukjent';
                }
                $url_string = $this->title_urlsafe;
		
		$this->url = $url_string.'/'.$this->id;
		$this->full_url = $this->tvurl.$this->url;
		$this->embed_url = $this->embedurl.$this->url;
		
		// IMAGE
		$this->image_url = $this->storageurl.$this->img;
		
		// SET
		$this->set_url = $this->tvurl.'samling/'.$this->set;
		
		// CATEGORY
		$this->category = utf8_encode($this->category);
		$this->category_url = $this->tvurl.'kategorier/'.$this->category;
	}
	
	
	private function _safeURL($string) {
		$string = str_replace(' ','-', $string);
		return preg_replace('/[^a-z0-9A-Z-_]+/', '', $string);
	}
	public function getPlayCount() {
		if( !isset( $this->playCount) ) {
			$this->_loadPlayCount();
		}		
		return $this->playCount;
	}
	private function _loadPlayCount() {
		$sql = new SQL("SELECT `plays`
						FROM `ukm_tv_plays_cache`
						WHERE `tv_id` = '#tvid'",
						array('tvid' => $this->tv_id ) );
		$this->playCount = $sql->run('field', 'plays');
	}
}
