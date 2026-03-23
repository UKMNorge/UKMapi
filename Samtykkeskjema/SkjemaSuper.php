<?php

namespace UKMNorge\Samtykkeskjema;

use Exception;

/**
 * Abstract superclass representerer et skjema generelt.
 *
 * Provides the common identity/naming contract and the int-or-array
 * constructor dispatch that both concrete classes use.
 */
abstract class SkjemaSuper {

    protected string $id;
    protected string $navn = '';
    
    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getNavn(): string {
        return $this->navn;
    }

    public function isAnswered($userId) : bool {
        return false;
    }

    public function isGodkjent($userId) : bool {
        return false;
    }

}
