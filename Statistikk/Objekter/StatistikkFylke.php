<?php

namespace UKMNorge\Statistikk\Objekter;

use UKMNorge\Database\SQL\Query;
use UKMNorge\Statistikk\Objekter\StatistikkSuper;
use UKMNorge\Statistikk\Objekter\StatistikkKommune;
// use UKMNorge\Statistikk\StatistikkManager;
use UKMNorge\Geografi\Fylke;
use UKMNorge\Innslag\Typer\Typer;
use UKMNorge\API\SSB\Klass;




use Exception;
use DateTime;


class StatistikkFylke extends StatistikkSuper {
    private Fylke $fylke;
    // private StatistikkManager $sm;
    private int $season;

    public function __construct(Fylke $fylke, $season) {
        // $this->sm = new StatistikkManager();
        $this->fylke = $fylke;
        $this->season = $season;
    }

    /**
    * Returnerer antall unike deltakere på kommune nivå i fylke
    *
    * @return int antall unike deltakere.
    */
    public function getAntallUnikeDeltakere() : int {
        return $this->runAntall(true);
    }

    /**
    * Returnerer antall IKKE UNIKE deltakere på kommune nivå i fylke
    *
    * @return int antall deltakere.
    */
    public function getAntallDeltakere() : int {
        return $this->runAntall();
    }

    /**
    * Returnerer antall uregistrerte deltakere på kommune nivå i fylke
    *
    * @return int antall uregistrerte deltakere.
    */
    public function getAntallUregistrerteDeltakere() : int {
        $sql = new Query(
            "SELECT 
                SUM(pl_missing) AS antall 
            FROM
                smartukm_place
            WHERE season='#season'
            AND pl_owner_fylke = '#fylke_id'",
            [
                'season' => $this->season,
                'fylke_id' => $this->fylke->getId(),
            ]
        );

        $res = $sql->run('array');
        return (int) intval($res['antall']);
    }

    /**
    * Returnerer antall uregistrerte deltakere i fylkesfestivaler
    *
    * @return int antall uregistrerte deltakere.
    */
	
    public function getAntallUregistrerteDeltakereFylke() : int {
        $sql = new Query(
            "SELECT 
                SUM(pl_missing) AS antall 
            FROM
                smartukm_place
            WHERE season='#season'
            AND pl_owner_fylke = '#fylke_id'
            AND pl_type = 'fylke'",
            [
                'season' => $this->season,
                'fylke_id' => $this->fylke->getId(),
            ]
        );

        $res = $sql->run('array');
        return (int) intval($res['antall']);
    }

    /**
    * Returnerer antall unike deltakere på fylkesarrangementer i fylke
    *
    * @return int antall unike deltakere.
    */
    public function getAntallUnikeDeltakereFylke() : int {
        return $this->runAntall(false, true);
    }

    /**
    * Returnerer antall IKKE UNIKE deltakere på fylkesarrangementer i fylke
    *
    * @return int antall deltakere.
    */
    public function getAntallDeltakereFylke() : int {
        return $this->runAntall(false, true);
    }


    private function runAntall($unique = false, bool $fylkeArrangementer = false) : int {
        $select = $unique ? "COUNT(DISTINCT p_id)" : "COUNT(p_id)";
        $sql = new Query(
            "SELECT " . $select . " as antall
            FROM (
                " . ($fylkeArrangementer == true ? $this->getQueryFylkeFylkesarrangementer($this->season) : $this->getQueryFylke($this->season)) . "
            ) AS subquery;",
            [
                'fylke_id' => $this->fylke->getId(),
                'season' => $this->season,
                'kommuner_ids' => implode(',', $this->getKommunerIds())
            ]
        );   

        $res = $sql->run('array');
        return (int) intval($res['antall']);
    }

    /**
    * Returnerer gjennomsnitt av UNIKE deltakere i arrangemeter 
    * Dette går gjennom alle kommuner og beregner gjennomsnittet
    *
    * OBS: Det tas ikke i beregning fylkesarrangementer
    * OBS: Arrangementer som har 0 deltakere blir ikke tatt med i beregningen
    *
    * @return int antall unike deltakere.
    */
    public function getGjennomsnittDeltakereIArrangementer($season) : int {
        $sql = new Query("
            WITH ParticipantCount AS (
                SELECT 
                    arrangement.pl_id,
                    COUNT(DISTINCT innslag_person.p_id) AS participant_count
                FROM
                    statistics_before_2024_smartukm_rel_pl_k AS arr_kommune
                    JOIN statistics_before_2024_smartukm_place AS arrangement ON arrangement.pl_id = arr_kommune.pl_id
                    JOIN statistics_before_2024_smartukm_rel_pl_b AS arr_innslag ON arr_innslag.pl_id = arrangement.pl_id
                    JOIN statistics_before_2024_smartukm_rel_b_p AS innslag_person ON innslag_person.b_id = arr_innslag.b_id
                    JOIN statistics_before_2024_smartukm_band AS innslag ON innslag.b_id = arr_innslag.b_id
                    JOIN smartukm_kommune AS kommune ON kommune.id = arr_kommune.k_id
                WHERE 
                    kommune.idfylke = '#fylke_id' 
                    AND arrangement.season = '#season' 
                    AND (innslag.b_status = 8 OR innslag.b_status = 99)
                GROUP BY 
                    arrangement.pl_id
                
                UNION ALL
                
                SELECT 
                    usf.pl_id,
                    COUNT(DISTINCT usf.p_id) AS participant_count
                FROM
                    ukm_statistics_from_2024 AS usf
                    JOIN smartukm_kommune AS kommune ON kommune.id = usf.k_id
                WHERE 
                    kommune.idfylke = '#fylke_id' 
                    AND usf.season = '#season'
                GROUP BY 
                    usf.pl_id
            )
            SELECT 
                AVG(participant_count) AS average_p
            FROM 
                ParticipantCount
            WHERE 
                pl_id IS NOT NULL;
        ", [
            'fylke_id' => $this->fylke->getId(),
            'season' => $season
        ]);

        $res = $sql->run('array');
        return (int) intval($res['average_p']);
    }

    /**
     * Gjennomsnitt deltaekre per fylke
     * 
     * Eksempel: 120 deltakere i Agder
     * 
     * @return string SQL spørring
     */
    public function getGjennomsnittDeltakereIFylke($season) : int {
        $sql = new Query("
            SELECT COUNT(distinct p_id) as antall
            FROM (
                SELECT 
                    innslag_person.p_id AS p_id
                FROM
                    statistics_before_2024_smartukm_rel_pl_k AS arr_kommune
                    JOIN statistics_before_2024_smartukm_place AS arrangement ON arrangement.pl_id = arr_kommune.pl_id
                    JOIN statistics_before_2024_smartukm_rel_pl_b AS arr_innslag ON arr_innslag.pl_id = arrangement.pl_id
                    JOIN statistics_before_2024_smartukm_rel_b_p AS innslag_person ON innslag_person.b_id = arr_innslag.b_id
                    JOIN statistics_before_2024_smartukm_band AS innslag ON innslag.b_id = arr_innslag.b_id
                    JOIN smartukm_kommune AS kommune ON kommune.id = arr_kommune.k_id
                WHERE 
                    kommune.idfylke = '#fylke_id' 
                    AND arrangement.season = '#season' 
                    AND (innslag.b_status = 8 OR innslag.b_status = 99)
          
                
                UNION ALL
                
                SELECT 
                    usf.p_id AS p_id
                FROM
                    ukm_statistics_from_2024 AS usf
                    JOIN smartukm_kommune AS kommune ON kommune.id = usf.k_id
                WHERE 
                    kommune.idfylke = '#fylke_id' 
                    AND usf.season = '#season'
            ) as sub
        ", [
            'fylke_id' => $this->fylke->getId(),
            'season' => $season
        ]);

        $res = $sql->run('array');
        return (int) intval($res['antall']);
    }

    
    /**
     * Returnerer antall UNIKE deltakere fordelt på alder i fylke
     * Det velges alle participant fra alle arrangement i fylke i en sesong.
     * 
     * OBS: det brukes sesong år og 31. desember som dato når deltakere deltok i arrangementet.
     * 
    * @return array[] An array of arrays with keys 'age' and 'antall'.
    */
    public function getAldersfordeling() : array {
        $seasonDate = new DateTime($this->season.'-12-31');

        $sql = new Query(
            "SELECT 
                age, 
                COUNT(*) AS participant_count 
            FROM (SELECT 
                DISTINCT participant.p_id, 
                participant.p_dob,
                TIMESTAMPDIFF(YEAR, 
                    FROM_UNIXTIME(participant.p_dob),
                    FROM_UNIXTIME(#dateSeas))
                AS age
            FROM (
                " . $this->getQueryFylke($this->season) . "
            ) AS subquery
                JOIN statistics_before_2024_smartukm_participant AS participant
                ON subquery.p_id = participant.p_id
                ) AS age_subquery
                GROUP BY 
                    age
                ORDER BY 
                    age;
                ",
                [
                    'fylke_id' => $this->fylke->getId(),
                    'season' => $this->season,
                    'dateSeas' => $seasonDate->getTimestamp(),
                    'kommuner_ids' => implode(',', $this->getKommunerIds())
                ]
        );

        $retArr = [];
        $res = $sql->run();

        while($row = Query::fetch($res)) {
            $retArr[] = [
                'age' => $row['age'],
                'antall' => $row['participant_count']
            ];
        }

        return $retArr;
    }

    /**
     * Returnerer antall innslag i fylke fordelt på sjanger
     * 
     * OBS: Det hentes innslag kun fra arrangementer på kommunenivå i et fylke
     * 
     * @param int $excludePlId - arrangementet som skal ekskluderes fra statistikken. Vanligvis kan pl_id brukes av arrangementet hvor statistikk hentes fra
     * @return array[] An array of arrays with keys 'antall' and 'type_navn'.
    */
    public function getSjangerFordeling(int $excludePlId=-1) : array {
        if($excludePlId == null) {
            $excludePlId = -1;
        }

        // > 2019 innslag fra ukm_rel_arrangement_person og fra juli 2024 brukes tabellen ukm_statistics_from_2024
        if($this->season > 2019) {
            $sql = new Query("SELECT 
                DISTINCT innslag.b_id, 
                innslag.bt_id, 
                innslag.b_kategori, 
                arrpers.arrangement_id
            FROM statistics_before_2024_ukm_rel_arrangement_person AS arrpers 
            JOIN statistics_before_2024_smartukm_band AS innslag ON innslag.b_id=arrpers.innslag_id 
            JOIN statistics_before_2024_smartukm_place AS place ON place.pl_id=arrpers.arrangement_id
            JOIN statistics_before_2024_smartukm_rel_pl_k AS rel_kommune ON rel_kommune.pl_id=place.pl_id
            JOIN smartukm_kommune AS kommune ON kommune.id=rel_kommune.k_id
            WHERE kommune.id IN (#kommuner_ids) 
                AND place.season='#season' 
                AND innslag.b_status = 8 
                AND arrpers.arrangement_id!='#plId'

            UNION 

            SELECT DISTINCT 
                stat.b_id as b_id, 
                stat.bt_id as bt_id, 
                stat.b_kategori as b_kategori, 
                stat.pl_id as arrangement_id
            FROM ukm_statistics_from_2024 AS stat 
            WHERE stat.f_id='#fylkeId' 
                AND stat.fylke='false'
                AND stat.season='#season' 
                AND stat.innslag_status = 8 
                AND stat.pl_id!='#plId'",
                [
                    'fylkeId' => $this->fylke->getId(),
                    'plId' => $excludePlId, // exclude arrangementet if needed
                    'season' => $this->season,
                    'kommuner_ids' => implode(',', $this->getKommunerIds())
                ]
            );
        }
        // Before 2019 brukes statistics_before_2024_smartukm_rel_pl_b tabell
        else {
            $sql = new Query("SELECT
                    DISTINCT innslag.b_id,
                    innslag.bt_id,
                    innslag.b_kategori,
                    rel_pl_b.pl_id
                FROM 
                    statistics_before_2024_smartukm_band AS innslag
                JOIN statistics_before_2024_smartukm_rel_pl_b AS rel_pl_b ON rel_pl_b.b_id = innslag.b_id
                JOIN statistics_before_2024_smartukm_place as place on place.pl_id=rel_pl_b.pl_id
                JOIN statistics_before_2024_smartukm_rel_pl_k AS rel_kommune ON rel_kommune.pl_id=place.pl_id
                JOIN smartukm_kommune AS kommune ON kommune.id=rel_kommune.k_id
                WHERE kommune.id IN (#kommuner_ids)
                AND (innslag.b_status = 8 OR innslag.b_status = 99)
                AND place.season='#season'
                AND place.pl_id!='#plId'", // excluded arrangementet
                [
                    'fylkeId' => $this->fylke->getId(),
                    'plId' => $excludePlId, // exclude arrangementet if needed
                    'season' => $this->season,
                    'kommuner_ids' => implode(',', $this->getKommunerIds())
                ]
            );
        }

        $retArr = [];
        $innslagArr = [];
        $typeArr = [];
        $res = $sql->run();


        while($row = Query::fetch($res)) {
            try{
                $type = Typer::getById($row['bt_id'], $row['b_kategori']);
                $innslagArr[$type->getKey()][] = $row['b_id'];
                $typeArr[$type->getKey()][] = $type->getNavn();
            }catch(Exception $e) {
                // The type is not found
                if($e->getCode() == 110002) {
                    $innslagArr['ukjent'][] = $row['b_id'];
                    $typeArr['ukjent'][] = 'Ukjent';
                }
            }
        }

        foreach($innslagArr as $key => $value) {
            $retArr[$key]['antall'] = count($value);
            $retArr[$key]['type_navn'] = $typeArr[$key][0];
        }

        return $retArr;
    }

    /**
     * Returnerer antall deltakere i fylke fordelt på kjønn
     *
     * Det brukes navn for å identifisere kjønn
     * 
     * @return array[]
    */
    public function getKjonnsfordeling() {
        $sql = new Query(
            "SELECT 
                p_id, 
                firstname 
            FROM(
                    SELECT 
                        participant.p_id, 
                        participant.p_firstname AS firstname 
                    FROM(
                        " . $this->getQueryFylke($this->season) . "
                    ) AS subquery 
                    JOIN  `smartukm_participant` AS participant ON participant.p_id = subquery.p_id
            ) AS subqueryOut 
            GROUP BY p_id;",
            [
                'fylke_id' => $this->fylke->getId(),
                'season' => $this->season,
                'kommuner_ids' => implode(',', $this->getKommunerIds())
            ]
        );



        $retArr = [];
        $res = $sql->run();

        // For each result from $sql call getKjonnByName()
        while($row = Query::fetch($res)) {
            $kjonn = $this->getKjonnByName($row['firstname']);
            $retArr[$kjonn] = 1 + ($retArr[$kjonn] ?? 0);
        }


        return $retArr;
    }

    /**
     * Returnerer alle kommuner i fylke som har aktivitet (har minst 1 arrangement)
     * 
     * @return string SQL spørring
     */

    public function getKommunerAktivitet() {
        $retArr = [];
        $retArr['season'] = $this->season;
        $alleKommunerIFylke = StatistikkKommune::getAlleKommunerFraSSB($this->season, $this->fylke);
        
        foreach($alleKommunerIFylke as $kommune) {
            $retArr['kommuner'][$kommune->getId()] = ['navn' => $kommune->getNavn(), 'aktivitet' => false];
        }

        $sql = new Query(
            "SELECT DISTINCT kommune_id, kommune_navn
            FROM (
                -- Before sommer 2024
                SELECT kommune.id AS kommune_id, kommune.name AS kommune_navn
                FROM smartukm_kommune AS kommune
                JOIN statistics_before_2024_smartukm_rel_pl_k AS pl_k ON pl_k.k_id = kommune.id
                WHERE kommune.idfylke='#fylke_id' AND pl_k.season='#season'

                UNION
                -- sommer 2024 and later
                SELECT stat.k_id AS kommune_id, kommune.name AS kommune_navn
                FROM ukm_statistics_from_2024 AS stat
                JOIN smartukm_kommune AS kommune ON kommune.id=stat.k_id
                WHERE f_id='#fylke_id' 
                    AND season='#season' 
                    AND fylke='false' 
                    AND land='false'
                    AND innslag_status=8
            ) AS combined_results
            GROUP BY kommune_id",
            [
                'fylke_id' => $this->fylke->getId(),
                'season' => $this->season
            ]
        );

        $res = $sql->run();
        

        while($row = Query::fetch($res)) {
            $retArr['kommuner'][$row['kommune_id']] = ['navn' => $row['kommune_navn'], 'aktivitet' => true];
        }

        return $retArr;
    }

    public function getAntallArrangementer() : int {
        return static::antallArrangementerIFylke($this->fylke->getId(), $this->season);
    }

    /**
     * Returnerer antall arrangementer i kommuner i fylke i en sesong 
     *
     * Static metode ble brukt for å ha tilgang til gamle fylker uten å sende som Fylke objekt til denne klassen. Gamle fylker finnes ikke lenger i systemet men de er lagret i databasen.
     * 
     * @return int antall arrangementer.
     */
    static function antallArrangementerIFylke(string $fylkeId, int $season) : int {
        $sql = new Query("
            SELECT COUNT(DISTINCT pl_id) AS antall FROM (
                SELECT pl_k.pl_id AS pl_id
                FROM smartukm_kommune AS kommune
                JOIN statistics_before_2024_smartukm_rel_pl_k AS pl_k ON pl_k.k_id = kommune.id
                WHERE kommune.idfylke='#fylke_id' AND pl_k.season='#season'

               UNION
                -- sommer 2024 and later
                SELECT stat.pl_id AS pl_id
                FROM ukm_statistics_from_2024 AS stat
                JOIN smartukm_kommune AS kommune ON kommune.id=stat.k_id
                WHERE f_id='#fylke_id' AND season='#season' AND fylke='false' AND land='false'
	        ) AS combinedUnion
        ",
        // OBS: Det sjekkes ikke om innslag har status 8 fordi det hentes kun arrangementer som er opprettet og som finnes i ukm_statistics_from_2024 tabellen
        [
            'fylke_id' => $fylkeId,
            'season' => $season
        ]);


        $res = $sql->run('array');
        return (int) intval($res['antall']);
        
    }

    /**
     * Returnerer id av alle fylker hentet fra SSB i en sesong
     *
     * @return array[] An array of arrays with key 'id' and value 'navn'.
     */
    public static function getAlleFylkeIdFraSSB(int $season) : array {
        // Hent alle kommuner fra SSB
        $dataset = new Klass();
        // 104 er "Standard for fylkesinndeling"
        $dataset->setClassificationId("104");
        $startDato = new DateTime($season."-01-01");
        $sluttDato = new DateTime($season."-12-31");

        $dataset->setRange($startDato, $sluttDato);
        $dataset->includeFutureChanges(true);
        $fylker = $dataset->getCodes();
        
        $SSBFylker = [];
        foreach($fylker->codes as $fylke) {
            // Viken before 2024 - i vår database er lagret som Akershus, Buskerud og Østfold
            if($fylke->code == '30') {
                $SSBFylker['31'] = $fylke->name . ' Østfold';
                $SSBFylker['32'] = $fylke->name . ' Akershus';
                $SSBFylker['33'] = $fylke->name . ' Buskerud';
            }

            // Vestfold og Telemark before 2024 - i vår database er lagret som Vestfold og Telemark
            if($fylke->code == '38') {
                $SSBFylker['39'] = $fylke->name . ' Vesfold';
                $SSBFylker['40'] = $fylke->name . ' Telemark';
            }

            // Troms og Finnmark before 2024 - i vår database er lagret som Troms og Finnmark
            if($fylke->code == '54') {
                $SSBFylker['55'] = $fylke->name . ' Troms - Romsa - Tromssa';
                $SSBFylker['56'] = $fylke->name . ' Finnmark - Finnmárku - Finmarkku';
            }

            // I 2018 ble Sør og Nord Trøndelag delt i Trøndelag men i systemet ble de lagret som Sør og Nord Trøndelag
            if($season == 2018 || $season == 2019) {
                if($fylke->code == '50') {
                    $SSBFylker['16'] = $fylke->name . ' Sør-Trøndelag';
                    $SSBFylker['17'] = $fylke->name . ' Nord-Trøndelag';
                }
            }

            $SSBFylker[$fylke->code] = $fylke->name;
        }

        return $SSBFylker;
    }

    /**
     * Hent alle arrangementer arrangert i fylke i en sesong
     *
     * @return array[] An array with pl_id
     */
    public function getFylkesarrangementerIds() {
        $sql = new Query("
            SELECT pl_id
            FROM statistics_before_2024_smartukm_place AS place
            WHERE place.pl_type='fylke' 
            AND season='#season'
            AND (place.old_pl_fylke='#fylke_id' OR place.pl_owner_fylke='#fylke_id')

            UNION

            SELECT pl_id
            FROM ukm_statistics_from_2024
            WHERE fylke=true
            AND season='#season'
            AND f_id='#fylke_id'
            ",
            // OBS: Det sjekkes ikke om innslag har status 8 fordi det hentes kun arrangementer som er opprettet og som finnes i ukm_statistics_from_2024 tabellen
            [
                'fylke_id' => $this->fylke->getId(),
                'season' => $this->season
            ]
        );

        $retArr = [];
        $res = $sql->run();

        // For each result from $sql call getKjonnByName()
        while($row = Query::fetch($res)) {
            $retArr[$row['pl_id']] = $row['pl_id'];
        }

        return $retArr;
    }

    /**
     * Hent antall innslag i fylke i en sesong
     *
     * @return int antall innslag
     */
    public function getAntallInnslag() : int {
        $sql = new Query("
            SELECT COUNT(DISTINCT b_id) AS antall
            FROM (
                SELECT innslag.b_id AS b_id
                FROM smartukm_band AS innslag
                JOIN smartukm_rel_pl_b AS pl_b ON pl_b.b_id = innslag.b_id
                JOIN statistics_before_2024_smartukm_rel_pl_k AS pl_k ON pl_k.pl_id = pl_b.pl_id
                JOIN smartukm_place AS arrangement ON arrangement.pl_id = pl_k.pl_id
                JOIN smartukm_kommune AS kommune ON kommune.id = pl_k.k_id
                WHERE arrangement.season='#season'
                AND (innslag.b_status = 8 OR innslag.b_status = 99)
                AND kommune.idfylke='#fylke_id'

                UNION 

                SELECT b_id AS b_id
                FROM ukm_statistics_from_2024
                WHERE fylke=false AND land=false
                AND f_id='#fylke_id'
                AND season='#season'
                AND innslag_status=8
                GROUP BY p_id, b_id
            ) AS combined_query",
            [
                'fylke_id' => $this->fylke->getId(),
                'season' => $this->season
            ]
        );

        $res = $sql->run('array');
        return (int) intval($res['antall']);
    }

    public static function getGjennomsnittAldersfordelingIAlleFylker(int $fra, int $til) : array {
        $retArr = [];
        $sql = new Query("
            SELECT year, AVG(gjennomsnitt) AS avg_gjennomsnitt
            FROM ukm_statistikk_static_aldersfordeling
            WHERE year >= '#fra' AND year <= '#til'
            GROUP BY year
            ORDER BY year",
            [
                'fra' => $fra,
                'til' => $til
            ]
        );

        $res = $sql->run();
        while($row = Query::fetch($res)) {
            $retArr[$row['year']] = number_format($row['avg_gjennomsnitt'], 1);
        }

        return $retArr;
    }

    /**
     * Hent alle kommuner som er i fylke og tiligerere fylke versjoner
     *
     * @return array[string] kommune id-er
     */
    private function getKommunerIds() : array {
        $sql = new Query("
            SELECT k_id
            FROM ukm_kommune_fylke_fusjonering
            WHERE ny_fylke_id='#fylke_id'
            ",
            [
                'fylke_id' => $this->fylke->getId()
            ]
        );

        $res = $sql->run();
        
        $retArr = [];
        while($row = Query::fetch($res)) {
            $retArr[] = $row['k_id'];
        }

        return $retArr;
    }
}
