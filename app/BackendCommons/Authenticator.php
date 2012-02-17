<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 *
 * License within file license.txt in the root folder.
 *
 */

namespace Backend;

/**
 * User authenticator
 *
 * @author Ondrej Slamecka
 */
class Authenticator extends \Nette\Object implements \Nette\Security\IAuthenticator
{

    /** @var \NDBF\Repository  */
    protected $users;

    public function __construct(\NDBF\Repository $user)
    {
        $this->users = $user;
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

        $user = $this->users->select()->where('email', $email)->fetch();

        if ($user === FALSE || $user['password'] !== self::calculateHash($password, $user->salt))
            throw new \Nette\Security\AuthenticationException('Email or password is incorrect.');

        return new \Nette\Security\Identity($user->id, $user->role, $user->toArray());
    }

    /**
     * Computes salted password hash.
     * @param  string
     * @return string
     */
    public static function calculateHash($password, $salt=NULL)
    {
        if ($salt === NULL)
            $salt = mt_rand();
        return hash_hmac('sha256', $password, $salt);
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

        if (is_string($token_age))
            $token_age = new \DateTime($token_age);
        $allowed_interval = new \DateTime();
        $allowed_interval->add(new \DateInterval('P0Y1DT0H0M'));

        if ($token_age > $allowed_interval)
            return false;
        else
            return true;
    }

    /**
     * Returns true if given $user is in admin role and provided correct password, false otherwise
     * @param \Nette\Http\User $user
     * @param string $password
     * @return bool
     */
    public function authenticateAdmin(\Nette\Http\User $user, $password)
    {
        if ($user->isInRole('admin')) {
            $enteredPasswordHash = Authenticator::calculateHash($password, $user->getIdentity()->data['salt']);
            $isRightPassword = $user->getIdentity()->data['password'] === $enteredPasswordHash;

            if ($isRightPassword)
                return true;
        }
        return false;
    }

}