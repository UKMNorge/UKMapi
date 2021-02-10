<?php

namespace UKMNorge\Oauth2\IdentityProvider\Interfaces;

interface User
{
    /**
     * Opprett nytt brukerobjekt
     *
     * @param string $id
     * @param string $first_name
     * @param string $last_name
     */
    public function __construct(string $id, string $first_name, string $last_name);

    /**
     * Hent brukerens ID
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Hent brukerens fornavn
     *
     * @return string
     */
    public function getFirstName(): string;

    /**
     * Hent brukerens etternavn
     *
     * @return string
     */
    public function getLastName(): string;
}
