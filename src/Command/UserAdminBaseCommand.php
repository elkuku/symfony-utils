<?php

declare(strict_types=1);

namespace Elkuku\SymfonyUtils\Command;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Interactive user management. Extend it in your project and implement getRoles() and setRole().
 *
 * The user class must implement UserInterface, have a setIdentifier(string) method, and expose
 * its ID through getId() or a public $id property. getGoogleId()/getGitHubId() are shown if present.
 * If the entity has more required fields, override fillNewUser() to ask for them.
 */
#[AsCommand(
    name: 'user-admin',
    description: 'Administer user accounts',
    aliases: ['useradmin', 'admin']
)]
abstract class UserAdminBaseCommand extends Command
{
    private const EXIT = 'Exit';
    private const CREATE = 'Create User';
    private const READ = 'Read Users';
    private const UPDATE = 'Update User';
    private const DELETE = 'Delete User';

    private SymfonyStyle $io;

    /**
     * @param ServiceEntityRepository<covariant object> $userRepository
     * @param string                                    $userFQCN       the user entity class, implementing UserInterface
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ServiceEntityRepository $userRepository,
        private readonly string $userFQCN = '\\App\\Entity\\User',
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->io->title('KuKu\'s User Admin');

        while ($this->showMenu()) {
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<\BackedEnum>
     */
    protected function getRoles(): array
    {
        throw new \RuntimeException('You have to implement the \'getRoles()\' method in the extended class.');
    }

    protected function setRole(UserInterface $user, string $role): UserInterface
    {
        throw new \RuntimeException('You have to implement the \'setRole()\' method in the extended class.');
    }

    /**
     * Called for a new user after its identifier and role are set, before it is saved.
     * Override it to ask for, or set, the other fields your user entity requires.
     */
    protected function fillNewUser(UserInterface $user, SymfonyStyle $io): UserInterface
    {
        return $user;
    }

    /**
     * @return bool false when the user chose to exit
     */
    private function showMenu(): bool
    {
        $answer = $this->ask(
            (new ChoiceQuestion(
                'Please select an option',
                [self::EXIT, self::CREATE, self::READ, self::UPDATE, self::DELETE],
                0
            ))->setErrorMessage('Choice %s is invalid.')
        );

        try {
            switch ($answer) {
                case self::CREATE:
                    $this->createUser();
                    $this->io->success('User created');
                    break;
                case self::READ:
                    $this->renderUsersTable();
                    break;
                case self::UPDATE:
                    $this->editUser();
                    $this->io->success('User updated');
                    break;
                case self::DELETE:
                    $this->deleteUser();
                    $this->io->success('User has been removed');
                    break;
                case self::EXIT:
                    $this->io->text('have Fun =;)');

                    return false;
                default:
                    throw new \UnexpectedValueException('Unknown answer: '.$answer);
            }
        } catch (\Exception $exception) {
            $this->io->error($exception->getMessage());
        }

        return true;
    }

    private function createUser(): void
    {
        $userClass = $this->userFQCN;

        if (!\is_a($userClass, UserInterface::class, true)) {
            throw new \LogicException(\sprintf('The user class "%s" does not exist or does not implement %s.', $userClass, UserInterface::class));
        }

        $user = new $userClass();
        $this->setIdentifier($user, $this->askIdentifier());
        $user = $this->setRole($user, $this->askRole());
        $user = $this->fillNewUser($user, $this->io);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    private function editUser(): void
    {
        $user = $this->findUser();

        $this->setIdentifier($user, $this->askIdentifier($user->getUserIdentifier()));
        $this->setRole($user, $this->askRole($user->getRoles()));

        $this->entityManager->flush();
    }

    private function deleteUser(): void
    {
        $user = $this->findUser();

        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    private function renderUsersTable(): void
    {
        $users = $this->userRepository->findBy([], ['id' => 'ASC']);

        $this->io->text(\sprintf('<fg=cyan>There are %d users in the database.</>', \count($users)));

        $table = new Table($this->io);
        $table->setHeaders(['ID', 'Identifier', 'Roles', 'GoogleId', 'GitHubId']);

        foreach ($users as $user) {
            if (!$user instanceof UserInterface) {
                continue;
            }

            $table->addRow([
                $this->getId($user),
                $user->getUserIdentifier(),
                \implode(', ', $user->getRoles()),
                $this->callOptional($user, 'getGoogleId'),
                $this->callOptional($user, 'getGitHubId'),
            ]);
        }

        $table->render();
    }

    private function askIdentifier(string $default = ''): string
    {
        do {
            $identifier = $this->ask(new Question('Identifier', '' !== $default ? $default : null));

            if ('' === $identifier) {
                $this->io->warning('An identifier is required :(');
            }
        } while ('' === $identifier);

        return $identifier;
    }

    /**
     * @param array<string> $default the user's current roles
     */
    private function askRole(array $default = []): string
    {
        $choices = \array_map(static fn (\BackedEnum $role): string => (string) $role->value, $this->getRoles());

        if ([] === $choices) {
            throw new \UnexpectedValueException('getRoles() must return at least one role.');
        }

        $defaultRole = $default[0] ?? $choices[0];

        return $this->ask(
            (new ChoiceQuestion('User role', $choices, $defaultRole))
                ->setErrorMessage('Choice %s is invalid.')
        );
    }

    private function findUser(): UserInterface
    {
        $id = $this->ask(new Question('User ID: '));

        $user = $this->userRepository->findOneBy(['id' => $id]);

        if (!$user instanceof UserInterface) {
            throw new \UnexpectedValueException('User not found!');
        }

        return $user;
    }

    private function ask(Question $question): string
    {
        $answer = $this->io->askQuestion($question);

        return \is_scalar($answer) ? (string) $answer : '';
    }

    private function setIdentifier(UserInterface $user, string $identifier): void
    {
        if (!\method_exists($user, 'setIdentifier')) {
            throw new \LogicException(\sprintf('%s needs a setIdentifier(string) method.', $user::class));
        }

        $user->setIdentifier($identifier);
    }

    private function getId(UserInterface $user): string
    {
        if (\method_exists($user, 'getId')) {
            return $this->toString($user->getId());
        }

        // Entities with a public (hooked) $id property have no getId().
        return \property_exists($user, 'id') ? $this->toString($user->id) : 'n/a';
    }

    private function callOptional(UserInterface $user, string $method): string
    {
        return \method_exists($user, $method) ? $this->toString($user->$method()) : 'n/a';
    }

    private function toString(mixed $value): string
    {
        return \is_scalar($value) ? (string) $value : '';
    }
}
