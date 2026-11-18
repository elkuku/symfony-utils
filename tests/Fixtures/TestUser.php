<?php

declare(strict_types=1);

namespace Elkuku\SymfonyUtils\Tests\Fixtures;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * A user entity as UserAdminBaseCommand expects it: public $id, setIdentifier(), getGitHubId().
 */
class TestUser implements UserInterface
{
    public ?int $id = null;

    private string $identifier = '';

    private TestRole $role = TestRole::USER;

    private ?int $gitHubId = null;

    public function getUserIdentifier(): string
    {
        return '' !== $this->identifier ? $this->identifier : 'unnamed';
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getRoles(): array
    {
        return [$this->role->value];
    }

    public function getRole(): TestRole
    {
        return $this->role;
    }

    public function setRole(TestRole $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getGitHubId(): ?int
    {
        return $this->gitHubId;
    }

    public function setGitHubId(?int $gitHubId): self
    {
        $this->gitHubId = $gitHubId;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }
}
