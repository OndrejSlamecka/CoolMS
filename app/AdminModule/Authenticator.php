<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 * 
 * License within file license.txt in the root folder.
 * 
 */

namespace AdminModule;

/**
 * User authenticator
 * 
 * @author Ondrej Slamecka
 */
class Authenticator extends \Nette\Object implements \Nette\Security\IAuthenticator
{

    /** @var \Nette\DI\Container */
    protected $context;

    public function __construct(\Nette\DI\Container $context)
    {
        $this->context = $context;
    }

    /**
     * Performs an authentication
     * @param  array Credentials
     * @return void
     * @throws AuthenticationException
     */
    public function authenticate(array $credentials)
    {
        list($email, $password) = $credentials;

        $users = $this->context->repositoryManager->User;

        $user = $users->find(array('email' => $email))->fetch();

        if ($user === null || $user['password'] !== self::hashPassword($email, $password))
            throw new \Nette\Security\AuthenticationException('Email or password is incorrect.');

        return new \Nette\Security\Identity($user->id, $user->role, $user->toArray());
    }

    /**
     * Hashes combination of password and email
     * @param string $name
     * @param string $passw
     * @return string
     */
    public static function hashPassword($email, $passw)
    {
        return hash("sha256", $email . $passw);
    }

    /**
     * Generates unique token. 24 characters long
     * @return string
     */
    public static function createToken()
    {
        return uniqid("", true);
    }

    /**
     * Verifies token age
     * @param string $token
     * @return boolean
     */
    public static function isTokenValid($token_age)
    {
        // Current setting: 1 day

        $token_age = new \DateTime($token_age);
        $allowed_interval = new \DateTime();
        $allowed_interval->add(new \DateInterval('P0Y1DT0H0M'));

        if ($token_age > $allowed_interval)
            return false;
        else
            return true;
    }

}