<?php
require_once('UKM/tv.class.php');

class tv_files {
	var $randomize = false;
	var $executed = false;
	var $loaded = false;
	var $internal_pointer = -1;
	var $limit = false;
	var $debug = false;
	
	public function debug() {
		$this->debug = true;
	}
	
	public function __construct($type, $object=false) {
		$this->type = $type;
		$this->object = $object;
		
		switch($this->type) {
			case 'place': 
				$this->qry = "SELECT * FROM `ukm_tv_files`
						WHERE `tv_tags` LIKE '%|pl_#plid|%'
						ORDER BY `tv_title` ASC";
				$this->vars = array( 'plid' => $object );
				break;
			case 'band':
				$this->qry ="SELECT `tv_id`
							 FROM `ukm_tv_files`
							 WHERE `b_id` = '#bid'
							 AND `tv_deleted` = 'false'";
				$this->vars = array( 'bid' => $object );
				break;
            // Person is a tag, so set correct format, and continue over to tag
			case 'popular_from_plid':
				// OBJECT MUST BE PL_ID
				$this->qry = "SELECT `t_play`.`tv_id`, COUNT(`t_play`.`tv_id`) AS `plays`
								FROM `ukm_tv_plays` AS `t_play`
								JOIN `ukm_tv_files` AS `t_tv` ON (`t_tv`.`tv_id` = `t_play`.`tv_id`)
								WHERE `tv_tags` LIKE '%|pl_#plid|%'
								GROUP BY `t_play`.`tv_id`
								ORDER BY `plays` DESC
								LIMIT #limit";
				$this->vars = array( 'plid' => $object);
				break;
            case 'person':
                $object = 'p_'. $object;
			case 'tag':
				$this->qry = "SELECT `tv_id`
							FROM `ukm_tv_files`
							WHERE `tv_tags` LIKE '%|#related|%'
							AND `tv_deleted` = 'false'";
				$this->vars = array( 'related' => $object );
				break;
			case 'set':
				$this->qry = "SELECT `tv_id`
							FROM `ukm_tv_files`
							WHERE `tv_category` LIKE '#search'
							AND `tv_deleted` = 'false'
							ORDER BY `tv_title` ASC";
				$this->vars = array('search' => $object);
				break;
			case 'alphabet':
				$this->qry = "SELECT `tv_id`
							FROM `ukm_tv_files`
							WHERE `tv_title` LIKE '#key%'
							AND `tv_deleted` = 'false'
							ORDER BY `tv_title` ASC";
				$this->vars = array('key' => $object);
				break;
			case 'related':
				// OBJECT must be a video object
				$this->randomize = true;
				$this->qry = "SELECT `file`.`tv_id`,
								(SELECT `ukm_tv_plays_cache`.`plays`
								 FROM `ukm_tv_plays_cache`
								 WHERE `ukm_tv_plays_cache`.`tv_id` = `file`.`tv_id`) AS `plays`
							FROM `ukm_tv_files` AS `file`
							WHERE `file`.`tv_category` = '#cat'
							AND `file`.`tv_id` != '#this'
							AND `tv_deleted` = 'false'
							ORDER BY `plays` DESC, RAND()
							LIMIT #limit";
				$this->vars = array('cat' => $object->set,
									'this' => $object->id);
				break;
			case 'featured':
				// OBJECT must be an string $listname
				$this->randomize = true;
				$this->qry = "SELECT `feat`.`tv_id`, `feature_name`
								FROM `ukm_tv_featured` AS `feat`
								JOIN `ukm_tv_files` AS `file` ON (`file`.`tv_id` = `feat`.`tv_id`)
								WHERE `feature_list` = '#list'
								AND `tv_deleted` = 'false'
								ORDER BY RAND()
								LIMIT #limit";
				$this->vars = array('list' => $object);
				break;
			case 'popular':
				// OBJECT must be an string timer or boolean
				if($object === false) {
					$this->qry = "SELECT `file`.`tv_id`,
									(SELECT `ukm_tv_plays_cache`.`plays`
									 FROM `ukm_tv_plays_cache`
									 WHERE `ukm_tv_plays_cache`.`tv_id` = `file`.`tv_id`) AS `plays`
								FROM `ukm_tv_files` AS `file`
								WHERE `tv_deleted` = 'false'
								ORDER BY `plays` DESC
								LIMIT #limit";
					$this->vars = array();
				} else {
					$this->qry = "SELECT `t_play`.`tv_id`, COUNT(`t_play`.`tv_id`) AS `plays`
									FROM `ukm_tv_plays` AS `t_play`
									JOIN `ukm_tv_files` AS `t_tv` ON (`t_tv`.`tv_id` = `t_play`.`tv_id`)
									WHERE `timestamp` LIKE '#timer%'
									AND `tv_deleted` = 'false'
									GROUP BY `t_play`.`tv_id`
									ORDER BY `plays` DESC
									LIMIT #limit";
					$this->vars = array( 'timer' => $object);
				}
				break;
			case 'search':
				// SEARCH FOR TITLE AND BAND NAME (TV TITLE)
				$search = str_replace(',', ' ', $object);
				if(substr_count($search, ' ') == 0) {
					$where = " `tv_title` LIKE '%#title%'";
				} else {
					$where = "MATCH (`tv_title`) AGAINST('+#title' IN BOOLEAN MODE)";
				}
				$qry = new SQL("SELECT `tv_id`,
								MATCH (`tv_title`) AGAINST('#title') AS `score`
								FROM `ukm_tv_files`
								WHERE $where
								AND `tv_deleted` = 'false'
								",
								array( 'title' => $object) );
				$res = $qry->run();
				$i = 0;
				if($res) {
					while( $r = mysql_fetch_assoc( $res ) ) {
						$videos[$r['tv_id']] = $r['score'];
						$titles[] = $r['tv_id'];
					}
				}
			
				// SEARCH FOR PERSONS NAME
				$search = str_replace(',', ' ', $object);
				if(false){#substr_count($search, ' ') == 0) {
					$where = " `p_name` LIKE '%#title%'";
				} else {
					$where = "MATCH (`p_name`) AGAINST('#title' IN BOOLEAN MODE)";
				}
				$qry = new SQL("SELECT `tv_id`, `p_name`,
								MATCH (`p_name`) AGAINST('#title') AS `score`
								FROM `ukm_tv_persons`
								WHERE $where
								AND `tv_deleted` = 'false'
								",
								array( 'title' => '+'.$object) );
				$res = $qry->run();
				$i = 0;
				if($res) {
					while( $r = mysql_fetch_assoc( $res ) ) {
						if(is_array($titles) && in_array($r['tv_id'], $titles)) {
							$videos[$r['tv_id']] = $videos[$r['tv_id']] + $r['score'];
						} else
							$videos[$r['tv_id']] = $r['score'];
					}
				}
				
				@arsort($videos);
				
				$this->videos = $this->video_ids = array();
				if(is_array($videos))
				foreach($videos as $id => $score) {
					$this->video_ids[] = $id;
				}
				$this->num_videos = sizeof($this->video_ids);
				$this->executed = true;
				break;
		}		
	}
	
	public function limit($requested_limit) {
		$this->limit = $requested_limit;
	}

	// WILL ROUND (floor) THE NUMBER OF VIDEOS TO CORRECTLY FILL ROWS
	public function round($requested_limit, $per_row) {
		$this->limit($requested_limit);
		$this->_execute();

		// If is int - success
		// Else pop off results down to max no full rows
		if(!is_int($this->num_videos / $perrow)) {
			$rows = $this->num_videos % $perrow;
			$target_num_videos = $rows * $perrow;

			for($i = $target_num_videos; $i < $this->num_videos; $i++) {
				array_pop($this->video_ids);
			}
		}
	}

    public function getVideos() {
        if( !isset( $this->videos ) ) {
            return array();
        }
        return $this->videos;
    }

	public function fetch($limit=false) {
		if(!$this->executed) {
			if($limit)
				$this->limit($limit);
			$this->_execute();
		}
		if(!$this->loaded)
			$this->_load();

		if(sizeof($this->videos)==0)
			return false;
		
		$this->internal_pointer++;
		
		if(isset($this->videos[$this->internal_pointer]))
			return $this->videos[$this->internal_pointer];
		
		return false;
	}
	
	public function print_list($limit=false) {
		$i=0;
		while($video = $this->fetch($limit)) {
			$i++;
			echo $video->listView();
			if( is_int($i / 5) )
				echo '</div><div class="row">';
		}
	} 
	
	private function _execute() {
		$this->video_ids = array();
		$this->videos = array();
		
		$this->executed = true;
		$this->vars['limit'] = $this->limit;
		// FETCH VIDEOS AND STORE IN ARRAY
		$this->sql = new SQL($this->qry, $this->vars);
		$result = $this->sql->run();
		if( $this->debug ) {
			echo $this->sql->debug();
		}
		if($result) {
			while($r = mysql_fetch_assoc($result)) {
				if($this->type=='featured')
					$this->new_titles[$r['tv_id']] = $r['feature_name'];
				
				$this->video_ids[] = $r['tv_id'];
			}
		}
		
		// RANDOMIZE IF RESULTS SHOULD BE RANDOM
		if($this->randomize)
			shuffle($this->video_ids);
		
		$this->num_videos = sizeof($this->video_ids);
	}
	
	
	private function _load() {
		$this->loaded = true;
		foreach($this->video_ids as $tv_id) {
			$video = new tv($tv_id);
			
			if($this->type=='featured')
				$video->setTitle($this->new_titles[$tv_id]);

			$this->videos[] = $video;
		}

	}
}
