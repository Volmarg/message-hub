<?php

namespace App\Command\Auth;

use App\Repository\UserRepository;
use App\Services\Internal\Jwt\UserJwtTokenService;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'mh:auth:generate-api-token',
    description: 'Will generate temporary api jwt token',
)]
class GenerateApiTokenCommand extends Command
{
    private const USER_NAME =  'user-name';

    public function __construct(
        private readonly UserRepository      $userRepository,
        private readonly UserJwtTokenService $userJwtTokenService,
    )
    {
        parent::__construct(null);
    }

    protected function configure(): void
    {
        $this
            ->addOption(self::USER_NAME, null, InputOption::VALUE_REQUIRED, 'User name for which token should be created');
    }

    /**
     * @throws JWTEncodeFailureException
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws JWTEncodeFailureException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userName = $input->getOption(self::USER_NAME);
        if (empty($userName)) {
            $io->error("Missing username");
            return self::INVALID;
        }

        $user = $this->userRepository->findOneByName($userName);
        if (empty($user)) {
            $io->error("No user was found for provided username: {$userName}");
            return self::INVALID;
        }

        $jwtToken = $this->userJwtTokenService->generate($user);

        $io->info("Your token");

        $output->writeln('');
        $output->writeln($jwtToken);
        $output->writeln('');


        return Command::SUCCESS;
    }
}
