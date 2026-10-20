<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Hook\AfterScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Prospecter\Core\Accounts\Domain\Account;
use Prospecter\Core\Accounts\Domain\Member;
use Prospecter\Core\Accounts\Domain\Role;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\Purpose;
use Prospecter\Core\PersonalData\Domain\Reader;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\SharedKernel\Application\Exception\ErrorType;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\Uuid;
use Tests\Core\Acceptance\Application;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

/**
 * The state of a scenario, shared by the other contexts: the application, the organizations it names
 * (scenarios speak of names, the application of identities), and the files the user is about to upload.
 */
final class ScenarioContext implements Context
{
    public readonly Application $application;
    private readonly string $uploads;
    /** @var array<string, AccountId> */
    private array $accountIds = [];
    /** @var array<string, OrganizationId> */
    private array $organizationIds = [];
    /** @var array<string, PersonId> */
    private array $personIds = [];
    /** @var array<string, MemberId> */
    private array $memberIds = [];

    public function __construct()
    {
        $this->application = new Application();
        $this->uploads = sys_get_temp_dir().'/prospecter-behat-'.bin2hex(random_bytes(6));
        mkdir($this->uploads);
    }

    #[AfterScenario]
    public function removeUploads(): void
    {
        array_map(unlink(...), glob($this->uploads.'/*') ?: []);
        rmdir($this->uploads);
    }

    #[Given('l\'organisation :name qui utilise Prospecter')]
    public function anOrganizationUsingProspecter(string $name): void
    {
        $this->openAccount($name, Account::open(new AccountId($this->application->newIdentity()), new LegalIdentity($name), $this->application->now()));
    }

    #[Given('nous sommes le :moment')]
    public function itIs(string $moment): void
    {
        $this->application->itIs(new \DateTimeImmutable($moment));
    }

    #[Given('/^nous sommes le (?<moment>\\d{2}\\/\\d{2}\\/\\d{4} à \\d{1,2}h\\d{2})$/')]
    public function itIsInFrench(string $moment): void
    {
        $this->application->itIs(Vocabulary::frenchMoment($moment));
    }

    #[Given('je suis connecté en tant que prospecteur de :organization')]
    public function iAmSignedInAsProspectorOf(string $account): void
    {
        $this->application->signIn($this->aMemberOf('moi', $account, Role::User, Role::Prospector, Role::PersonalData)->id());
    }

    #[Given('je suis connecté en tant que prospecteur de :organization sans le droit aux données personnelles')]
    public function iAmSignedInWithoutThePersonalDataRight(string $account): void
    {
        $this->application->signIn($this->aMemberOf('moi', $account, Role::User, Role::Prospector)->id());
    }

    #[Given('je suis connecté en tant que :name')]
    public function iAmSignedInAs(string $name): void
    {
        $this->application->signIn($this->memberIdOf($name));
    }

    /**
     * A member of the account, with their person in PersonalData (membership), known by name in the scenario.
     */
    public function aMemberOf(string $name, string $account, Role ...$roles): Member
    {
        $person = Person::collect(new PersonId($this->application->newIdentity()), $this->accountIdOf($account), Purpose::Membership, $this->application->now());
        $this->application->persons->save($person);
        $this->personIds[$name] = $person->id();
        $member = Member::register(new MemberId($this->application->newIdentity()), $this->accountIdOf($account), $person->id(), ...$roles);
        $this->application->members->save($member);
        $this->rememberMember($name, $member->id());

        return $member;
    }

    #[Given('le géocodeur situe :place en :longitude, :latitude')]
    public function theGeocoderKnows(string $place, float $longitude, float $latitude): void
    {
        $this->application->geoLocation->know($place, $longitude, $latitude);
    }

    #[Given('j\'ai une image :fileName de :size octets')]
    public function iHaveAnImage(string $fileName, int $size): void
    {
        file_put_contents($this->uploads.'/'.$fileName, str_repeat('x', $size));
    }

    #[Then('/^c\'est refusé : "(?<message>.*)"$/')]
    public function itIsRefused(string $message): void
    {
        Expect::that(null !== $this->application->refusal(), 'The use case was expected to refuse');
        Expect::same($message, $this->application->refusal()?->getMessage());
    }

    #[Then('ce n\'est pas refusé')]
    public function itIsNotRefused(): void
    {
        Expect::that(null === $this->application->refusal(), sprintf('Refused: %s', $this->application->refusal()?->getMessage()));
    }

    #[Then('c\'est introuvable')]
    public function itIsNotFound(): void
    {
        Expect::that(ErrorType::NotFound === $this->application->refusal()?->type, sprintf('Not found was expected, got: %s', $this->application->refusal()?->type->name ?? 'no refusal'));
    }

    public function save(string $name, Organization $organization): Organization
    {
        $this->application->organizations->save($organization);
        $this->remember($name, $organization->id()->value);

        return $organization;
    }

    public function remember(?string $name, string $id): void
    {
        if (null !== $name) {
            $this->organizationIds[$name] = new OrganizationId($id);
        }
    }

    public function openAccount(string $name, Account $account): void
    {
        $this->application->accounts->save($account);
        $this->accountIds[$name] = $account->id();
    }

    /**
     * The organization identity a request would carry for this name: the one of a prospected organization,
     * the one of an account (which is no prospected organization), or one nobody has.
     */
    public function idOf(string $name): string
    {
        $known = $this->organizationIds[$name] ?? $this->accountIds[$name] ?? null;

        return $known instanceof Uuid ? $known->value : $this->application->newIdentity();
    }

    public function accountIdOf(string $name): AccountId
    {
        return $this->accountIds[$name] ?? throw new \LogicException(sprintf('No account "%s" in the scenario', $name));
    }

    public function nameOf(Uuid $id): string
    {
        foreach ([...$this->accountIds, ...$this->organizationIds] as $name => $knownId) {
            if ($knownId->equals($id)) {
                return $name;
            }
        }

        throw new \LogicException(sprintf('Nothing named %s in the scenario', $id));
    }

    public function isAnOrganization(string $name): bool
    {
        return isset($this->organizationIds[$name]);
    }

    public function organizationNamed(string $name): Organization
    {
        return $this->application->organizations->get($this->organizationIds[$name] ?? throw new \LogicException(sprintf('No organization "%s" in the scenario', $name)));
    }

    public function me(): Member
    {
        return $this->application->members->get(new MemberId($this->application->signedIn()));
    }

    public function meAsReader(): Reader
    {
        return $this->application->readerService->readerFrom(new MemberId($this->application->signedIn()));
    }

    public function myPerson(): Person
    {
        return $this->application->persons->get($this->me()->personId());
    }

    /**
     * The person a request would carry for this name; an unknown name gets an identity nobody has.
     */
    public function rememberPerson(string $name, PersonId $id): void
    {
        $this->personIds[$name] = $id;
    }

    public function rememberMember(string $name, MemberId $id): void
    {
        $this->memberIds[$name] = $id;
    }

    public function memberIdOf(string $name): MemberId
    {
        return $this->memberIds[$name] ?? throw new \LogicException(sprintf('No member "%s" in the scenario', $name));
    }

    public function memberNamed(string $name): Member
    {
        return $this->application->members->get($this->memberIdOf($name));
    }

    public function personNamed(string $name): Person
    {
        return $this->application->persons->get($this->personIds[$name] ?? throw new \LogicException(sprintf('No person "%s" in the scenario', $name)));
    }

    public function nameOfPerson(PersonId $id): string
    {
        $name = array_search($id, $this->personIds, false);

        return false === $name ? throw new \LogicException(sprintf('No person %s in the scenario', $id)) : $name;
    }

    /**
     * A member of this account allowed to manage personal data, to arrange its persons the way the business
     * allows.
     */
    public function managerOfPersonalDataOf(AccountId $accountId): Reader
    {
        return new Reader(new MemberId($this->application->newIdentity()), $accountId, new PersonId($this->application->newIdentity()), true);
    }

    public function personIdOf(string $name): string
    {
        return isset($this->personIds[$name]) ? $this->personIds[$name]->value : $this->application->newIdentity();
    }

    public function meAsProspector(): Prospector
    {
        return $this->application->prospectorService->prospectorFrom(new MemberId($this->application->signedIn()));
    }

    /**
     * A prospector of the account owning this organization, to arrange its state the way the business allows.
     */
    public function managerOf(Organization $organization): Prospector
    {
        return $this->prospectorOf($organization->accountId());
    }

    public function prospectorOf(AccountId $accountId): Prospector
    {
        return new Prospector(new MemberId($this->application->newIdentity()), $accountId, managesPersonalData: true);
    }

    /**
     * A member of this account, to arrange its state the way the business allows.
     */
    public function memberOf(Account $account): Member
    {
        return Member::register(new MemberId($this->application->newIdentity()), $account->id(), new PersonId($this->application->newIdentity()), Role::User);
    }

    public function upload(string $fileName): \SplFileInfo
    {
        return new \SplFileInfo($this->uploads.'/'.$fileName);
    }
}
