<?php

declare(strict_types=1);

namespace Prospecter\Core\Domain\User;

use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\Shared\BusinessRuleViolation;
use Prospecter\Core\Domain\Shared\ContactDetails;
use Prospecter\Core\Domain\Shared\Email;
use Prospecter\Core\Domain\Shared\File;

/**
 * Aggregate root: a person using Prospecter for its organization. Signing in is not its business (ADR 0011).
 */
class User
{
    private readonly string $id;
    private readonly string $organizationId;
    private string $userName;
    /** @var list<string> */
    private readonly array $roles;
    private ?string $firstName = null;
    private ?string $lastName = null;
    private ?string $email = null;
    private ?string $phoneNumber = null;
    private ?string $language = null;
    private ?string $pictureUrl = null;
    private ?string $pictureExtension = null;
    private ?int $pictureSize = null;

    private function __construct(UserId $id, OrganizationId $organizationId, string $userName, Role ...$roles)
    {
        self::ensureUserNameIsGiven($userName);
        $this->id = $id->value;
        $this->organizationId = $organizationId->value;
        $this->userName = $userName;
        $this->roles = array_map(static fn (Role $role): string => $role->value, $roles);
    }

    public static function register(UserId $id, OrganizationId $organizationId, string $userName, Role ...$roles): self
    {
        return new self($id, $organizationId, $userName, ...$roles);
    }

    public function id(): UserId
    {
        return new UserId($this->id);
    }

    public function organizationId(): OrganizationId
    {
        return new OrganizationId($this->organizationId);
    }

    public function userName(): string
    {
        return $this->userName;
    }

    /**
     * @return list<Role>
     */
    public function roles(): array
    {
        return array_map(Role::from(...), $this->roles);
    }

    public function name(): PersonName
    {
        return new PersonName($this->firstName, $this->lastName);
    }

    public function contactDetails(): ContactDetails
    {
        return new ContactDetails(Email::orNone($this->email), $this->phoneNumber, $this->language);
    }

    public function picture(): ?File
    {
        return null === $this->pictureUrl ? null : new File($this->pictureUrl, (string) $this->pictureExtension, (int) $this->pictureSize);
    }

    public function rename(PersonName $name): void
    {
        $this->firstName = $name->firstName;
        $this->lastName = $name->lastName;
    }

    public function changeUserName(string $userName): void
    {
        self::ensureUserNameIsGiven($userName);
        $this->userName = $userName;
    }

    public function changeContactDetails(ContactDetails $contactDetails): void
    {
        $this->email = $contactDetails->email?->value;
        $this->phoneNumber = $contactDetails->phoneNumber;
        $this->language = $contactDetails->language;
    }

    public function changePicture(File $picture): void
    {
        $this->pictureUrl = $picture->url;
        $this->pictureExtension = $picture->extension;
        $this->pictureSize = $picture->size;
    }

    private static function ensureUserNameIsGiven(string $userName): void
    {
        if ('' === trim($userName)) {
            throw new BusinessRuleViolation('A user name is required', 'userName');
        }
    }
}
