<?php

namespace UKMNorge\Innslag;

require_once('UKM/Autoloader.php');

// KAN IKKE EXTENDE COLLECTION
// fordi numerisk ID er lik for alle underkategorier av scene 🤦🏼‍♂️
class Typer implements \Iterator
{
    private $var = array();
    static $all = null;
    static $allScene = null;

    public function add( $item ) {
        $this->var[] = $item;
	    return $this;
    }
    public function har( $object ) {
        if( is_string( $object ) ) {
            return $this->find( $object );
        }
	    return $this->find( $object->getId() );
    }

    public function get( $id ) {
        return $this->find( $id );
    }

    /**
     * Finn objekt 
     *
     * @param Any $id
     * @return Item
     */
    public function find( $id ) {
	    foreach( $this as $item ) {
		    if( $id == $item->getId() ) {
			    return $item;
		    }
	    }
	    return false;
    }

    public function addById($id)
    {
        return $this->add(self::getById($id));
    }

    public function harJobbeMed() {
        return $this->har( Typer::getByName('nettredaksjon') ) ||
            $this->har( Typer::getByName('konferansier') ) ||
            $this->har( Typer::getByName('arrangor') );
    }

    static function getById($id, $kategori = false)
    {
        return self::load($id, $kategori);
    }

    static function getByKey($key) {
        return static::getByName($key);
    }  
    
    static function getByName($key)
    {
        // Last med kategori om vi er på scene-innslag.
        if (in_array($key, array('musikk', 'dans', 'teater', 'litteratur'))) {
            return self::load(self::_translate_key_to_id($key), $key);
        }
        return self::load(self::_translate_key_to_id($key));
    }

    static function getAllTyper()
    {
        if (null == self::$all) {
            foreach (array(1, 2, 3, 4, 5, 6, 8, 10) as $id) {
                self::$all[] = self::getById($id);
            }
        }
        return self::$all;
    }

    static function getAllScene()
    {
        if (null == self::$allScene) {
            foreach (array('musikk', 'dans', 'teater', 'litteratur', 'annet') as $kategori) {
                self::$allScene[] = self::getById(1, $kategori);
            }
        }
        return self::$allScene;
    }

    static function load($id, $kategori = false)
    {
        switch ($id) {
            case 1:
                switch ($kategori) {
                    case 'musikk':
                        $data = [
                            'id' => 1,
                            'key' => 'musikk',
                            'name' => 'Musikk',
                            'icon' => 'https://ico.ukm.no/delta/delta-musikk-64.png',
                            'har_filmer' => true,
                            'har_titler' => true,
                            'har_tekniske_behov' => true,
                            'har_sjanger' => true,
                            'database_table' => 'smartukm_titles_scene',
                            'funksjoner' => false,
                        ];
                        break;
                    case 'dans':
                        $data = [
                            'id' => 1,
                            'key' => 'dans',
                            'name' => 'Dans',
                            'icon' => 'https://ico.ukm.no/delta/delta-dans-64.png',
                            'har_filmer' => true,
                            'har_titler' => true,
                            'har_tekniske_behov' => true,
                            'har_sjanger' => true,
                            'database_table' => 'smartukm_titles_scene',
                            'funksjoner' => false,
                        ];
                        break;
                    case 'teater':
                        $data = [
                            'id' => 1,
                            'key' => 'teater',
                            'name' => 'Teater',
                            'icon' => 'https://ico.ukm.no/delta/delta-teater-64.png',
                            'har_filmer' => true,
                            'har_titler' => true,
                            'har_tekniske_behov' => true,
                            'har_sjanger' => true,
                            'database_table' => 'smartukm_titles_scene',
                            'funksjoner' => false,
                        ];
                        break;
                    case 'litteratur':
                        $data = [
                            'id' => 1,
                            'key' => 'litteratur',
                            'name' => 'Litteratur',
                            'icon' => 'https://ico.ukm.no/delta/delta-litteratur-64.png',
                            'har_filmer' => true,
                            'har_titler' => true,
                            'har_tekniske_behov' => false,
                            'har_sjanger' => true,
                            'database_table' => 'smartukm_titles_scene',
                            'funksjoner' => false,
                        ];
                        break;
                    default:
                        $data = [
                            'id' => 1,
                            'key' => 'scene',
                            'name' => ($kategori == false ? 'Scene' : 'Annet på scene'),
                            'icon' => 'https://ico.ukm.no/delta/delta-annet-64.png',
                            'har_filmer' => true,
                            'har_titler' => true,
                            'har_tekniske_behov' => true,
                            'har_sjanger' => true,
                            'database_table' => 'smartukm_titles_scene',
                            'funksjoner' => false,
                        ];
                }
                break;
            case 2:
                $data = [
                    'id' => 2,
                    'key' => 'video',
                    'name' => 'Film',
                    'icon' => 'https://ico.ukm.no/delta/delta-film-64.png',
                    'har_filmer' => true,
                    'har_titler' => true,
                    'har_tekniske_behov' => false,
                    'har_sjanger' => true,
                    'database_table' => 'smartukm_titles_video',
                    'funksjoner' => false,
                ];
                break;
            case 3:
                $data = [
                    'id' => 3,
                    'key' => 'utstilling',
                    'name' => 'Utstilling',
                    'icon' => 'https://ico.ukm.no/delta/delta-utstilling-64.png',
                    'har_filmer' => false,
                    'har_titler' => true,
                    'har_tekniske_behov' => false,
                    'har_sjanger' => false,
                    'database_table' => 'smartukm_titles_exhibition',
                    'funksjoner' => false,
                ];
                break;
            case 4:
                $data = [
                    'id' => 4,
                    'key' => 'konferansier',
                    'name' => 'Konferansier',
                    'icon' => 'https://ico.ukm.no/delta/delta-konferansier-64.png',
                    'har_filmer' => false,
                    'har_titler' => false,
                    'har_tekniske_behov' => false,
                    'har_sjanger' => false,
                    'database_table' => false,
                    'funksjoner' => false,
                ];
                break;
            case 5:
                $data = [
                    'id' => 5,
                    'key' => 'nettredaksjon',
                    'name' => 'UKM Media',
                    'icon' => 'https://ico.ukm.no/delta/delta-nettredaksjon-64.png',
                    'har_filmer' => false,
                    'har_titler' => false,
                    'har_tekniske_behov' => false,
                    'har_sjanger' => false,
                    'database_table' => false,
                    'funksjoner' => [
                        'tekst' => 'Journalist',
                        'foto' => 'Fotograf',
                        'videoreportasjer' => 'Videoreportasjer',
                        'flerkamera_regi' => 'Flerkamera, regi',
                        'flerkamera_kamera' => 'Flerkamera, kamera',
                        'pr' => 'PR og pressekontakt'
                    ],
                ];
                break;
            case 6:
                $data = [
                    'id' => 6,
                    'key' => 'matkultur',
                    'name' => 'Matkultur',
                    'icon' => 'https://ico.ukm.no/delta/delta-matkultur-64.png',
                    'har_filmer' => true,
                    'har_titler' => true,
                    'har_tekniske_behov' => false,
                    'har_sjanger' => false,
                    'database_table' => 'smartukm_titles_other',
                    'funksjoner' => false,
                ];
                break;
            case 8:
            case 9:
                $data = [
                    'id' => 8,
                    'key' => 'arrangor',
                    'name' => 'Arrangør',
                    'icon' => 'https://ico.ukm.no/delta/delta-arrangor-64.png',
                    'har_filmer' => false,
                    'har_titler' => false,
                    'har_tekniske_behov' => false,
                    'har_sjanger' => false,
                    'database_table' => false,
                    'funksjoner' => [
                        'lyd' => 'Lyd',
                        'lys' => 'Lys',
                        'scenearbeider' => 'Scenearbeider',
                        'artistvert' => 'Artistvert',
                        'info' => 'Info / sekretariat',
                        'koordinator' => 'Koordinator / produsent'
                    ],
                ];
                break;
            case 10:
                $data = [
                    'id' => 10,
                    'key' => 'ressurs',
                    'name' => 'UKM-ressurs',
                    'icon' => 'https://ico.ukm.no/delta/delta-arrangor-64.png',
                    'har_filmer' => false,
                    'har_titler' => false,
                    'har_tekniske_behov' => false,
                    'har_sjanger' => false,
                    'database_table' => false,
                    'funksjoner' => [
                        'ambassador' => 'Ambassadør',
                        'ressurs' => 'Ressurs',
                    ],
                ];
                break;

            default:
                $data = array('id' => 'missing ' . $id, 'key' => 'missing');
        }
        return new Type(
            $data['id'],
            $data['key'],
            $data['name'],
            $data['icon'],
            $data['har_filmer'],
            $data['har_titler'],
            $data['funksjoner'],
            $data['database_table'],
            $data['har_tekniske_behov'],
            $data['har_sjanger']
        );
    }


    static function _translate_key_to_id($key)
    {
        switch ($key) {
            case 'musikk':
            case 'dans':
            case 'teater':
            case 'litteratur':
            case 'scene':
                $bt_id = 1;
                break;
            case 'film':
            case 'video':
                $bt_id = 2;
                break;
            case 'utstilling':
                $bt_id = 3;
                break;
            case 'konferansier':
                $bt_id = 4;
                break;
            case 'nettredaksjon':
                $bt_id = 5;
                break;
            case 'matkultur':
                $bt_id = 6;
                break;
            case 'arrangor':
                $bt_id = 8;
                break;
            case 'sceneteknikk':
                $bt_id = 9;
                break;
            case 'annet':
                $bt_id = 1;
                break;
            case 'ressurs':
                $bt_id = 10;
                break;
            default:
                $bt_id = false;
        }
        return $bt_id;
    }



    public function count() {
	    return sizeof( $this->var );
    }

    public function rewind()
    {
        reset($this->var);
    }
  
    public function current()
    {
        $var = current($this->var);
        return $var;
    }
  
    public function key() 
    {
        $var = key($this->var);
        return $var;
    }
  
    public function next() 
    {
        $var = next($this->var);
        return $var;
    }
    public function valid()
    {
        $key = key($this->var);
        $var = ($key !== NULL && $key !== FALSE);
        return $var;
    }
}
