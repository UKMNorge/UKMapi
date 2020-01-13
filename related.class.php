<?php
class related {
	var $table = 'ukmno_wp_related';
	var $authors;
	
	####
	public function __construct($b_id,$album=0, $pl_type=false, $season=false) {
		global $blog_id;
		$this->b_id = $b_id;
		$this->album_id = $album;
		$this->load_band();
		
		if( function_exists( 'get_option') ) {
			$this->pl_type = get_option('site_type');
			$this->season  = get_option('season');
			$this->blog_id = $blog_id;
			$this->blog_url = get_option('siteurl');
		} else {
			$this->pl_type = $pl_type;
			$this->season = $season;
		}
				
	}

	###
	public function set($post_id, $post_type, $post_meta=array()) {
		$this->delete($post_id, $post_type);
		
		$set = new SQLins($this->table);
		
		$set->add('blog_id', $this->blog_id);
		$set->add('blog_url', $this->blog_url);
		
		$set->add('post_id', $post_id);
		$set->add('post_type', $post_type);
		$set->add('post_meta', serialize($post_meta));

		$set->add('a_id', $this->album_id);
		
		$set->add('b_id', $this->b_id);
		$set->add('b_kommune', $this->b_kommune);
		$set->add('b_season', $this->season);
		$set->add('pl_type', $this->pl_type);
		
		#error_log('RELATED SET ' .$set->debug());
		return $set->run();
	}

	###
	public function delete($post_id, $post_type) {
		if($post_type == 'post') 
			$del = new SQLdel($this->table,
							  array('blog_id'=>$this->blog_id,
							  		'post_id'=>$post_id,
							  		'post_type'=>$post_type,
							  		'b_id'=>$this->b_id)
							  );
		else
			$del = new SQLdel($this->table,
							  array('blog_id'=>$this->blog_id,
							  		'post_id'=>$post_id,
							  		'post_type'=>$post_type)
							  );
		$del->run();
		#error_log('RELATED DELETE ' . $del->debug());
	}
	
	
	###
	# Get related items for one band
	public function getAlbum() {
		$get = new SQL("SELECT * FROM `#table`
						WHERE `a_id` = '#aid'",
						array('table'=>$this->table,
							  'aid'=>$this->album_id)
						);
		$get = $get->run();
		while($r = SQL::fetch($get)) {
			$r['post_meta'] = unserialize($r['post_meta']);
			$ret[$r['post_id']] = $r;
		}	

		return $ret;
	}
	
	public function get() {
		$ret = null;
		$get = new SQL("SELECT * FROM `#table`
						WHERE `b_id` = '#bid'",
						array('table'=>$this->table,
							  'bid'=>$this->b_id)
						);
		$get = $get->run();
		if(!$get)
			return false;
		while($r = SQL::fetch($get)){
			$r['post_meta'] = unserialize($r['post_meta']);
			$r['post_meta'] = $this->loadAuthor($r['post_meta']);
			$r['post_meta'] = $this->missingLarge($r['post_meta']);
			
			// Video har ingen unik ID, vi må derfor generere en, slik at man kan loope arrayet
			// Bruker file grunnet feil våren 2012, da konverteren konverterte samme fil mange,mange ganger
			if($r['post_type']=='video')
				$r['post_id'] = $r['post_meta']['file'];

			$ret[$r['post_id']] = $r;
		}
		return $ret;
	}
	
	private function loadAuthor($meta) {
		if( isset( $meta['author'] ) ) {
			$aut = $meta['author'];
		} else {
			$aut = '';
		}
		if(!isset($this->authors[$aut])) {
			if( function_exists('get_userdata') ) {
				$user_info = get_userdata($aut);
				if( is_object( $user_info ) ) {
					$name = ucwords($user_info->display_name);
				} else {
					$name = '';
				}
			} else {
				$name = '';
			}
		} else {
			$name = $this->authors[$aut];
		}
		$this->authors[$aut] = $meta['author'] = $name;
		return $meta;
	}
	
	public function getLastImage($size){
		$get = new SQL("SELECT * FROM `#table`
						WHERE `b_id` = '#bid'
						ORDER BY `rel_id` DESC
						LIMIT 1",
						array('table'=>$this->table,
							  'bid'=>$this->b_id)
						);
		$r = $get->run('array');
		if(!$r||!is_array($r))
			return false;
		$r['post_meta'] = unserialize($r['post_meta']);
		return $r['blog_url'].'/files/'.$r['post_meta']['sizes'][$size]['file'];
	}
	
	private function missingLarge($post_meta) {
		
		if( is_array( $post_meta['sizes'] ) && !isset( $post_meta['sizes']['large'] ) ) {
			$post_meta['sizes']['large']['file'] = isset( $post_meta['file'] ) ? $post_meta['file'] : '';
		}

		if( !is_array( $post_meta['sizes'] ) ) {
			$post_meta['sizes'] = [
				'large' => [
					'file' => (isset( $post_meta['file'] ) ? $post_meta['file'] : '' )
				]
			];
		}
		return $post_meta;
	}
	
	###
	# Get band infos (only kommune)
	private function load_band() {
		if($this->b_id==0){
			$this->b_kommune = false;
			return;
		}
		$load = new SQL("SELECT `b_kommune`
						 FROM `smartukm_band` 
						 WHERE `b_id` = '#bid'",
						 array('bid'=>$this->b_id));
		$this->b_kommune = $load->run('field','b_kommune');
	}
}
?>