<?php

require_once('UKM/sql.class.php');
require_once('UKM/fylke.class.php');
require_once('UKM/fylker.class.php');


class monstring_skjema {
	public function __construct($f_id, $pl_id=null) {
#		trigger_error('Ikke testet i UKMapi, kopiert fra UKMvideresending_festival', E_USER_NOTICE);

		$this->f_id = $f_id;
		$this->pl_id = $pl_id;
		$this->fylke = Fylker::getById($f_id);
	}
	
	public function getId() {
		return $this->f_id;
	}

	public function getQuestions() {
		$sql = new SQL("SELECT * FROM `smartukm_videresending_fylke_sporsmal` AS `sporsmal`
						WHERE `sporsmal`.`f_id` = '#f_id'
						ORDER BY `sporsmal`.`order`", array('f_id' => $this->f_id));

		$res = $sql->run();
		$questions = array();
		while ($row = mysql_fetch_assoc($res)) {
			$questions[] = $this->getQuestionFromData($row);
		}
		return $questions;
	}

	public function updateQuestion($id, $title, $type, $help, $order) {
		$sql = new SQLins('smartukm_videresending_fylke_sporsmal', array('q_id' => $id));
		$sql->add('q_title', $title);
		$sql->add('q_type', $type);
		$sql->add('q_help', $help);
		$sql->add('order', $order);
		
		$res = $sql->run();
		
		if($res == 0 && $sql->error)
			return false;
		return true;
	}

	public function deleteQuestion($id) {
		$sql = new SQLdel('smartukm_videresending_fylke_sporsmal', array('q_id' => $id));

		$res = $sql->run();
		if($res == 1)
			return true;
	}

	public function addQuestion($title, $type, $help, $order = null) {
		if(null == $order) {
			// TODO: Sett order.
		}
		$sql = new SQLins('smartukm_videresending_fylke_sporsmal');
		$sql->add('q_title', $title);
		$sql->add('q_type', $type);
		$sql->add('q_help', $help);
		$sql->add('order', $order);
		$sql->add('f_id', $this->f_id);
		$res = $sql->run();
		
		// If insert worked
		if ($res > 0)
			return true;
		return false;
	}

	public function getQuestionsWithAnswers() {
		/*$sql = new SQL("SELECT * FROM `smartukm_videresending_fylke_sporsmal`
						WHERE `f_id` = '#f_id'", array('f_id' => $this->f_id));*/
	
		// Finn spørsmål for fylket
		$sql = new SQL("SELECT *
						FROM `smartukm_videresending_fylke_sporsmal` AS `sporsmal`
						WHERE `sporsmal`.`f_id` = '#f_id'
						GROUP BY `sporsmal`.`q_id`
						ORDER BY `sporsmal`.`order`",  array('f_id' => $this->f_id));

		#echo $sql->debug();
		$res = $sql->run();
		$data = array();
		$qs = '(';
		while ($row = mysql_fetch_assoc($res)) {
			$data[$row['q_id']] = $row;
			$qs .= $row['q_id'].','; 
		}	
		$qs = rtrim($qs, ',');
		$qs .= ')';

		// Finn svar
		$sql = new SQL("SELECT * FROM `smartukm_videresending_fylke_svar` 
						WHERE `q_id` IN #questions
						AND `pl_id` = '#pl_id'", array('questions' => $qs, 'pl_id' => $this->pl_id));
		#echo $sql->debug();
		$res = $sql->run();

		$replies = array();
		while($row = mysql_fetch_assoc($res)) {
			$replies[$row['q_id']] = $row['answer'];
		}
		// Data har alle spørsmål
		foreach($data as $q_id => $array) {
			$questions[$q_id] = $array;
			if(array_key_exists($q_id, $replies))
				$questions[$q_id]['answer'] = $replies[$q_id];
			else 
				$questions[$q_id]['answer'] = null;
		}

		/*echo '<pre>';
		var_dump($questions);
		echo '</pre>';*/
		
		// Bygg spørsmål med svar-array
		$data = array();
		foreach($questions as $row) {
			$data[] = $this->getQuestionFromData($row);
		}

		/*echo '<pre>';
		var_dump($questions);
		echo '</pre>';*/

		// Returner et ferdig sortert array i rett rekkefølge
		#$questions = $this->orderQuestions($questions);
		/*echo '<pre>';
		var_dump($questions);
		echo '</pre>';*/
		return $data;
	}

	private function orderQuestions($questions) {
		usort($questions, function($a, $b) {
			return $a->order - $b->order;
		});
		return $questions;
	}

	public function getQuestionFromData($data) {
		/*echo '<pre>';
		var_dump($data);
		echo '</pre>';*/
		$q = new stdClass();
		$q->id = $data['q_id'];
		$q->title = utf8_encode($data['q_title']); 
		$q->type = $data['q_type']; // May be 'janei', 'korttekst', 'langtekst', 'kontakt', 'overskrift'. Mulig mer?
		$q->help = utf8_encode($data['q_help']); 
		$q->order = $data['order']; // Rekkefølgehjelper, for å sortere elementer.
		$q->f_id = $data['f_id']; // Fylke-ID - trengs denne?
		$q->fylke = $this->fylke;

		if ($q->type == 'kontakt') 
			$q->value = $this->getKontakt($data['answer']);
		else
			$q->value = stripslashes( utf8_encode($data['answer']) );
		return $q;
	}

	public function getKontakt($str) {
		#var_dump($str);
		$str = explode('__||__', $str);
		$answer = new stdClass();
		$answer->navn = stripslashes( utf8_encode($str[0]) );
		$answer->mobil = utf8_encode($str[1]);
		$answer->epost = utf8_encode($str[2]);

		return $answer;
	}

	public function answerQuestion($q_id, $answer, $debug = false) {

		// Check if question is answered before
		$sql = new SQL("SELECT COUNT(*) FROM smartukm_videresending_fylke_svar 
						WHERE `q_id` = '#q_id' AND `pl_id` = '#pl_id'", array('q_id' => $q_id, 'pl_id' => $this->pl_id));
		$res = $sql->run('field', 'COUNT(*)');
		if($res > 0) 
			$sql = new SQLins('smartukm_videresending_fylke_svar', array('q_id' => $q_id, 'pl_id' => $this->pl_id));
		else
			$sql = new SQLins('smartukm_videresending_fylke_svar');

		if (is_array($answer) && (count($answer) > 1) ) {
			$a = '';
			foreach ($answer as $sub) {
				$a = $a . '__||__' . $sub;
			}
			$a = trim($a, '__||__');
		} 
		else
			$a = $answer;

		$sql->add('q_id', $q_id);
		$sql->add('pl_id', $this->pl_id);
		$sql->add('answer', (string)$a);
		
		$res = $sql->run();

		if ($res != 1)
			if ($sql->error())
				return $sql; 
		return $res;
	}

	/**
	 * Test-method only, provides dummy data.
	 *
	 */
	public function getQuestion($id) {
		$q = new stdClass();
		$q->id = $id;
		$q->title = 'Spørsmålstittel'.$id; // TODO: DROPP ID
		$q->type = 'kontakt'; // May be 'janei', 'korttekst', 'langtekst', 'kontakt', 'overskrift'. Mulig mer?
		$q->help = 'Hjelpetekst til spørsmålet'; 
		$q->order = $id; // Rekkefølgehjelper, for å sortere elementer.
		$q->f_id = $this->f_id; // Fylke-ID - trengs denne?
		$q->fylke = $this->fylke;
		if($this->pl_id)
			$q->value = $this->getQuestionAnswer($q);
		return $q;
	}
}