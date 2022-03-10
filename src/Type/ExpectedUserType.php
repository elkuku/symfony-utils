<?php

namespace Elkuku\SymfonyUtils\Type;

use Symfony\Component\Security\Core\User\UserInterface;

class ExpectedUserType implements UserInterface
{
    private string $identifier;

    /**
     * @var array<string>
     */
    private array $roles;

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): ExpectedUserType
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }


    /**
     * @param array<string> $roles
     */
    public function setRoles(array $roles): ExpectedUserType
    {
        $this->roles = $roles;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }
}
