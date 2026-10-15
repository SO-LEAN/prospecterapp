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
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\NotFound;
use Prospecter\Core\SharedKernel\Domain\Uuid;
use Tests\Core\Acceptance\Application;
use Tests\Core\Acceptance\Expect;

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
        $this->openAccount($name, Account::open($this->application->accounts->nextIdentity(), new LegalIdentity($name)));
    }

    #[Given('je suis connecté en tant que prospecteur de :organization')]
    public function iAmSignedInAsProspectorOf(string $account): void
    {
        $member = Member::register($this->application->members->nextIdentity(), $this->accountIdOf($account), 'prospector', Role::User, Role::Prospector);
        $this->application->members->save($member);
        $this->application->signIn($member->id());
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
        Expect::that($this->application->refusal() instanceof NotFound, sprintf('Not found was expected, got: %s', get_debug_type($this->application->refusal())));
    }

    public function save(string $name, Organization $organization): Organization
    {
        $this->application->organizations->save($organization);
        $this->remember($name, $organization->id());

        return $organization;
    }

    public function remember(?string $name, OrganizationId $id): void
    {
        if (null !== $name) {
            $this->organizationIds[$name] = $id;
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
    public function idOf(string $name): OrganizationId
    {
        return $this->organizationIds[$name]
            ?? (isset($this->accountIds[$name]) ? new OrganizationId($this->accountIds[$name]->value) : $this->application->organizations->nextIdentity());
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

    public function organizationNamed(string $name): Organization
    {
        return $this->application->organizations->get($this->organizationIds[$name] ?? throw new \LogicException(sprintf('No organization "%s" in the scenario', $name)));
    }

    public function me(): Member
    {
        return $this->application->members->get($this->application->signedIn());
    }

    public function meAsProspector(): Prospector
    {
        return $this->application->prospectors->get($this->application->signedIn());
    }

    /**
     * A prospector of the account owning this organization, to arrange its state the way the business allows.
     */
    public function managerOf(Organization $organization): Prospector
    {
        return $this->prospectorOf($organization->ownerId());
    }

    public function prospectorOf(AccountId $accountId): Prospector
    {
        return new Prospector($this->application->members->nextIdentity(), $accountId);
    }

    /**
     * A member of this account, to arrange its state the way the business allows.
     */
    public function memberOf(Account $account): Member
    {
        return Member::register($this->application->members->nextIdentity(), $account->id(), 'someone', Role::User);
    }

    public function upload(string $fileName): \SplFileInfo
    {
        return new \SplFileInfo($this->uploads.'/'.$fileName);
    }
}
