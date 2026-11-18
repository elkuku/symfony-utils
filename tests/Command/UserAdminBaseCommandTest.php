<?php

declare(strict_types=1);

namespace Elkuku\SymfonyUtils\Tests\Command;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Elkuku\SymfonyUtils\Tests\Fixtures\TestRole;
use Elkuku\SymfonyUtils\Tests\Fixtures\TestUser;
use Elkuku\SymfonyUtils\Tests\Fixtures\TestUserAdminCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Security\Core\User\UserInterface;

class UserAdminBaseCommandTest extends TestCase
{
    public function testExit(): void
    {
        $tester = $this->createTester($this->createStub(EntityManagerInterface::class));
        $tester->setInputs(['Exit']);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('have Fun', $tester->getDisplay());
    }

    public function testCreateUser(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::callback(
            static fn (object $user): bool => $user instanceof TestUser
                && 'alice' === $user->getUserIdentifier()
                && TestRole::ADMIN === $user->getRole()
        ));
        $entityManager->expects(self::once())->method('flush');

        $tester = $this->createTester($entityManager);
        $tester->setInputs(['Create User', 'alice', 'ROLE_ADMIN', 'Exit']);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('User created', $tester->getDisplay());
    }

    public function testFillNewUserAsksForMoreFields(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::callback(
            static fn (object $user): bool => $user instanceof TestUser
                && 'alice' === $user->getUserIdentifier()
                && 'Alice Liddell' === $user->getName()
        ));

        $command = new class($entityManager, $this->createStub(ServiceEntityRepository::class)) extends TestUserAdminCommand {
            protected function fillNewUser(UserInterface $user, SymfonyStyle $io): UserInterface
            {
                \assert($user instanceof TestUser);

                return $user->setName((string) $io->ask('Name'));
            }
        };

        $tester = new CommandTester($command);
        $tester->setInputs(['Create User', 'alice', 'ROLE_USER', 'Alice Liddell', 'Exit']);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('User created', $tester->getDisplay());
    }

    public function testPromptsShowEachColonOnce(): void
    {
        $tester = $this->createTester($this->createStub(EntityManagerInterface::class));
        $tester->setInputs(['Create User', 'alice', 'ROLE_USER', 'Exit']);
        $tester->execute([]);

        $display = $tester->getDisplay();
        self::assertStringContainsString('Identifier:', $display);
        self::assertStringContainsString('User role [ROLE_USER]:', $display);
        self::assertStringNotContainsString(': :', $display);
        self::assertStringNotContainsString('):', $display);
    }

    public function testReadUsers(): void
    {
        $alice = (new TestUser())->setIdentifier('alice')->setGitHubId(42);
        $alice->id = 1;
        $bob = (new TestUser())->setIdentifier('bob')->setRole(TestRole::ADMIN);
        $bob->id = 2;

        $repository = $this->createStub(ServiceEntityRepository::class);
        $repository->method('findBy')->willReturn([$alice, $bob]);

        $tester = $this->createTester($this->createStub(EntityManagerInterface::class), $repository);
        $tester->setInputs(['Read Users', 'Exit']);
        $tester->execute([]);

        $display = $tester->getDisplay();
        self::assertStringContainsString('There are 2 users in the database.', $display);
        self::assertMatchesRegularExpression('/1\s+\|\s+alice\s+\|\s+ROLE_USER\s+\|\s+n\/a\s+\|\s+42/', $display);
        self::assertMatchesRegularExpression('/2\s+\|\s+bob\s+\|\s+ROLE_ADMIN/', $display);
    }

    public function testUpdateUser(): void
    {
        $user = (new TestUser())->setIdentifier('alice');

        $repository = $this->createMock(ServiceEntityRepository::class);
        $repository->expects(self::once())->method('findOneBy')->with(['id' => '7'])->willReturn($user);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $tester = $this->createTester($entityManager, $repository);
        $tester->setInputs(['Update User', '7', 'alice2', 'ROLE_ADMIN', 'Exit']);
        $tester->execute([]);

        self::assertSame('alice2', $user->getUserIdentifier());
        self::assertSame(TestRole::ADMIN, $user->getRole());
        self::assertStringContainsString('User updated', $tester->getDisplay());
    }

    public function testDeleteUser(): void
    {
        $user = (new TestUser())->setIdentifier('alice');

        $repository = $this->createStub(ServiceEntityRepository::class);
        $repository->method('findOneBy')->willReturn($user);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('remove')->with($user);
        $entityManager->expects(self::once())->method('flush');

        $tester = $this->createTester($entityManager, $repository);
        $tester->setInputs(['Delete User', '7', 'Exit']);
        $tester->execute([]);

        self::assertStringContainsString('User has been removed', $tester->getDisplay());
    }

    public function testUnknownUserShowsAnError(): void
    {
        $repository = $this->createStub(ServiceEntityRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('remove');

        $tester = $this->createTester($entityManager, $repository);
        $tester->setInputs(['Delete User', '99', 'Exit']);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('User not found!', $tester->getDisplay());
    }

    /**
     * @param ServiceEntityRepository<TestUser>|null $repository
     */
    private function createTester(
        EntityManagerInterface $entityManager,
        ?ServiceEntityRepository $repository = null,
    ): CommandTester {
        return new CommandTester(new TestUserAdminCommand(
            $entityManager,
            $repository ?? $this->createStub(ServiceEntityRepository::class),
        ));
    }
}
