<?php

namespace UKMNorge\Innslag\Typer;

use Exception;
use Symfony\Component\Yaml\Yaml;

require_once('UKM/Autoloader.php');

class Type
{
    var $id = null;
    var $key = null;
    var $name = null;
    var $tekst = null;
    var $type = false; # gruppe|person
    var $frist = 1;
    var $er_scene = false;

    var $har_titler = false;
    var $har_sjanger = false;
    var $har_funksjoner = false;
    var $har_tekniske_behov = false;

    var $har_filmer = false; # Kan det finnes noe i UKM-TV?
    var $har_bilder = false;

    var $funksjoner = null;
    var $tabell = null;

    var $autfollow_personer = null;

    public function __construct(String $id)
    {
        switch ($id) {
            case 'annet':
                $id = 'scene';
                break;
            case 'video':
                $id = 'film';
                break;
        }

        try {
            $filename = stream_resolve_include_path('UKM/Innslag/Typer/config/' . basename($id) . '.yml');
            $config = Yaml::parse(
                file_get_contents(
                    $filename
                )
            );
        } catch (Exception $e) {
            throw new Exception(
                'Kunne ikke laste inn innslag type ' . $id,
                110003
            );
        }

        $this->id           = $config['numeric_id'];
        $this->key          = strtolower($config['id']);
        $this->name         = $config['navn'];
        $this->tekst        = static::arrayToDotKey('', $config['tekst'], []);
        $this->type         = $config['type'];
        $this->kategori     = $config['kategori'];
        $this->frist        = $config['frist'];
        $this->er_scene     = $config['er_scene'];

        $this->har_tid              = $config['har']['varighet'];
        $this->har_titler           = $config['har']['titler'];
        $this->har_beskrivelse      = $config['har']['beskrivelse'];
        $this->har_sjanger          = $config['har']['sjanger'];
        $this->har_funksjoner       = $config['har']['funksjon'];
        $this->har_tekniske_behov   = $config['har']['tekniske_behov'];

        $this->har_filmer           = $config['har']['media']['filmer'];
        $this->har_bilder           = $config['har']['media']['bilder'];

        $this->autfollow_personer   = isset($config['personer_autofollow']) && $config['personer_autofollow'];

        if (isset($config['funksjoner'])) {
            $keyval = [];
            foreach ($config['funksjoner'] as $key) {
                $keyval[$key] = $this->getTekst('funksjon.' . $key);
            }
            $this->funksjoner = $keyval;
        }

        if (isset($config['titler']['tabell'])) {
            $this->tabell = $config['titler']['tabell'];
        }
    }

    /**
     * Hent tekst fra config
     *
     * @param String $key
     * @param Array $str_replace (key=>val)
     * @return String tekst
     */
    public function getTekst($key, $str_replace = null)
    {
        if (!isset($this->tekst[$key])) {
            return $key;
        }
        if (is_array($str_replace)) {
            return str_replace(array_keys($str_replace), array_values($str_replace), $this->tekst[$key]);
        }
        return $this->tekst[$key];
    }

    /**
     * Hent all tekst i key => val-format
     *
     * @return Array<String>
     */
    public function getAllTekst()
    {
        return $this->tekst;
    }

    /**
     * Hent tekst fra config
     *
     * @param String $key
     * @param Array $str_replace (key=>val)
     * @return String tekst
     */
    public function _($key, $str_replace = null)
    {
        return $this->getTekst($key, $str_replace);
    }

    /**
     * Hent tekst fra config
     *
     * @param String $key
     * @param Array $str_replace (key=>val)
     * @return String tekst
     */
    public function getText($key, $str_replace = null)
    {
        return $this->getTekst($key, $str_replace);
    }

    /**
     * Hent typens numeriske ID 
     * 
     * @deprecated APIv2
     *
     * @return Int $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Hent ID som streng (FORETRUKKET FRA APIv3)
     *
     * @return String
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Hent typens navn
     *
     * @return String navn
     */
    public function getNavn()
    {
        return $this->name;
    }

    /**
     * Har typen funksjoner
     *
     * @return Bool
     */
    public function harFunksjoner()
    {
        return $this->har_funksjoner;
    }

    /**
     * Hent funksjoner
     *
     * @return Array funksjoner ($id => $navn)
     */
    public function getFunksjoner()
    {
        return $this->funksjoner;
    }

    /**
     * Har typen titler?
     *
     * @return Bool
     */
    public function harTitler()
    {
        return $this->har_titler;
    }

    /**
     * Hent tittel-tabell
     *
     * @return String $tabell
     */
    public function getTabell()
    {
        return $this->tabell;
    }

    /**
     * Har typen en tid?
     *
     * @return Bool
     */
    public function harTid()
    {
        return $this->har_tid;
    }

    /**
     * Kan typen ha filmer?
     *
     * @return Bool
     */
    public function harFilmer()
    {
        return $this->har_filmer;
    }

    /**
     * Kan typen ha bilder?
     * 
     * @return Bool
     */
    public function harBilder()
    {
        return $this->har_bilder;
    }

    /**
     * Er dette en type som er med og lager UKM?
     * 
     * @deprecated APIv3
     * @see erEnkeltPerson()
     *
     * @return Bool
     */
    public function erJobbeMed()
    {
        return $this->kategori == 'jobbe';
    }

    /**
     * Er dette en type som viser frem noe?
     *
     * @return Bool
     */
    public function erViseFrem()
    {
        return $this->kategori == 'vise';
    }

    /**
     * Er dette en type som kun kan være en enkeltperson?
     * 
     * Hvis ikke må det være en gruppe
     *
     * @return Bool
     */
    public function erEnkeltPerson()
    {
        return $this->type == 'person';
    }

    /**
     * Er dette en type som kan være en gruppe?
     * 
     * Hvis ikke må det være en enkeltperson
     *
     * @return Bool
     */
    public function erGruppe()
    {
        return $this->type == 'gruppe';
    }

    /**
     * Er dette en underkategori av scene?
     *
     * @return Bool
     */
    public function erScene() {
        return $this->er_scene;
    }

    /**
     * Skal personer i innslaget automatisk følge med når det videresendes?
     *
     * @return bool
     */
    public function harAutomatiskVideresendingAvPersoner()
    {
        return $this->autfollow_personer;
    }

    /**
     * Har typen tekniske behov?
     *
     * @return Bool
     */
    public function harTekniskeBehov()
    {
        return $this->har_tekniske_behov;
    }

    /**
     * Har typen en beskrivelse?
     *
     * @return void
     */
    public function harBeskrivelse()
    {
        return $this->har_beskrivelse;
    }

    /**
     * Har typen sjanger?
     *
     * @return Bool
     */
    public function harSjanger()
    {
        return $this->har_sjanger;
    }

    /**
     * Hvilken frist benytter sjangeren?
     *
     * @return Int 1|2
     */
    public function getFrist()
    {
        return $this->frist;
    }

    /**
     * toString == getNavn()
     *
     * @return String
     */
    public function __toString()
    {
        return $this->getNavn();
    }

    /**
     * Hvilket tittel-objekt bruker denne typen?
     *
     * @return String class name
     */
    public function getTittelClass()
    {
        switch ($this->getKey()) {
            case 'musikk':
                return 'Musikk';
            case 'dans':
                return 'Dans';
            case 'teater':
                return 'Teater';
            case 'litteratur':
                return 'Litteratur';
            case 'matkultur':
                return 'Matkultur';
            case 'utstilling':
                return 'Utstilling';
            case 'film':
            case 'video':
                return 'Film';
            case 'scene':
            case 'annet':
                return 'Annet';
        }
        throw new Exception(
            'Innslag-type ' . $this->getNavn() . ' har ikke titler',
            130001
        );
    }

    /**
     * Hent et key-val objekt med valgte funksjoner og riktig tekst
     *
     * @param Array $valgte_funksjoner
     * @return Array $valgte_funksjoner
     */
    public function getValgteFunksjonerSomKeyVal(array $valgte_funksjoner)
    {
        $return = [];
        foreach ($valgte_funksjoner as $key) {
            $return[$key] = $this->getTekst('funksjon.' . $key);
        }
        return $return;
    }

    /**
     * Konverter tekst-array to key => val
     *
     * @param String $key_base
     * @param Array $config_array
     * @param Array $output
     * @return Array
     */
    public static function arrayToDotKey(String $key_base, array $config_array, array $output)
    {
        foreach ($config_array as $key => $value) {
            if (is_array($value)) {
                $output = array_merge($output, static::arrayToDotKey($key_base . '.' . $key, $value, $output));
            } else {
                $output[trim($key_base . '.' . $key, '.')] = $value;
            }
        }
        return $output;
    }

    /**
     * Valider at gitt objekt er gyldig Type-objekt
     *
     * @param Any $object
     * @return Bool
     */
    public static function validateClass($object)
    {
        return is_object($object) &&
            in_array(
                get_class($object),
                ['UKMNorge\Innslag\Typer\Type']
            );
    }
}
