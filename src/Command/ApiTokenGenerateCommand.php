<?php

namespace App\Command;

use App\Entity\ApiToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ApiTokenGenerateCommand extends Command
{
    private const ROLE_ARGUMENT = 'role';

    private const ROLE_PATTERN = '/^ROLE_[A-Z0-9_]+$/';

    protected static $defaultName = 'app:api-token:generate';

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
            ->setDescription('Generate a new API token.')
            ->setHelp(sprintf('The <info>%s</info> command generates a random token and associates it with a pseudo user that has the provided access role.', self::$defaultName))
            ->addArgument(self::ROLE_ARGUMENT, InputArgument::REQUIRED, sprintf('The role to authenticate. Must match %s.', self::ROLE_PATTERN))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $role = $input->getArgument(self::ROLE_ARGUMENT);

        if (1 !== preg_match(self::ROLE_PATTERN, $role)) {
            $io->error(sprintf('\'%s\' is not a valid role. Required pattern: %s', $role, self::ROLE_PATTERN));
            return Command::FAILURE;
        }

        try {
            $tokenString = base64_encode(random_bytes(189));
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        $apiToken = new ApiToken();
        $apiToken->setToken($tokenString);
        $apiToken->setRole($role);
        $this->entityManager->persist($apiToken);
        $this->entityManager->flush();

        $io->text('<comment>Token:</comment>');
        $io->writeln($tokenString);
        $io->newLine();
        $io->success(sprintf('Successfully generated token for role \'%s\'!', $role));

        return Command::SUCCESS;
    }
}
