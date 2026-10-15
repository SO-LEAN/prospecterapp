<?php

declare(strict_types=1);

namespace Prospecter\Core\Domain\User;

use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\Shared\BusinessRuleViolation;
use Prospecter\Core\Domain\Shared\ContactDetails;
use Prospecter\Core\Domain\Shared\File;

/**
 * Aggregate root: a person using Prospecter for its organization. Signing in is not its business (ADR 0011).
 */
class User
{
    /** @var list<Role> */
    private readonly array $roles;
    private PersonName $name;
    private ContactDetails $contactDetails;
    private ?File $picture = null;

    private function __construct(
        private readonly UserId $id,
        private readonly OrganizationId $organizationId,
        private string $userName,
        Role ...$roles,
    ) {
        self::ensureUserNameIsGiven($userName);
        $this->roles = array_values($roles);
        $this->name = new PersonName();
        $this->contactDetails = new ContactDetails();
    }

    public static function register(UserId $id, OrganizationId $organizationId, string $userName, Role ...$roles): self
    {
        return new self($id, $organizationId, $userName, ...$roles);
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
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
        return $this->roles;
    }

    public function name(): PersonName
    {
        return $this->name;
    }

    public function contactDetails(): ContactDetails
    {
        return $this->contactDetails;
    }

    public function picture(): ?File
    {
        return $this->picture;
    }

    /**
     * A user may change everything of their own account.
     *
     * @return list<UserIntent>
     */
    public function possibleIntents(): array
    {
        return UserIntent::cases();
    }

    public function rename(PersonName $name): void
    {
        $this->name = $name;
    }

    public function changeUserName(string $userName): void
    {
        self::ensureUserNameIsGiven($userName);
        $this->userName = $userName;
    }

    public function changeContactDetails(ContactDetails $contactDetails): void
    {
        $this->contactDetails = $contactDetails;
    }

    public function changePicture(File $picture): void
    {
        $this->picture = $picture;
    }

    private static function ensureUserNameIsGiven(string $userName): void
    {
        if ('' === trim($userName)) {
            throw new BusinessRuleViolation('A user name is required', 'userName');
        }
    }
}
