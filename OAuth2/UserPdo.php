<?php

namespace UKMNorge\OAuth2;


use Exception;

// This class is an extension of Pdo to modify the functionality for the users
// This is a storage for the user
class UserPdo extends Pdo implements UserCredentialsInterface {
    
    /**
     * Accepts a TempUser and saves it into database. Returns a User.
     *
     * @param TempUser $user
     * @param string $password
     * @return User
     */
    public function createUser(TempUser $user, $password) : User {        
        $tel_nr = $user->getTelNr();
        $firstName = $user->getFirstName();
        $lastName = $user->getLastName();


        if($this->userExists($user->getTelNr())) {
            throw new Exception('Ny bruker kan ikke oppretes fordi den eksisterer');
        }
        
        // User does not exist, OK
        $password = $this->hashPassword($password);
        $stmt = $this->db->prepare(sprintf('INSERT INTO %s (tel_nr, password, first_name, last_name, tel_nr_verified) VALUES (:tel_nr, :password, :firstName, :lastName, false)', $this->config['user_table']));    
        $stmt->execute(compact('tel_nr', 'password', 'firstName', 'lastName'));            
        
        return new User($tel_nr);
    }

    /**
     * Update the user data. This can not be used to update the password!
     *
     * @param User $user
     * @return bool
     */
    public function updateUser(User $user) : bool {
        $tel_nr = $user->getTelNr();
        $first_name = $user->getFirstName();
        $last_name = $user->getLastName();

        $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET tel_nr=:tel_nr, first_name=:first_name, last_name=:last_name, tel_nr_verified=:tel_nr_verified where tel_nr=:tel_nr', $this->config['user_table']));
        return $stmt->execute(compact('tel_nr', 'first_name', 'last_name', 'tel_nr_verified'));
    }

    /**
     * @param string $string
     * @param string $password
     * @return bool
     */
    public function checkUserCredentials($tel_nr, $password) : bool {       
        try{
            $user = $this->getUser($tel_nr);
            return $this->checkPassword($user, $password);
        }
        catch(Exception $e) {
            // Trace down exception
            return false;
        }
    }

    public function isUserLoggedin() : bool {
        if(!isset($_SESSION)) { 
            session_start(); 
        }

        if (isset($_SESSION['valid']) && $_SESSION['valid'] == true) {
            return true;
        }

        return false;
    }

    public function getUserLoggedinTelNr() : string {
        if(!isset($_SESSION)) { 
            session_start(); 
        } 

        if (isset($_SESSION['tel_nr'])) {
            return $_SESSION['tel_nr'];
        }

        return '';
    }

    /**
     * Change the user password
     *
     * @param User $user
     * @param string $password
     * @return bool
     */
    public function changePassword(User $user, string $password) {
        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number    = preg_match('@[0-9]@', $password);

        if(!$uppercase || !$lowercase || !$number || strlen($password) < 7) {
            throw new Exception("Passordet er ikke gyldig! Må ha minst 8 tegn og inneholde både tall og store/små bokstaver");
        }

        $tel_nr = $user->getTelNr();
        $password = $this->hashPassword($password);

        $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET password=:password where tel_nr=:tel_nr', $this->config['user_table']));
        return $stmt->execute(compact('tel_nr', 'password'));
    }

    /**
     * Set the user tel nr to verified
     *
     * @param User $user
     * @return bool
     */
    public function setVerifiedTelNr(User $user) : bool {
        $tel_nr = $user->getTelNr();
        $trueVal = '1';

        $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET tel_nr_verified=:trueVal where tel_nr=:tel_nr', $this->config['user_table']));
        return $stmt->execute(compact('tel_nr', 'trueVal'));
       
    }

    // Is tel_nr verified
    public function isTelNrVerified(string $tel_nr) : bool {
        $user = new User($tel_nr); // Throws exception if the user does not exist
        return $user->isTelNrVerified(); 
    }

    public function userExists(string $tel_nr) : bool {
        try{
            $this->getUser($tel_nr);
            return true;
        }
        catch(Exception $e) {
            return false;
        }
    }

}

?>