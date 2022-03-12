<?php

namespace Elkuku\SymfonyUtils\Command;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Elkuku\SymfonyUtils\Type\ExpectedUserRepository;
use Elkuku\SymfonyUtils\Type\ExpectedUserType;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\User\UserInterface;
use UnexpectedValueException;

#[AsCommand(
    name: 'user-admin',
    description: 'Administer user accounts',
    aliases: ['useradmin', 'admin']
)]
class UserAdminBaseCommand extends Command
{
    private InputInterface $input;
    private OutputInterface $output;
    private SymfonyStyle $io;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        /**
         * @var ServiceEntityRepository<ExpectedUserRepository>
         */
        private readonly ServiceEntityRepository $userRepository,
        /**
         * @var array<string>
         */
        private readonly array $userRoles,
        /**
         * @var class-string<ExpectedUserType>
         */
        private readonly string $userFQCN = '\\App\\Entity\\User',
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $this->input = $input;
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);

        $this->io->title('KuKu\'s User Admin');

        do {
            $exit = $this->showMenu();
        } while (!$exit);

        return Command::SUCCESS;
    }

    private function showMenu(): int
    {
        $answer = $this->getAnswer();
        $this->output->writeln($answer);

        try {
            switch ($answer) {
                case 'Create User':
                    $this->createUser();
                    $this->io->success('User created');
                    break;
                case 'Read Users':
                    $this->renderUsersTable();
                    break;
                case 'Update User':
                    $this->editUser();
                    break;
                case 'Delete User':
                    $this->deleteUser();
                    $this->io->success('User has been removed');
                    break;
                case 'Exit':
                    $this->io->text('have Fun =;)');

                    return Command::FAILURE;
                default:
                    throw new UnexpectedValueException(
                        'Unknown answer: '.$answer
                    );
            }
        } catch (Exception $exception) {
            $this->io->error($exception->getMessage());
        }

        return Command::SUCCESS;
    }

    private function getAnswer(): string
    {
        $question = (new ChoiceQuestion(
            'Please select an option (defaults to exit)',
            [
                'Exit',
                'Create User',
                'Read Users',
                'Update User',
                'Delete User',
            ],
            0
        ))
            ->setErrorMessage('Choice %s is invalid.');

        return $this->getHelper('question')->ask(
            $this->input,
            $this->output,
            $question
        );
    }

    private function createUser(): void
    {
        $user = (new $this->userFQCN)
            ->setIdentifier($this->askIdentifier())
            ->setRoles([$this->askRole()]);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    private function askIdentifier(string $default = ''): string
    {
        $io = new SymfonyStyle($this->input, $this->output);
        do {
            $questionText = sprintf(
                'Identifier%s: ',
                $default ? " ($default)" : ''
            );
            $identifier = $this->getHelper('question')->ask(
                $this->input,
                $this->output,
                new Question($questionText, $default)
            );
            if (!$identifier) {
                $io->warning('Identifier required :(');
            }
        } while ($identifier === null);

        return $identifier;
    }

    /**
     * @param array<string> $default
     */
    private function askRole(array $default = []): mixed
    {
        $defaultRole = $default ? $default[0] : 'ROLE_USER';
        $questionText = sprintf('User role%s: ', " ($defaultRole)");

        return $this->getHelper('question')->ask(
            $this->input,
            $this->output,
            (new ChoiceQuestion(
                $questionText,
                array_values($this->userRoles),
                $defaultRole
            ))
                ->setErrorMessage('Choice %s is invalid.')
        );
    }

    private function renderUsersTable(): void
    {
        $table = new Table($this->output);
        $table->setHeaders(
            ['ID', 'Identifier', 'Roles', 'GoogleId', 'GitHubId']
        );

        $users = $this->userRepository
            ->findBy([], ['id' => 'ASC']);

        $this->io->text(
            sprintf(
                '<fg=cyan>There are %d users in the database.</>',
                count($users)
            )
        );

        /* @type ExpectedUserType $user */
        foreach ($users as $user) {
            $gitHubId = method_exists($user, 'getGitHubId')
                ? $user->getGitHubId() : 'n/a';
            $googleId = method_exists($user, 'getGoogleId')
                ? $user->getGoogleId() : 'n/a';
            $table->addRow(
                [
                    $user->getId(),
                    $user->getUserIdentifier(),
                    implode(', ', $user->getRoles()),
                    $googleId,
                    $gitHubId,
                ]
            );
        }
        $table->render();
    }

    private function editUser(): void
    {
        /**
         * @var \Elkuku\SymfonyUtils\Type\ExpectedUserType $user
         */
        $user = $this->findUser();

        $user->setIdentifier($this->askIdentifier($user->getUserIdentifier()));
        $user->setRoles([$this->askRole($user->getRoles())]);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    private function deleteUser(): void
    {
        $user = $this->findUser();

        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    private function findUser(): UserInterface
    {
        $id = $this->getHelper('question')->ask(
            $this->input,
            $this->output,
            new Question('User ID: ')
        );

        $user = $this->userRepository->findOneBy(['id' => $id]);

        if (!$user) {
            throw new UnexpectedValueException('User not found!');
        }

        return $user;
    }
}
