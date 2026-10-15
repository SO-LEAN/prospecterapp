<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Hook\AfterScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Prospecter\Core\Application\Exception\NotFoundException;
use Prospecter\Core\Domain\Organization\LegalIdentity;
use Prospecter\Core\Domain\Organization\Organization;
use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\User\Role;
use Prospecter\Core\Domain\User\User;
use Tests\Core\Acceptance\Application;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Double\SignedInUser;

/**
 * The state of a scenario, shared by the other contexts: the application, the organizations it names
 * (scenarios speak of names, the application of identities), and the files the user is about to upload.
 */
final class ScenarioContext implements Context
{
    public readonly Application $application;
    private readonly string $uploads;
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
        $organization = Organization::usingProspecter($this->application->organizations->nextIdentity(), new LegalIdentity($name));
        $this->save($name, $organization);
    }

    #[Given('je suis connecté en tant que prospecteur de :organization')]
    public function iAmSignedInAsProspectorOf(string $organization): void
    {
        $user = User::register($this->application->users->nextIdentity(), $this->idOf($organization), 'prospector', Role::User, Role::Prospector);
        $this->application->users->save($user);
        $this->application->signIn(new SignedInUser($user->id(), $user->organizationId(), ...$user->roles()));
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
        Expect::that($this->application->refusal() instanceof NotFoundException, sprintf('Not found was expected, got: %s', get_debug_type($this->application->refusal())));
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

    /**
     * The identity a request would carry for this name; an unknown name gets an identity nobody has.
     */
    public function idOf(string $name): OrganizationId
    {
        return $this->organizationIds[$name] ?? $this->application->organizations->nextIdentity();
    }

    public function nameOf(OrganizationId $id): string
    {
        foreach ($this->organizationIds as $name => $knownId) {
            if ($knownId->equals($id)) {
                return $name;
            }
        }

        throw new \LogicException(sprintf('No organization named %s in the scenario', $id));
    }

    public function organizationNamed(string $name): Organization
    {
        return $this->application->organizations->get($this->organizationIds[$name] ?? throw new \LogicException(sprintf('No organization "%s" in the scenario', $name)));
    }

    public function me(): User
    {
        return $this->application->users->get($this->application->signedIn()->getUserId());
    }

    public function upload(string $fileName): \SplFileInfo
    {
        return new \SplFileInfo($this->uploads.'/'.$fileName);
    }
}
