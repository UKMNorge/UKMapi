<?php

namespace UKMNorge\Arrangement;


class Aktuelle extends Kommende
{
    /**
     * @var MONTHS_THRESHOLD Int antall måneder før ikke lenger aktuelt
     */
    const MONTHS_THRESHOLD = 12;
}
