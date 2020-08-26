<?php

namespace App\Command;

use App\Entity\ApiToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ApiTokenInvalidateCommand extends Command
{
    private const TOKEN_ARGUMENT = 'token';

    protected static $defaultName = 'app:api-token:invalidate';

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
            ->setDescription('Invalidate one API token.')
            ->setHelp(sprintf('The <info>%s</info> command invalidates the specified token, rendering it invalid for authentication.', self::$defaultName))
            ->addArgument(self::TOKEN_ARGUMENT, InputArgument::REQUIRED, 'The token to invalidate.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tokenString = $input->getArgument(self::TOKEN_ARGUMENT);

        $apiToken = $this->entityManager->getRepository(ApiToken::class)->findOneBy([ApiToken::TOKEN => $tokenString]);
        if ($apiToken === null) {
            $io->error(sprintf('Cannot invalidate a token that does not exist: %s', $tokenString));
            return Command::FAILURE;
        }

        $this->entityManager->remove($apiToken);
        $this->entityManager->flush();

        $io->success('Successfully invalidated token!');

        return Command::SUCCESS;
    }
}
