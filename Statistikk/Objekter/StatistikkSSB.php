<?php

namespace UKMNorge\Statistikk\Objekter;
use Exception;

class StatistikkSSB {


    /**
     * Henter aldersfordeling for en kommune fra SSB API
     *
     * Alder fra 10 til 25 blir hentet for menn og kvinner som er fusjonert i en enkelt array.
     * 
     * @param int $kommuneId
     * @param int $season
     * @return void
     */
    public static function getAldersfordelingKommune($kommuneId, int $season) : array {
        // URL til SSB API for Statistikkbanken
        $url = "https://data.ssb.no/api/v0/no/table/07459";

        // JSON query med spesifikk kommune
        $query = [
            "query" => [
                [
                    "code" => "Region",
                    "selection" => [
                        "filter" => "item",
                        "values" => [
                            $kommuneId,  // kommune id
                        ]
                    ]
                ],
                [
                    "code" => "Kjonn",
                    "selection" => [
                        "filter" => "item",
                        "values" => [
                            "1",  // Menn
                            "2"   // Kvinner
                        ]
                    ]
                ],
                [
                    "code" => "Alder",
                    "selection" => [
                        "filter" => "item",
                        // Alder
                        "values" => [
                            "010",
                            "011",
                            "012",
                            "013",
                            "014",
                            "015",
                            "016",
                            "017",
                            "018",
                            "019",
                            "020",
                            "021",
                            "022",
                            "023",
                            "024",
                            "025",
                        ]
                    ]
                ],
                [
                    "code" => "ContentsCode",
                    "selection" => [
                        "filter" => "item",
                        "values" => [
                            "Personer1"  // Antall personer
                        ]
                    ]
                ],
                [
                    "code" => "Tid",
                    "selection" => [
                        "filter" => "item",
                        "values" => [
                            $season  // Sesong
                        ]
                    ]
                ]
            ],
            "response" => [
                "format" => "json-stat2"
            ]
        ];

        // Konverterer query til JSON-format
        $jsonQuery = json_encode($query);

        // Initialiserer en cURL-session
        $ch = curl_init($url);

        // Setter cURL-innstillinger for å sende en POST-forespørsel med JSON-data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonQuery)
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonQuery);

        // Utfører forespørselen og henter responsen
        $response = curl_exec($ch);

        // Sjekker om cURL-forespørselen var vellykket
        if ($response === false) {
            throw new Exception("cURL error: " . curl_error($ch));
        }

        // Dekoder JSON-responsen
        $data = json_decode($response, true);

        // Sjekk for feil i responsen
        if (isset($data['error'])) {
            throw new Exception("SSB API error: " . $data['error']['message']);
        }

        // Henter dimensjoner
        $dimensions = $data['dimension'];
        $values = $data['value'];

        // Henter indekser for dimensjoner
        $ageIndex = $dimensions['Alder']['category']['index'];
        $ageLabels = $dimensions['Alder']['category']['label'];
        $ageSize = count($ageLabels);

        // Beregner antall personer for hver alder
        $ageCounts = array_fill_keys(array_keys($ageLabels), 0);

        $indexSize = $dimensions['Region']['size'][0] * $dimensions['Kjonn']['size'][0] * $dimensions['Alder']['size'][0] * $dimensions['Tid']['size'][0];
        for ($i = 0; $i < count($values); $i++) {
            $ageIdx = ($i % $ageSize);
            $ageKey = array_keys($ageLabels)[$ageIdx];
            $ageCounts[$ageKey] += $values[$i];
        }

        $retArr = [];
        foreach ($ageCounts as $ageCode => $count) {
            $retArr[intval($ageCode)] = ['age' => intval($ageCode), 'antall' => $count];
        }

        // Lukker cURL-sessionen
        curl_close($ch);

        return $retArr;
    }
}