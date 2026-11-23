<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Hook\AfterScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Prospecter\Core\Accounts\Domain\Model\Account;
use Prospecter\Core\Accounts\Domain\Model\Member;
use Prospecter\Core\Accounts\Domain\Model\ProfileId;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\SharedKernel\Application\Exception\ErrorType;
use Prospecter\Core\SharedKernel\Domain\Company\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\Gesture\Gesture;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Prospecter\Core\SharedKernel\Domain\Identity\Uuid;
use Tests\Core\Acceptance\Application;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;
use Tests\Core\Double\StateOf;

/**
 * A scenario's state, which the other contexts share: the application, the organizations it names, and the files
 * the user is about to upload. The scenarios use names, and the application uses ids.
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
    private string $exchange = '';

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

    #[Given('je lis l\'heure de :timeZone')]
    public function iReadTheClockOf(string $timeZone): void
    {
        $this->application->readTheClockOf(new \DateTimeZone($timeZone));
    }

    #[Given(':minutes minutes ont passé')]
    public function minutesPassed(int $minutes): void
    {
        $this->application->itIs($this->application->now()->modify(sprintf('+%d minutes', $minutes)));
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

    #[Given('je suis connecté en tant qu\'un autre prospecteur de :organization')]
    public function iAmSignedInAsAnotherProspectorOf(string $account): void
    {
        $this->application->signIn($this->aMemberOf('un autre prospecteur', $account, Role::User, Role::Prospector, Role::PersonalData)->id());
    }

    #[Given('je suis connecté en tant que :name')]
    public function iAmSignedInAs(string $name): void
    {
        $this->application->signIn($this->memberIdOf($name));
    }

    /**
     * An account member, with their person in PersonalData (membership), known by name in the scenario.
     */
    public function aMemberOf(string $name, string $account, Role ...$roles): Member
    {
        $person = Person::collect(new Gesture($this->accountIdOf($account), new MemberId($this->application->newIdentity()), $this->application->now()), new PersonId($this->application->newIdentity()), Purpose::Membership);
        $this->application->persons->save($person);
        $this->personIds[$name] = $person->id();
        $member = Member::register(new MemberId($this->application->newIdentity()), $this->accountIdOf($account), new ProfileId($person->id()->value), ...$roles);
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

    #[Then('c\'est introuvable, pour la raison :reason')]
    public function itIsNotFoundFor(string $reason): void
    {
        $this->itIsNotFound();
        Expect::same($reason, $this->application->refusal()?->reason);
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
     * The organization id that a request would carry for this name: a prospected organization's id, an account's
     * id (an account is not a prospected organization), or an id that nobody has.
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
        return $this->application->readers->readerFrom(new MemberId($this->application->signedIn()));
    }

    public function myPerson(): Person
    {
        return $this->application->persons->get($this->profileOf($this->me()));
    }

    public function profileOf(Member $member): PersonId
    {
        return new PersonId(StateOf::the($member)->object('personId', ProfileId::class)->value);
    }

    /**
     * The person that a request would carry for this name. An unknown name gets an id that nobody has.
     */
    public function rememberPerson(string $name, PersonId $id): void
    {
        $this->personIds[$name] = $id;
    }

    public function rememberMember(string $name, MemberId $id): void
    {
        $this->memberIds[$name] = $id;
    }

    public function findMemberId(string $name): ?MemberId
    {
        return $this->memberIds[$name] ?? null;
    }

    public function memberIdOf(string $name): MemberId
    {
        return $this->memberIds[$name] ?? throw new \LogicException(sprintf('No member "%s" in the scenario', $name));
    }

    public function nameOfMember(MemberId $id): string
    {
        $name = array_search($id, $this->memberIds, false);

        return false === $name ? throw new \LogicException(sprintf('No member %s in the scenario', $id)) : $name;
    }

    public function memberNamed(string $name): Member
    {
        return $this->application->members->get($this->memberIdOf($name));
    }

    public function personNamed(string $name): Person
    {
        return $this->application->persons->get($this->personIds[$name] ?? throw new \LogicException(sprintf('No person "%s" in the scenario', $name)));
    }

    /**
     * The name of a person, whatever context designates them.
     */
    public function nameOfPerson(Uuid $id): string
    {
        $name = array_search($id->value, array_map(strval(...), $this->personIds), true);

        return false === $name ? throw new \LogicException(sprintf('No person %s in the scenario', $id)) : $name;
    }

    /**
     * A person's name as the signed-in member reads it, in clear or masked, the way the clients name people.
     */
    public function nameOfPersonAsRead(Uuid $id): string
    {
        $person = $this->application->consultPersons(new ConsultPersons($this->application->signedIn(), [$id->value]))[$id->value] ?? null;

        return null === $person ? '' : trim(sprintf('%s %s', $person->firstName, $person->lastName));
    }

    /**
     * A member of this account who may manage personal data, so that the scenario arranges the account's persons
     * the way the business allows.
     */
    public function managerOfPersonalDataOf(AccountId $accountId): Reader
    {
        return new Reader(new MemberId($this->application->newIdentity()), $accountId, new PersonId($this->application->newIdentity()), true);
    }

    /**
     * The contact Prospecting knows by this name: the person PersonalData knows by it.
     */
    public function contactIdOf(string $name): ContactId
    {
        return new ContactId($this->personNamed($name)->id()->value);
    }

    public function personIdOf(string $name): string
    {
        return isset($this->personIds[$name]) ? $this->personIds[$name]->value : $this->application->newIdentity();
    }

    public function meAsProspector(): Prospector
    {
        return $this->application->prospectors->prospectorFrom(new MemberId($this->application->signedIn()));
    }

    /**
     * A prospector of the account that owns this organization. They author what a scenario arranges.
     */
    public function managerOf(Organization $organization): MemberId
    {
        return $this->prospectorOf($organization->accountId())->id;
    }

    public function prospectorOf(AccountId $accountId): Prospector
    {
        return new Prospector(new MemberId($this->application->newIdentity()), $accountId, managesPersonalData: true);
    }

    /**
     * What I do now, by hand.
     */
    public function myGesture(): Gesture
    {
        $me = $this->meAsProspector();

        return new Gesture($me->accountId, $me->id, $this->application->now());
    }

    /**
     * What a prospector of this account does now. They author what a scenario arranges.
     */
    public function aGestureIn(AccountId $accountId): Gesture
    {
        $prospector = $this->prospectorOf($accountId);

        return new Gesture($prospector->accountId, $prospector->id, $this->application->now());
    }

    /**
     * A member of this account, so that the scenario arranges the account's state the way the business allows.
     */
    public function memberOf(Account $account): Member
    {
        return Member::register(new MemberId($this->application->newIdentity()), $account->id(), new ProfileId($this->application->newIdentity()), Role::User);
    }

    /**
     * The scenarios' "Cet échange": the last exchange logged.
     */
    public function rememberTheExchange(string $id): void
    {
        $this->exchange = $id;
    }

    public function theExchange(): string
    {
        return $this->exchange;
    }

    public function upload(string $fileName): \SplFileInfo
    {
        return new \SplFileInfo($this->uploads.'/'.$fileName);
    }
}
