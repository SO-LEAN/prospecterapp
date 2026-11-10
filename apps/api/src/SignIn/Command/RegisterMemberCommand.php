<?php

namespace App\SignIn\Command;

use App\Shared\CommandBus;
use App\Shared\Identities;
use App\SignIn\Credentials;
use Prospecter\Core\Accounts\Application\RegisterAMember\RegisterAMember;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * A colleague registered by an administrator until a screen of the SPA does it (ADR 0075): the member (the core),
 * then what they sign in with; the member fills in their profile once signed in.
 */
#[AsCommand(name: 'app:member:register', description: 'Register a member in the account of an administrator, signing in with the email given')]
final readonly class RegisterMemberCommand
{
    use TellsTheRefusal;

    private const string PROSPECTOR = 'ROLE_PROSPECTOR';
    private const string PERSONAL_DATA = 'ROLE_PERSONAL_DATA';
    private const string ADMIN = 'ROLE_ADMIN';

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
        #[Argument(description: 'The login email of an administrator of the account')]
        string $administratorEmail,
        #[Argument(description: 'The login email of the new member')]
        string $email,
        #[Option(description: 'The right to manage personal data')]
        bool $personalData = false,
        #[Option(description: 'The right to administer the account')]
        bool $admin = false,
        #[Option(description: 'For scripts; asked without echo otherwise')]
        ?string $password = null,
    ): int {
        $member = new MemberToRegister($administratorEmail, $email, self::rolesGiven($personalData, $admin), $password);

        try {
            $this->register($member, $io);
        } catch (CredentialsRefused|UseCaseRefused $refusal) {
            return self::refused($refusal, $io);
        }

        $io->success(sprintf('"%s" is a member of the account of "%s".', $email, $administratorEmail));

        return Command::SUCCESS;
    }

    /**
     * @return list<string> a prospector, and the other roles the options give
     */
    private static function rolesGiven(bool $personalData, bool $admin): array
    {
        return array_keys(array_filter([self::PROSPECTOR => true, self::PERSONAL_DATA => $personalData, self::ADMIN => $admin]));
    }

    private function register(MemberToRegister $member, SymfonyStyle $io): void
    {
        $administrator = $this->administratorSigningInWith($member->administratorEmail);
        $password = $this->newCredentials->passwordFor($member->email, $member->password, $io);
        $memberId = $this->identities->next();
        $this->registerInTheAccountOf($administrator, $memberId, $member);
        $this->credentials->register($memberId, $member->email, $password);
    }

    private function administratorSigningInWith(string $email): string
    {
        return $this->credentials->memberSigningInWith($email) ?? throw CredentialsRefused::becauseNobodySignsInWith($email);
    }

    private function registerInTheAccountOf(string $administrator, string $memberId, MemberToRegister $member): void
    {
        $this->commands->dispatch(new RegisterAMember($administrator, $memberId, $this->identities->next(), $member->roles, $this->clock->now()));
    }
}
