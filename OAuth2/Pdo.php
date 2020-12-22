<?php

namespace UKMNorge\OAuth2;

use Exception;
use OAuth2\Storage\Pdo as BshafferPdo;

class Pdo extends BshafferPdo {
    
    // Password hashing algorithm (PHP native algorithm)
    // Override
    protected function hashPassword($password)
    {
      return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * plaintext passwords are bad!  Override this for your application
     *
     * @param array $user
     * @param string $password
     * @return bool
     */
    protected function checkPassword($user, $password) : bool {
      return password_verify($password, $user['password']);
    }

    /**
     * @param string $tel_nr
     * @return array|bool
     */
    public function getUserDetails($tel_nr)
    {
        return parent::getUserDetails($tel_nr);
    }

    /**
     * override
     * @param string $tel_nr
     * @return array|bool
     */
    public function getUser($tel_nr)
    {
        $stmt = $this->db->prepare($sql = sprintf('SELECT * from %s where tel_nr=:tel_nr', $this->config['user_table']));
        $stmt->execute(array('tel_nr' => $tel_nr));

        $userInfo = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$userInfo) {
          throw new Exception("Bruker eksisterer ikke!", 183001);
        }

        // the default behavior is to use "tel_nr" as the user_id
        return array_merge(array(
            'user_id' => $tel_nr
        ), $userInfo);
    }

    /**
     * plaintext passwords are bad!  Override this for your application
     * override
     *
     * @param string $tel_nr
     * @param string $password
     * @param string $firstName
     * @param string $lastName
     * @return bool
     */
    public function setUser($tel_nr, $password, $firstName = null, $lastName = null)
    {
        // do not store in plaintext
        $password = $this->hashPassword($password);

        // if it exists, update it.
        if ($this->getUser($tel_nr)) {
            $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET password=:password, first_name=:firstName, last_name=:lastName where tel_nr=:tel_nr', $this->config['user_table']));
        } else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (tel_nr, password, first_name, last_name) VALUES (:tel_nr, :password, :firstName, :lastName)', $this->config['user_table']));
        }

        return $stmt->execute(compact('tel_nr', 'password', 'firstName', 'lastName'));
    }


    /**
     * DDL to create OAuth2 database and tables for PDO storage
     * override
     * 
     * @see https://github.com/dsquier/oauth2-server-php-mysql
     *
     * @param string $dbName
     * @return string
     */
    public function getBuildSql($dbName = 'oauth2_server_php')
    {
        $sql = "
        CREATE TABLE {$this->config['client_table']} (
          client_id             VARCHAR(80)   NOT NULL,
          client_secret         VARCHAR(80),
          redirect_uri          VARCHAR(2000),
          grant_types           VARCHAR(80),
          scope                 VARCHAR(4000),
          user_id               VARCHAR(80),
          PRIMARY KEY (client_id)
        );

            CREATE TABLE {$this->config['access_token_table']} (
              access_token         VARCHAR(40)    NOT NULL,
              client_id            VARCHAR(80)    NOT NULL,
              user_id              VARCHAR(80),
              expires              TIMESTAMP      NOT NULL,
              scope                VARCHAR(4000),
              PRIMARY KEY (access_token)
            );

            CREATE TABLE {$this->config['code_table']} (
              authorization_code  VARCHAR(40)    NOT NULL,
              client_id           VARCHAR(80)    NOT NULL,
              user_id             VARCHAR(80),
              redirect_uri        VARCHAR(2000),
              expires             TIMESTAMP      NOT NULL,
              scope               VARCHAR(4000),
              id_token            VARCHAR(1000),
              PRIMARY KEY (authorization_code)
            );

            CREATE TABLE {$this->config['refresh_token_table']} (
              refresh_token       VARCHAR(40)    NOT NULL,
              client_id           VARCHAR(80)    NOT NULL,
              user_id             VARCHAR(80),
              expires             TIMESTAMP      NOT NULL,
              scope               VARCHAR(4000),
              PRIMARY KEY (refresh_token)
            );

            CREATE TABLE {$this->config['user_table']} (
              tel_nr              VARCHAR(80),
              password            VARCHAR(80),
              first_name          VARCHAR(80),
              last_name           VARCHAR(80),
              tel_nr_verified     BOOLEAN,
              scope               VARCHAR(4000)
            );

            CREATE TABLE {$this->config['scope_table']} (
              scope               VARCHAR(80)  NOT NULL,
              is_default          BOOLEAN,
              PRIMARY KEY (scope)
            );

            CREATE TABLE {$this->config['jwt_table']} (
              client_id           VARCHAR(80)   NOT NULL,
              subject             VARCHAR(80),
              public_key          VARCHAR(2000) NOT NULL
            );

            CREATE TABLE {$this->config['jti_table']} (
              issuer              VARCHAR(80)   NOT NULL,
              subject             VARCHAR(80),
              audiance            VARCHAR(80),
              expires             TIMESTAMP     NOT NULL,
              jti                 VARCHAR(2000) NOT NULL
            );

            CREATE TABLE {$this->config['public_key_table']} (
              client_id            VARCHAR(80),
              public_key           VARCHAR(2000),
              private_key          VARCHAR(2000),
              encryption_algorithm VARCHAR(100) DEFAULT 'RS256'
            )
        ";

        return $sql;
    }
}
