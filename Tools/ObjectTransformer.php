<?php

namespace UKMNorge\Tools;

use UKMNorge\Arrangement\Arrangement;

class ObjectTransformer {
    
    public static function arrangement(Arrangement $arrangement) : array{
        return [
            'id' => $arrangement->getId(),
            'navn' => $arrangement->getNavn(),
            'url' => $arrangement->getLink(),
            'start' => $arrangement->getStart()->getTimestamp()
        ];
    }

}