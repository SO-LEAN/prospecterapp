<?php

namespace App\Command;

use App\Passwords;
use App\Security\MemberProvider;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:user:set-password', description: 'Set the password a user signs in with')]
final class SetPasswordCommand extends Command
{
    public function __construct(private readonly MemberProvider $members, private readonly Passwords $passwords)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'The login email of the member')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'For scripts; asked without echo otherwise');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $loginEmail = (string) $input->getArgument('email');
        $member = $this->members->byLoginEmail($loginEmail);
        $password = $input->getOption('password') ?? $io->askHidden('New password');

        if (!is_string($password) || '' === $password) {
            $io->error('The password cannot be empty.');

            return Command::INVALID;
        }

        $this->passwords->set($member->getUserIdentifier(), $password);
        $io->success(sprintf('Password of "%s" set.', $loginEmail));

        return Command::SUCCESS;
    }
}
