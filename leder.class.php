<?php

class leder {
	var $table = array('l_navn','l_epost','l_mobilnummer','l_type');
	
	public function __construct( $id=false ) {
		$this->ID = $id;
		
		if( $id ) {
			$this->_load();
		}
	}
	
	public function set( $key, $val ) {
		$this->$key = $val;
	}
	
	public function update() {
		$sql = new SQLins('smartukm_videresending_ledere_ny', array('l_id' => $this->ID));
		$this->_add_sql_values( $sql );
		$res = $sql->run();
		return $res != -1;
	}
	
	public function create( $pl_from, $pl_to, $season ) {
		$this->pl_from = $pl_from;
		$this->pl_to = $pl_to;
		$this->season = $season;
		
		$sql = new SQLins('smartukm_videresending_ledere_ny');
		$this->_add_sql_values( $sql );
		$res = $sql->run();
		
		$this->ID = $sql->insid();
	}
	
	public function load_by_type( $pl_from, $pl_to, $type ) {
		$this->l_type = $type;
		$sql = new SQL("SELECT `l_id`
						FROM `smartukm_videresending_ledere_ny`
						WHERE `pl_id_from` = '#from'
						AND `pl_id_to` = '#to'
						AND `l_type` = '#type' ",
					array(	'from' => $pl_from,
							'to' => $pl_to,
							'type' => $type
						)
					);
		$this->ID = $sql->run('field', 'l_id');
		return $this->_load();
	}
	
	public function delete( $pl_from ) {
		$sql = new SQLdel('smartukm_videresending_ledere_ny', array('l_id' => $this->ID, 'pl_id_from' => $pl_from ));
		$res = $sql->run();
		
		if( $res != -1 ) {
			$sql = new SQLdel('smartukm_videresending_ledere_natt', array('l_id' => $this->ID));
			return $sql->run() != -1;
		}
		return false;
	}
	
	public function netter() {
		$sql = new SQL("SELECT *
						FROM `smartukm_videresending_ledere_natt`
						WHERE `l_id` = '#leder'",
					array(	'leder' => $this->ID,
						)
					);
		$res = $sql->run();
		
		while( $r = mysql_fetch_assoc( $res ) ) {
			$this->natt[ $r['dato'] ] = $this->_natt( $r );
		}
		
		return $this->natt;
	}
	
	public function natt( $dato=false, $sted=false ) {
		$sql = new SQL("SELECT *
						FROM `smartukm_videresending_ledere_natt`
						WHERE `l_id` = '#leder'
						AND `dato` = '#dato'",
					array(	'leder' => $this->ID,
							'dato' => $dato 
						)
					);
		$res = $sql->run();
		
		if( mysql_num_rows( $res ) == 1 ) {
			$r = mysql_fetch_assoc( $res );
			
			$natt = $this->_natt( $r );
			
			$this->natt[ $r['dato'] ] = $natt;

			if( !$dato && !$sted )
				return $natt;
				
			if( $natt->sted != $sted ) {
				$natt->sted = $sted;
				
				$SQLupd = new SQLins('smartukm_videresending_ledere_natt', array('l_id'=>$this->ID, 'dato'=>$natt->dato));
				$SQLupd->add('sted', $natt->sted);
				$res = $SQLupd->run();
				
				return $res != -1 ? $natt : false;
			}
			return $natt;
		} else {
			$SQLins = new SQLins('smartukm_videresending_ledere_natt');
			$SQLins->add('l_id', $this->ID);
			$SQLins->add('dato', $dato);
			$SQLins->add('sted', $sted);
			$res = $SQLins->run();
			
			return $res != -1 ? $this->_natt( array('dato'=>$dato, 'sted'=>$sted ) ) : false; 
		}
	}
	
	private function _natt( $r ) {
		$natt = new stdClass();
		$natt->dato = $r['dato'];
		$natt->sted = $r['sted'];
		
		$dato = explode('_', $r['dato']);
		$natt->dag = $dato[0];
		$natt->mnd = $dato[1];
		return $natt;
	}
	
	private function _add_sql_values( $sql ) {
		foreach( $this->table as $key ) {
			if( $key == 'l_mobilnummer')
				$sql->add( $key, (int)$this->$key );
			else
				$sql->add( $key, $this->$key );
		}
		$sql->add('pl_id_to', $this->pl_to);
		$sql->add('pl_id_from', $this->pl_from);
		$sql->add('season', $this->season);
		return $sql;
	}
	
	
	private function _load() {
		$sql = new SQL("SELECT * 
						FROM `smartukm_videresending_ledere_ny`
						WHERE `l_id` = '#l_id'",
					array('l_id' => $this->ID)
					);
		$res = $sql->run();
		
		if( mysql_num_rows( $res ) == 0 )
			return false;
		
		$row = mysql_fetch_assoc( $res );
		
		foreach( $row as $key => $val ) {
			$this->$key = $val;
		}
		
		$this->pl_to = $this->pl_id_to;
		$this->pl_from = $this->pl_id_from;
		switch( $this->l_type ) {
			case 'hoved':
			case 'utstilling':
			case 'reise':
				$this->type_nice = ucfirst( $this->l_type ).'leder';
				break;
			case 'turist':
			case 'ledsager':
				$this->type_nice = ucfirst( $this->l_type );
				break;
		}

		$from = new monstring( $this->pl_from );
		$this->kommer_fra = $from->g('pl_name');
		
		$this->netter();
		
		return true;
	}
}