<?php

declare(strict_types=1);

namespace Elkuku\SymfonyUtils\Tests\Fixtures;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Elkuku\SymfonyUtils\Command\UserAdminBaseCommand;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * A project's user-admin command, as it extends UserAdminBaseCommand.
 */
class TestUserAdminCommand extends UserAdminBaseCommand
{
    /**
     * @param ServiceEntityRepository<TestUser> $userRepository
     */
    public function __construct(EntityManagerInterface $entityManager, ServiceEntityRepository $userRepository)
    {
        parent::__construct($entityManager, $userRepository, TestUser::class);
    }

    protected function getRoles(): array
    {
        return TestRole::cases();
    }

    protected function setRole(UserInterface $user, string $role): UserInterface
    {
        \assert($user instanceof TestUser);

        return $user->setRole(TestRole::from($role));
    }
}
