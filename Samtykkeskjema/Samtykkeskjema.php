<?php

namespace UKMNorge\Samtykkeskjema;

use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Innslag\Media\Bilder\Bilde;
use UKMNorge\Filmer\UKMTV\Film;
use UKMNorge\Innslag\Innslag;
use Exception;

require_once('UKM/Autoloader.php');

/**
 * Samtykkeskjema representerer et samtykkeskjema-prosjekt.
 * 
 * Samtykkeskjemaet kan ha prosjekter, arrangementer, versjoner og entiteter.
 * Prosjekter er prosjekter knyttet til samtykkeskjemaet.
 * Arrangementer er arrangementer knyttet til samtykkeskjemaet.
 * Versjoner er versjoner av samtykkeskjemaet.
 * Entiteter er entiteter knyttet til samtykkeskjemaet.
 * 
 * OBS: Samtykkeskjema har versjoner og versjoner kan ha svar/samtykke fra brukere.
 */
class SamtykkeSkjema extends SkjemaSuper {
    
    const TABLE = 'samtykkeskjema';

    protected string $id;
    protected string $navn;
    protected array $versjoner = [];
    protected array $prosjekter = [];
    protected array $arrangementer = [];
    protected array $entiteter = []; // Representasjon av samtykkeskjemaet i andre klasser (objekter) som Bilde, Film, Innslag, etc.

    private array $supportedEntiteter = [
        'arrangement' => Arrangement::class,
        'bilde' => Bilde::class,
        'film' => Film::class,
        'innslag' => Innslag::class,
    ];

    /**
     * Constructor
     * @param int|array $data - Kan være ID (int) eller en database-rad (array)
     * @throws Exception
     */
    public function __construct($data) {
        if (is_numeric($data)) {
            $this->_loadById($data);
        } elseif (is_array($data)) {
            $this->_loadByRow($data);
        } else {
            throw new Exception('Kan kun opprette Samtykkeskjema med numerisk ID eller rad fra database.');
        }
    }

    /**
     * Hent alle samtykkeskjemaer
     * @return SamtykkeSkjema[]
     */
    public static function getAll(): array
    {
        $samtykkeskjemaer = [];
        $sql = new Query(
            "SELECT * FROM `" . self::TABLE . "` ORDER BY `id` DESC",
            []
        );
        $res = $sql->run();
        while ($row = Query::fetch($res)) {
            $samtykkeskjemaer[] = new self($row);
        }
        return $samtykkeskjemaer;
    }

    /**
     * Hent alle samtykkeskjemaer fra arrangement
     * @param int $arrangementId
     * @return SamtykkeSkjema[]
     */
    public static function getAllByArrangementId($arrangementId) : array {
        $samtykkeskjemaer = [];
        $sql = new Query("
            SELECT s.*
            FROM `" . self::TABLE . "` AS s
            JOIN `rel_samtykkeskjema_arrangement` AS rel ON s.id = rel.skjema_id
            WHERE rel.arrangement_id = '#id'",
            [
                'id' => $arrangementId
            ]
        );
        $res = $sql->run();
        while($row = Query::fetch($res)) {
            $samtykkeskjemaer[] = new self($row);
        }
        return $samtykkeskjemaer;
    }


    /**
     * Hent samtykkeskjemaer fra arrangement
     * @param int $arrangementId
     * @return SamtykkeSkjema[]
     */
    public static function getByArrangementId($arrangementId) : array {
        $samtykkeskjemaer = [];
        $sql = new Query("
            SELECT s.*
            FROM `" . self::TABLE . "` AS s
            JOIN `rel_samtykkeskjema_arrangement` AS rel ON s.id = rel.skjema_id
            WHERE rel.arrangement_id = '#id'",
            [
                'id' => $arrangementId
            ]
        );

        $res = $sql->run();
        while($row = Query::fetch($res)) {
            $samtykkeskjemaer[] = new self($row);
        }
        return $samtykkeskjemaer;
    }

    // Override SkjemaSuper
    public function isAnswered($userId) : bool {
        foreach($this->getVersjoner() as $versjon) {
            if($versjon->isAnswered($userId)) {
                return true;
            }
        }
        return false;
    }

    public function isGodkjent($userId) : bool {
        return $this->getLastVersion()->isGodkjent($userId);
    }

    /**
     * Hent samtykkeskjemaer fra prosjekt
     * @param int $prosjektId
     * @return SamtykkeSkjema[]
     */
    public static function getByProsjektId($prosjektId) : array {
        $samtykkeskjemaer = [];
        $sql = new Query("
            SELECT s.*
            FROM `" . self::TABLE . "` AS s
            JOIN `" . SamtykkeProsjekt::TABLE . "` AS p ON s.id = p.skjema_id
            WHERE p.id = '#id'",
            [
                'id' => $prosjektId
            ]
        );
        $res = $sql->run();
        while($row = Query::fetch($res)) {
            $samtykkeskjemaer[] = new self($row);
        }
        return $samtykkeskjemaer;
    }

    /**
     * Sett navn på samtykkeskjemaet
     * @param string $navn
     */
    public function setNavn(string $navn): void
    {
        $this->navn = $navn;
    }

    /**
     * Hent ID
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Hent tittel
     * @return string
     */
    public function getNavn(): string {
        return $this->navn;
    }

    /**
     * Hent data fra database ved ID
     * @param int $id
     * @throws Exception
     */
    protected function _loadById($id) {
        // Bruk Query-klassen fra UKM om tilgjengelig, eller simuler
        $sql = new Query("
            SELECT *
            FROM `" . self::TABLE . "`
            WHERE `id` = '#id'",
            [
                'id' => $id
            ]
        );
        $res = $sql->run('array');

        if ($res) {
            $this->_loadByRow($res);
        } else {
            throw new Exception('Samtykkeskjema med ID ' . $id . ' finnes ikke');
        }
    }

    /**
     * Last inn data fra array
     * @param array $row
     */
    protected function _loadByRow($row) {
        $this->id         = $row['id'];
        $this->navn     = $row['navn'];
    }

    public function getProsjekter() {
        if( empty($this->prosjekter) ) {
            $this->_loadProsjekter();
        }
        return $this->prosjekter;
    }

    private function _loadProsjekter() {
        $sql = new Query("
            SELECT *
            FROM `" . SamtykkeProsjekt::TABLE . "`
            WHERE `skjema_id` = '#id'",
            [
                'id' => $this->id
            ]
        );
        $res = $sql->run();
        while($row = Query::fetch($res)) {
            $this->prosjekter[] = new SamtykkeProsjekt($row);
        }
    }

    /**
     * Hent arrangementer
     * @return Arrangement[]
     */
    public function getArrangementer() {
        if( empty($this->arrangementer) ) {
            $this->_loadArrangementer();
        }
        return $this->arrangementer;
    }

    /**
     * Last inn arrangementer
     */
    private function _loadArrangementer() {
        $sql = new Query("
            SELECT p.*
            FROM `smartukm_place` p
            JOIN `rel_samtykkeskjema_arrangement` r ON r.arrangement_id = p.pl_id
            WHERE r.skjema_id = '#id'",
            [
                'id' => $this->id
            ]
        );
        $res = $sql->run();
        while($row = Query::fetch($res)) {
            $this->arrangementer[] = new Arrangement($row);
        }
    }

    /**
     * Hent versjoner av samtykkeskjemaet
     * Det er kun versjoner som kan ha svar/samtykke fra brukere
     * @return SamtykkeVersjon[]
     */
    public function getVersjoner() {
        if( empty($this->versjoner) ) {
            $this->_loadVersjoner();
        }
        return $this->versjoner;
    }

    /**
     * Hent versjon av samtykkeskjemaet
     * @param int $versjonId
     * @return SamtykkeVersjon | null
     */
    public function getVersjon($versjonId) : SamtykkeVersjon | null {
        $versjoner = $this->getVersjoner();
        foreach($versjoner as $versjon) {
            if($versjon->getId() == $versjonId) {
                return $versjon;
            }
        }
        return null;
    }

    public function getLastVersion() : SamtykkeVersjon | null {
        $versjoner = $this->getVersjoner();
        if (empty($versjoner)) {
            return null;
        }

        // Finn versjonen med høyest versjonsnummer
        $lastVersion = null;
        $maxVersjonNr = null;
        foreach ($versjoner as $versjon) {
            $currentNr = $versjon->getVersjonNr();
            if ($maxVersjonNr === null || version_compare($currentNr, $maxVersjonNr, '>')) {
                $maxVersjonNr = $currentNr;
                $lastVersion = $versjon;
            }
        }
        return $lastVersion;
    }

    /**
     * Last inn versjoner
     * HUSK: kun versjoner som kan ha svar/samtykke fra brukere
     */
    private function _loadVersjoner() {
        $sql = new Query("
            SELECT *
            FROM `samtykkeskjema_version`
            WHERE `skjema_id` = '#id'",
            [
                'id' => $this->id
            ]
        );
        $res = $sql->run();
        while($row = Query::fetch($res)) {
            $this->versjoner[] = new SamtykkeVersjon($row);
        }
    }

    /**
     * Entiteter er representasjon av samtykkeskjemaet i andre klasser (objekter) som Bilde, Film, Innslag, etc.
     * @return array<string, object>
     */
    public function getEntiteter() {
        if( empty($this->entiteter) ) {
            $this->_loadEntiteter();
        }
        return $this->entiteter;
    }
    private function _loadEntiteter() {
        $sql = new Query("
            SELECT *
            FROM `samtykkeskjema_entitet`
            WHERE `skjema_id` = '#id'",
            [
                'id' => $this->id
            ]
        );
        $res = $sql->run();
        while($row = Query::fetch($res)) {
            try {
                $this->entiteter[$row['entitet_navn']] = new $this->supportedEntiteter[$row['entitet_navn']]($row['entitet_id']);
            } catch(Exception $e) {
                // Ignore error, entiteten er ikke støttet
            }
        }
    }

    public function getJson() {
        $ret = [];
        $lastVersjon = $this->getLastVersion();

        $prosjekter = array_map(function ($p) {
            return [
                'id'             => $p->getId(),
                'navn'           => $p->getNavn(),
                'beskrivelse'    => $p->getBeskrivelse(),
                'arrangement_id' => $p->getArrangementId(),
            ];
        }, $this->getProsjekter());

        return [
            'id'         => $this->getId(),
            'navn'       => $this->getNavn(),
            'prosjekter' => $prosjekter,
            'versjon'    => $lastVersjon ? [
                'id'          => $lastVersjon->getId(),
                'versjon_nr'  => $lastVersjon->getVersjonNr(),
                'beskrivelse' => $lastVersjon->getBeskrivelse(),
                'body_text'   => $lastVersjon->getBodyText(),
                'file_path'   => $lastVersjon->getFilePath(),
            ] : null,
        ];
    }

}
