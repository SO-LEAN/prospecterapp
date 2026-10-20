<?php

namespace App\SignIn\Command;

use App\Shared\CommandBus;
use App\Shared\Identities;
use App\SignIn\Credentials;
use Prospecter\Core\Accounts\Application\RegisterAMember\RegisterAMember;
use Prospecter\Core\PersonalData\Application\ChangePersonContactDetails\ChangePersonContactDetails;
use Prospecter\Core\PersonalData\Application\RenamePerson\RenamePerson;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * A colleague registered by an administrator until a screen of the SPA does it (ADR 0075): the member (the core),
 * their name and the email of their profile, as the new member, then what they sign in with.
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
        #[Argument(description: 'The login email of the new member, also the email of their profile')]
        string $email,
        #[Option(description: 'The first name of the new member')]
        ?string $firstName = null,
        #[Option(description: 'The last name of the new member')]
        ?string $lastName = null,
        #[Option(description: 'The right to manage personal data')]
        bool $personalData = false,
        #[Option(description: 'The right to administer the account')]
        bool $admin = false,
        #[Option(description: 'For scripts; asked without echo otherwise')]
        ?string $password = null,
    ): int {
        $member = new MemberToRegister($administratorEmail, $email, $firstName, $lastName, self::rolesGiven($personalData, $admin), $password);

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

    /**
     * The member, then their profile filled in by the new member themselves.
     */
    private function registerInTheAccountOf(string $administrator, string $memberId, MemberToRegister $member): void
    {
        $profile = $this->identities->next();
        $now = $this->clock->now();
        $this->commands->dispatch(new RegisterAMember($administrator, $memberId, $profile, $member->roles, $now));
        $this->commands->dispatch(new RenamePerson($memberId, $profile, $member->firstName, $member->lastName, $now));
        $this->commands->dispatch(new ChangePersonContactDetails($memberId, $profile, $member->email, null, null, $now));
    }
}
