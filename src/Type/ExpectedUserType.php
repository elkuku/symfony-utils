<?php

namespace Elkuku\SymfonyUtils\Type;

use App\Enum\UserRole;
use Symfony\Component\Security\Core\User\UserInterface;

class ExpectedUserType implements UserInterface
{
    private ?int $id = null;
    private string $identifier;

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

    public function setRole(UserRole $role): ExpectedUserType
    {
        $this->role = $role;

        return $this;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function eraseCredentials(): void
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
