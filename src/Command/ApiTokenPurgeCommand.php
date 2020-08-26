<?php

namespace App\Command;

use App\Entity\ApiToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ApiTokenPurgeCommand extends Command
{
    private const FORCE_OPTION = 'force';

    private const ROLE_ARGUMENT = 'role';

    protected static $defaultName = 'app:api-token:purge';

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * ApiTokenGenerateCommand constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Purge all API tokens.')
            ->setHelp(sprintf('The <info>%s</info> command invalidates all tokens or all tokens for a specified role.', self::$defaultName))
            ->addArgument(self::ROLE_ARGUMENT, InputArgument::OPTIONAL, 'The role to purge.')
            ->addOption(self::FORCE_OPTION, null, InputOption::VALUE_NONE, 'Set this option to execute this action')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $role = $input->getArgument(self::ROLE_ARGUMENT);
        if ($role === null) {
            return $this->purgeAllApiTokens($io, $input);
        } else {
            return $this->purgeRoleApiTokens($io, $input, $role);
        }
    }

    /**
     * @param SymfonyStyle $io
     * @param InputInterface $input
     * @return int
     */
    protected function purgeAllApiTokens(SymfonyStyle $io, InputInterface $input): int
    {
        $affected = $this->entityManager->getRepository(ApiToken::class)->count([]);

        if ($affected === 0) {
            $io->writeln('<comment>There are no tokens.</comment>');
            return Command::FAILURE;
        }

        if ($input->getOption(self::FORCE_OPTION) === false) {
            $io->writeln([
                sprintf('<info>Would remove all (<comment>%d</comment>) API tokens.</info>', $affected),
                'Please run the operation with --force to execute',
                '<error>All tokens will be permanently lost!</error>',
            ]);
            return Command::FAILURE;
        }

        $this->entityManager->createQueryBuilder()
            ->delete(ApiToken::class, 't')
            ->getQuery()
            ->execute();
        $this->entityManager->flush();

        $io->writeln(sprintf('<info>Removed all (<comment>%d</comment>) API tokens.</info>', $affected));
        return Command::SUCCESS;
    }

    /**
     * @param SymfonyStyle $io
     * @param InputInterface $input
     * @param string $role
     * @return int
     */
    protected function purgeRoleApiTokens(SymfonyStyle $io, InputInterface $input, string $role): int
    {
        $affected = $this->entityManager->getRepository(ApiToken::class)->count([ApiToken::ROLE => $role]);

        if ($affected === 0) {
            $io->writeln(sprintf('<comment>There are no tokens for role %s.</comment>', $role));
            return Command::SUCCESS;
        }

        if ($input->getOption(self::FORCE_OPTION) === false) {
            $io->writeln([
                sprintf('<info>Would remove all (<comment>%d</comment>) API tokens for role <comment>%s</comment>.</info>', $affected, $role),
                'Please run the operation with --force to execute',
                '<error>All matching tokens will be permanently lost!</error>',
            ]);
            return Command::FAILURE;
        }

        $this->entityManager->createQueryBuilder()
            ->delete(ApiToken::class, 't')
            ->where('t.role = :role')
            ->setParameter('role', $role)
            ->getQuery()
            ->execute();
        $this->entityManager->flush();

        $io->writeln(sprintf('<info>Removed all (<comment>%d</comment>) API tokens for role <comment>%s</comment>.</info>', $affected, $role));
        return Command::SUCCESS;
    }
}
