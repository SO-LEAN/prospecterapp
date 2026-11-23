<?php

declare(strict_types=1);

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
 * Opens an installation's first account, and any account until the members can open theirs. The command opens the
 * account with its founder (the core), then registers what the founder signs in with. The founder fills in their
 * profile once signed in.
 */
#[AsCommand(name: 'app:account:open', description: 'Open an organization\'s account, whose founder signs in with the given email')]
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
        #[Argument(description: 'The organization\'s corporate name')]
        string $corporateName,
        #[Argument(description: 'The founder\'s login email')]
        string $email,
        #[Option(description: 'The organization\'s legal form, such as SARL')]
        ?string $form = null,
        #[Option(description: 'The password, for scripts. Otherwise, the command asks for it without echo')]
        ?string $password = null,
    ): int {
        try {
            $this->open($corporateName, $form, $email, $password, $io);
        } catch (CredentialsRefused|UseCaseRefused $refusal) {
            return self::refused($refusal, $io);
        }

        $io->success(sprintf('The account of "%s" is open. Its founder signs in with "%s".', $corporateName, $email));

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
        $this->commands->dispatch(new OpenAccount(id: $this->identities->next(), corporateName: $corporateName, form: $form, founderId: $founder, founderProfileId: $this->identities->next(), executedAt: $this->clock->now()));
    }
}
