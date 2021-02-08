<?php

namespace UKMNorge\OAuth2\Interfaces;

use \OAuth2\storage\UserCredentialsInterface as BshafferUserCredentialsInterface;


/**
 *
 * @author 
 */
interface UserCredentialsInterface extends BshafferUserCredentialsInterface
{

    // Returns true if the user is logged in
    public function isUserLoggedin() : bool;

    // Returns the tel_nr of the logged in user
    public function getUserLoggedinTelNr() : string;
} 
