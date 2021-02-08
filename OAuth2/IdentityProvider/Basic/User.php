<?php

namespace UKMNorge\OAuth2\IdentityProvider\Basic;

use UKMNorge\Oauth2\IdentityProvider\Interfaces\User as UserInterface;

class User implements UserInterface
{

    private $id;
    private $first_name;
    private $last_name;

    /**
     * Opprett nytt brukerobjekt
     *
     * @param string $id
     * @param string $first_name
     * @param string $last_name
     */
    public function __construct(string $id, string $first_name, string $last_name)
    {
        $this->id = $id;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
    }

    /**
     * Hent brukerens ID
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Hent brukerens fornavn
     *
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->first_name;
    }


    /**
     * Hent brukerens etternavn
     *
     * @return string
     */
    public function getLastName(): string
    {
        return $this->last_name;
    }
}
