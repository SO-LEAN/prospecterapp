<?php

namespace App\SignIn\Command;

use App\Shared\CommandBus;
use App\Shared\Identities;
use App\SignIn\Credentials;
use Prospecter\Core\Accounts\Application\OpenAccount\OpenAccount;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The first account of an installation, and any account until the members open theirs: the account and its founder
 * (the core), then what the founder signs in with; the founder fills in their profile once signed in.
 */
#[AsCommand(name: 'app:account:open', description: 'Open the account of an organization, its founder signing in with the email given')]
final readonly class OpenAccountCommand
{
    use TellsTheRefusal;

    public function __construct(
        private CommandBus $commands,
        private Credentials $credentials,
        private NewCredentials $newCredentials,
        private Identities $identities,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'The corporate name of the organization')]
        string $corporateName,
        #[Argument(description: 'The login email of the founder')]
        string $email,
        #[Option(description: 'The legal form of the organization, such as SARL')]
        ?string $form = null,
        #[Option(description: 'For scripts; asked without echo otherwise')]
        ?string $password = null,
    ): int {
        try {
            $this->open($corporateName, $form, $email, $password, $io);
        } catch (CredentialsRefused|UseCaseRefused $refusal) {
            return self::refused($refusal, $io);
        }

        $io->success(sprintf('The account of "%s" is open: its founder signs in with "%s".', $corporateName, $email));

        return Command::SUCCESS;
    }

    private function open(string $corporateName, ?string $form, string $email, ?string $password, SymfonyStyle $io): void
    {
        $password = $this->newCredentials->passwordFor($email, $password, $io);
        $founder = $this->identities->next();
        $this->openWith($founder, $corporateName, $form);
        $this->credentials->register($founder, $email, $password);
    }

    private function openWith(string $founder, string $corporateName, ?string $form): void
    {
        $this->commands->dispatch(new OpenAccount($this->identities->next(), $corporateName, $form, $founder, $this->identities->next(), $this->clock->now()));
    }
}
