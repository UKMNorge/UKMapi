<?php

namespace UKMNorge\Arrangement;


class Kommende extends Load
{
    
    /**
     * Hent alle kommende arrangement i hele Norge
     *
     * @param Filter $filter
     * @return Arrangementer
     */
    public static function getAllCollection(Filter $filter = null)
    {
        if (is_null($filter)) {
            $filter = new Filter();
        }
        return new Arrangementer('alle', 0, $filter);
    }
}
