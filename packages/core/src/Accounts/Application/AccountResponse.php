<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application;

use Prospecter\Core\Accounts\Domain\AccountIntent;
use Prospecter\Core\Accounts\Domain\MemberIntent;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * My membership as every use case on it presents it: who I am in the account (my personal data are read in
 * PersonalData under my person id) and my organization.
 */
final readonly class AccountResponse
{
    /**
     * @param list<MemberIntent>  $possibleIntents what I may change of my account
     * @param list<AccountIntent> $accountIntents  what I may change of my organization
     */
    public function __construct(
        public string $userName,
        public PersonId $personId,
        public array $possibleIntents,
        public LegalIdentity $organizationIdentity,
        public ?File $organizationLogo,
        public array $accountIntents,
    ) {
    }
}
