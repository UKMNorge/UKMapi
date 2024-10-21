<?php

namespace UKMNorge\Statistikk\Objekter;
use Exception;

class StatistikkSSB {


    public static function getAldersfordelingKommune($kommuneId, int $season): array {
        $url = "https://data.ssb.no/api/v0/no/table/07459";
    
        // JSON query for age distribution
        $queryAgeDistribution = [
            "query" => [
                [
                    "code" => "Region",
                    "selection" => [
                        "filter" => "item",
                        "values" => [
                            $kommuneId,
                        ]
                    ]
                ],
                [
                    "code" => "Kjonn",
                    "selection" => [
                        "filter" => "item",
                        "values" => [
                            "1", // Men
                            "2"  // Women
                        ]
                    ]
                ],
                [
                    "code" => "Alder",
                    "selection" => [
                        "filter" => "item",
                        "values" => [
                            "010", "011", "012", "013", "014", "015", "016", "017", "018", "019", "020", "021"
                        ]
                    ]
                ],
                [
                    "code" => "ContentsCode",
                    "selection" => [
                        "filter" => "item",
                        "values" => [
                            "Personer1" // Number of persons
                        ]
                    ]
                ],
                [
                    "code" => "Tid",
                    "selection" => [
                        "filter" => "item",
                        "values" => [
                            $season
                        ]
                    ]
                ]
            ],
            "response" => [
                "format" => "json-stat2"
            ]
        ];
    
        // Add another query to get total population data
        $queryTotalPopulation = [
            "query" => [
                [
                    "code" => "Region",
                    "selection" => [
                        "filter" => "item",
                        "values" => [
                            $kommuneId,
                        ]
                    ]
                ],
                [
                    "code" => "Kjonn",
                    "selection" => [
                        "filter" => "item",
                        "values" => [
                            "1", // Men
                            "2"  // Women
                        ]
                    ]
                ],
                [
                    "code" => "Alder",
                    "selection" => [
                        "filter" => "all",
                        "values" => []
                    ]
                ],
                [
                    "code" => "ContentsCode",
                    "selection" => [
                        "filter" => "item",
                        "values" => [
                            "Personer1" // Number of persons
                        ]
                    ]
                ],
                [
                    "code" => "Tid",
                    "selection" => [
                        "filter" => "item",
                        "values" => [
                            $season
                        ]
                    ]
                ]
            ],
            "response" => [
                "format" => "json-stat2"
            ]
        ];
    
        // Function to perform cURL request
        $ageData = self::performCurlRequest($url, $queryAgeDistribution);
        // $totalPopulationData = self::performCurlRequest($url, $queryTotalPopulation);
    
        // Process the data for age distribution
        $ageCounts = self::processAgeDistribution($ageData);
        // $totalPopulation = self::processTotalPopulation($totalPopulationData);
    
        return [
            'age_distribution' => $ageCounts,
            'total_population' => 0
        ];
    }
    
    private static function performCurlRequest($url, $query): array {
        $jsonQuery = json_encode($query);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonQuery)
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonQuery);
    
        $response = curl_exec($ch);
        if ($response === false) {
            throw new Exception("cURL error: " . curl_error($ch));
        }
    
        $data = json_decode($response, true);
        curl_close($ch);
    
        if (isset($data['error'])) {
            throw new Exception("SSB API error: " . $data['error']['message']);
        }
    
        return $data;
    }
    
    private static function processAgeDistribution($data): array {
        $dimensions = $data['dimension'];
        $values = $data['value'];
    
        $ageLabels = $dimensions['Alder']['category']['label'];
        $ageCounts = array_fill_keys(array_keys($ageLabels), 0);
    
        $ageSize = count($ageLabels);
        for ($i = 0; $i < count($values); $i++) {
            $ageIdx = ($i % $ageSize);
            $ageKey = array_keys($ageLabels)[$ageIdx];
            $ageCounts[$ageKey] += $values[$i];
        }
    
        $retArr = [];
        foreach ($ageCounts as $ageCode => $count) {
            $retArr[intval($ageCode)] = ['age' => intval($ageCode), 'antall' => $count];
        }
    
        return $retArr;
    }
    
    private static function processTotalPopulation($data): int {
        return array_sum($data['value']);
    }
    
}