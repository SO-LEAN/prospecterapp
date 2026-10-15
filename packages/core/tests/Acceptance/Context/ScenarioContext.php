<?php

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Hook\AfterScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use PHPUnit\Framework\Assert;
use Prospecter\Core\Entity\Organization;
use Prospecter\Core\Entity\User;
use Prospecter\Core\Exception\UseCase\NotFoundException;
use SplFileInfo;
use Tests\Core\Acceptance\Application;
use Tests\Core\Double\SignedInUser;

/**
 * The state of a scenario, shared by the other contexts: the application, the people and
 * organizations it knows by name, and the files the user is about to upload.
 */
final class ScenarioContext implements Context
{
    public readonly Application $application;
    private readonly string $uploads;

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
    public function anOrganizationUsingProspecter(string $name): Organization
    {
        $organization = new Organization();
        $organization->setCorporateName($name);

        return $this->application->organizations->create($organization);
    }

    #[Given('je suis connecté en tant que prospecteur de :organization')]
    public function iAmSignedInAsProspectorOf(string $organization): void
    {
        $this->signIn('prospector', $organization, ['ROLE_USER', 'ROLE_PROSPECTOR']);
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
        Assert::assertNotNull($this->application->refusal(), 'the use case was expected to refuse');
        Assert::assertSame($message, $this->application->refusal()->getMessage());
    }

    #[Then('c\'est introuvable')]
    public function itIsNotFound(): void
    {
        Assert::assertInstanceOf(NotFoundException::class, $this->application->refusal());
    }

    #[Then('je suis informé que :message')]
    public function iAmNotifiedThat(string $message): void
    {
        Assert::assertContains(['success', $message], $this->application->notifications->all());
    }

    /**
     * @param list<string> $roles
     */
    public function signIn(string $userName, string $organizationName, array $roles): User
    {
        $user = new User();
        $user->setUserName($userName);
        $user->setRoles($roles);
        $user->setOrganization($this->organizationNamed($organizationName));
        $this->application->users->create($user);

        $this->application->signIn(new SignedInUser($user->getId(), $user->getOrganization()->getId(), $roles));

        return $user;
    }

    public function organizationNamed(string $name): Organization
    {
        return $this->application->organizations->findOneBy(['corporateName' => $name])
            ?? throw new \LogicException(sprintf('No organization "%s" in the scenario', $name));
    }

    /**
     * The id a request would carry for this name; an unknown name gets an id nobody has.
     */
    public function idOf(string $organizationName): int
    {
        return $this->application->organizations->findOneBy(['corporateName' => $organizationName])?->getId() ?? 999_999;
    }

    public function me(): User
    {
        return $this->application->users->get($this->application->signedIn()->getUserId());
    }

    public function upload(?string $fileName): ?SplFileInfo
    {
        return null === $fileName ? null : new SplFileInfo($this->uploads.'/'.$fileName);
    }
}
