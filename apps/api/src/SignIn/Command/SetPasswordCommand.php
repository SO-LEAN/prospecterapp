<?php

namespace App\SignIn\Command;

use App\SignIn\Credentials;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:user:set-password', description: 'Set the password a member signs in with')]
final readonly class SetPasswordCommand
{
    public function __construct(private Credentials $credentials)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'The login email of the member')]
        string $email,
        #[Option(description: 'For scripts; asked without echo otherwise')]
        ?string $password = null,
    ): int {
        $memberId = $this->credentials->memberSigningInWith($email);

        if (null === $memberId) {
            $io->error(sprintf('Nobody signs in with "%s".', $email));

            return Command::FAILURE;
        }

        $password ??= $io->askHidden('New password');

        if (!is_string($password) || '' === $password) {
            $io->error('The password cannot be empty.');

            return Command::INVALID;
        }

        $this->credentials->changePassword($memberId, $password);
        $io->success(sprintf('Password of "%s" set.', $email));

        return Command::SUCCESS;
    }
}
