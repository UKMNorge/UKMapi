<?php

namespace UKMNorge\OAuth2;


use Exception;
use UKMNorge\OAuth2\Interfaces\UserCredentialsInterface;
use UKMNorge\OAuth2\IdentityProvider\Basic\User as IPUser;

// This class is an extension of Pdo to modify the functionality for the users
// This is a storage for the user
class UserPdo extends Pdo implements UserCredentialsInterface {
    

    public function __construct($connection, $config = array()) {
        parent::__construct($connection, array(
            'sms_forward_table' => 'sms_forward',
            'user_IP_table' => 'user_identity_provider'
            )
        );
    }
    /**
     * Accepts a TempUser and saves it into database. Returns a User.
     *
     * @param TempUser $user
     * @param string $password
     * @return bool
     */
    public function createUser(TempUser $user, $password) : bool {
        $tel_country_code = $user->getTelCountryCode();
        $tel_nr = $user->getTelNr();
        $firstName = $user->getFirstName();
        $lastName = $user->getLastName();
        $birthday = $user->getBirthday()->format('Y-m-d');
        
        try {
            $this->validatePassword($password);
        }catch(Exception $e) {
            throw $e;
        }
        

        if($this->userExists($user->getTelNr())) {
            throw new Exception('Ny bruker kan ikke oppretes fordi den eksisterer');
        }
        
        // Delete not verified user with this tel_nr if it is created
        $this->deleteNotVerifiedUser($user->getTelNr());

        // User does not exist, OK
        $password = $this->hashPassword($password);
        $stmt = $this->db->prepare(sprintf('INSERT INTO %s (tel_nr, password, first_name, last_name, tel_nr_verified, birthday, tel_country_code, vilkaar) VALUES (:tel_nr, :password, :firstName, :lastName, false, :birthday, :tel_country_code, false)', $this->config['user_table']));    
        $stmt->execute(compact('tel_nr', 'password', 'firstName', 'lastName', 'birthday', 'tel_country_code'));
        
        return true;
    }

    
    private function deleteNotVerifiedUser(string $tel_nr) : void {
        $stmt = $this->db->prepare(sprintf('DELETE FROM %s WHERE tel_nr = :tel_nr and tel_nr_verified=0', $this->config['user_table']));
        $stmt->execute(compact('tel_nr'));
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
        // The user has not been found
        catch(Exception $e) {
            // Trace down exception
            return false;
        }
    }

    /**
     * Is the user currently logged in?
     * 
     * @return bool
     */
    public function isUserLoggedin() : bool {
        if(!isset($_SESSION)) {
            session_start(); 
        }


        if (isset($_SESSION['valid']) && $_SESSION['valid'] == true) {
            return true;
        }

        return false;
    }

    private function validatePassword($password) : bool {
        if(strlen($password) < 8) {
			throw new Exception("Passordet må inneholde minst 8 tegn");
		}
		if(preg_match('/[a-z]/i', $password) < 1 ) {
			throw new Exception("Passordet må inneholde minst 1 bokstav");
		}
		if(preg_match('/^(?=.*[A-Z])/', $password) < 1) {
			throw new Exception("Passordet må inneholde minst 1 stor bokstav");
		}
		if(preg_match('/(?=.*[0-9])/', $password) < 1) {
			throw new Exception("Passordet må inneholde minst 1 tall"); 
		}
		if(preg_match('/(?=.*[%@$])/', $password) > 0) {
			throw new Exception("Passordet kan ikke inneholde symbolene %@$"); 
		}
        
        return true;
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
     * Change the user's password
     *
     * @param User $user
     * @param string $password
     * @return bool
     */
    public function changePassword(User $user, string $password) {
        try {
            if($this->validatePassword($password)) {
       
                $tel_nr = $user->getTelNr();
                $password = $this->hashPassword($password);
                
                $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET password=:password where tel_nr=:tel_nr', $this->config['user_table']));
                return $stmt->execute(compact('tel_nr', 'password'));
            }
        }catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Change the user's first name
     *
     * @param User $user
     * @param string $password
     * @return bool
     */
    public function changeFirstName(User $user, string $firstName) {
        if(strlen($firstName) < 2) {
            throw new Exception('Navn må ha minst 2 tegn!');
        }

        $tel_nr = $user->getTelNr();
        $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET first_name=:firstName where tel_nr=:tel_nr', $this->config['user_table']));
        return $stmt->execute(compact('tel_nr', 'firstName'));
    }

    /**
     * Change the user's last name
     *
     * @param User $user
     * @param string $password
     * @return bool
     */
    public function changeLastName(User $user, string $lastName) {
        if(strlen($lastName) < 2) {
            throw new Exception('Navn må ha minst 2 tegn!');
        }

        $tel_nr = $user->getTelNr();
        $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET last_name=:lastName where tel_nr=:tel_nr', $this->config['user_table']));
        return $stmt->execute(compact('tel_nr', 'lastName'));
    }

    /**
     * Set the user tel nr to verified
     *
     * @param User $user
     * @return bool
     */
    public function setUserToVerified(string $tel_nr) : bool {
        $trueVal = '1';

        $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET tel_nr_verified=:trueVal where tel_nr=:tel_nr', $this->config['user_table']));
        return $stmt->execute(compact('tel_nr', 'trueVal'));
       
    }

    public function setVilkaarToAccepted(User $user) : bool {
        $trueVal = '1';
        $tel_nr = $user->getTelNr();

        $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET vilkaar=:trueVal where tel_nr=:tel_nr', $this->config['user_table']));
        
        $execute = $stmt->execute(compact('tel_nr', 'trueVal'));
        if(!$execute) {
            throw new Exception('Vilkaar ble ikke lagret!');
        }
        return $execute;
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

    // Add code to database (sms_forward)
    public function addSMSForward(string $telNr, string $generatedCode) : bool {
        
        $stmt = $this->db->prepare(sprintf('REPLACE INTO %s (tel_nr, generated_code, received_code) VALUES (:telNr, :generatedCode, null)', $this->config['sms_forward_table']));    
        return $stmt->execute(compact('telNr', 'generatedCode'));
    }

    // Check if code has been received
    public function checkSMSforward(string $tel_nr, string $generatedCode) : bool {
        $stmt = $this->db->prepare($sql = sprintf('SELECT * from %s where tel_nr=:tel_nr', $this->config['sms_forward_table']));
        $stmt->execute(array('tel_nr' => $tel_nr));

        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if(!$data) {
            return false;
        }

        if($generatedCode == $data['generated_code'] && $data['generated_code'] == $data['received_code']) {
            // Clean
            $this->deleteSMSForward($tel_nr);
            return true;
        }

        return false;
    }

    private function deleteSMSForward(string $tel_nr) : bool {
        $stmt = $this->db->prepare(sprintf('DELETE FROM %s WHERE tel_nr = :tel_nr', $this->config['sms_forward_table']));
        $stmt->execute(compact('tel_nr'));

        return $stmt->rowCount() > 0;
    }

    // Get id of the user by providing tel_nr
    protected function telNrToId(string $telNr) : int {
        $stmt = $this->db->prepare($sql = sprintf('SELECT * from %s where tel_nr=:tel_nr' , $this->config['user_table']));
        $stmt->execute(array('tel_nr' => $telNr));

        $userInfo = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $userInfo['id'];        
    }

    /**
     * Registrer ny bruker gjennom Service Provider. Dette registreres en bruker bare på user_identity_provider
     *
     * @param string $telNr
     * @param string $provider - for eksempel Facebook
     * @param string $IPUser - Identity Provider User (from ...IdentityProvider\Basic\User)
     * @param string $accessToken - Access Token fra provider
     */
    public function registerUserWithServiceProvider(string $userId, string $provider, string $accessToken) : bool {
        $createdDate = date("Y-m-d");

        // Creating new user provider because it does not exist
        if(!$this->userWithProviderExists($userId, $provider)) {
            $stmt = $this->db->prepare(sprintf('REPLACE INTO %s (user_id, provider, provider_user_id, access_token, created) VALUES (null, :provider, :userId, :accessToken, :createdDate)', $this->config['user_IP_table']));
            return $stmt->execute(compact('provider', 'userId', 'accessToken', 'createdDate'));
        }
        
        // Updating the access token only
        $stmt = $this->db->prepare(sprintf('UPDATE %s SET access_token=:accessToken WHERE provider_user_id=:userId AND provider=:provider', $this->config['user_IP_table']));
        $res = $stmt->execute(array(
            'accessToken' => $accessToken,
            'userId' => $userId,
            'provider' => $provider,
        ));
        return $res; 
    }

    /**
     * Sjekk hvis brukeren med bruker id fra provider og provider navn eksisterer
     *
     * @param string $provider - for example 'Facebook'
     * @param string $IPUser - Identity Provider User (from ...IdentityProvider\Basic\User)
     * @return bool
     */
    public function userWithProviderExists(string $userIPID, string $provider) : bool {
        $stmt = $this->db->prepare($sql = sprintf('SELECT * from %s where provider_user_id=:userIPID AND provider=:provider', $this->config['user_IP_table']));
        $stmt->execute(array(
            'userIPID' => $userIPID,
            'provider' => $provider,
        ));
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        return !$data ? false : true;
    }

    /**
     * Hent bruker (User) hvis providerUserId er koblet med en bruker fra oauth_user.
     *
     * @param string $providerUserId - bruker id fra provider
     * @param string $provider - for example 'Facebook'
     * @return User
     */
    public function getUserProvider(string $providerUserId, string $provider) : User {
        $stmt = $this->db->prepare(sprintf('SELECT * from %s where provider_user_id=:providerUserId AND provider=:provider', $this->config['user_IP_table']));
        $stmt->execute(compact('providerUserId', 'provider'));
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $tel_nr = $data['user_id'];

        if($tel_nr) {
            return new User($tel_nr);
        }
        throw new Exception("NOT FOUND: User provider with id " . $providerUserId . ' and provider ' . $provider);
    }

    /**
     * Koble bruker fra provider med en bruker tel_nr fra oauth_user
     *
     * @param string $telNr - bruker id fra oauth_user
     * @param string $providerUserId - bruker id fra provider
     * @param string $provider - for example 'Facebook'
     * @return User
     */
    public function addUserToProvider($telNr, $providerUserId, $provider) {
        $stmt = $this->db->prepare(sprintf('UPDATE %s SET user_id=:telNr WHERE provider_user_id=:providerUserId AND provider=:provider', $this->config['user_IP_table']));
        return $stmt->execute(compact('telNr', 'providerUserId', 'provider'));
    }
    
}
