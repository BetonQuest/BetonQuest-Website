<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;

class PseudoUser implements UserInterface
{
    private string $username;

    private array $roles;

    /**
     * PseudoUser constructor.
     * @param $username
     */
    public function __construct($username)
    {
        if (0 !== strpos($username, 'ROLE_')) {
            throw new UnsupportedUserException("Only supporting roles as username!");
        }
        $this->username = $username;
        $this->roles = [$username];
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @see UserInterface
     */
    public function getPassword()
    {
        // not needed for users that cannot have passwords
        return null;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed for users that cannot have passwords
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // not needed for users that cannot have passwords
    }
}
