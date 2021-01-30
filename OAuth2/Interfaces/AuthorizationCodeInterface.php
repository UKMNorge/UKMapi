<?php

namespace UKMNorge\OAuth2\Interfaces;

use \OAuth2\storage\AuthorizationCodeInterface as BshafferAuthorizationCodeInterface;


/**
 *
 * @author 
 */
interface AuthorizationCodeInterface extends BshafferAuthorizationCodeInterface
{

    // Returns true if the user is logged in
    public function isUserLoggedin() : bool;

    // Returns the tel_nr of the logged in user
    public function getUserLoggedinTelNr() : string;


    public function getUserDetails($tel_nr);
} 
