<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\BusinessRuleViolation;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Aggregate root: a person using Prospecter for its account. Signing in is not its business (ADR 0011).
 */
class Member
{
    /** @var list<Role> */
    private readonly array $roles;
    private PersonName $name;
    private ContactDetails $contactDetails;
    private ?File $picture = null;

    private function __construct(
        private readonly MemberId $id,
        private readonly AccountId $accountId,
        private string $userName,
        Role ...$roles,
    ) {
        self::ensureUserNameIsGiven($userName);
        $this->roles = array_values($roles);
        $this->name = new PersonName();
        $this->contactDetails = new ContactDetails();
    }

    public static function register(MemberId $id, AccountId $accountId, string $userName, Role ...$roles): self
    {
        return new self($id, $accountId, $userName, ...$roles);
    }

    public function id(): MemberId
    {
        return $this->id;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
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
     * @return list<MemberIntent>
     */
    public function possibleIntents(): array
    {
        return MemberIntent::cases();
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
