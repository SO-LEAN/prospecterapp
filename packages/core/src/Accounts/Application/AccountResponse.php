<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application;

use Prospecter\Core\Accounts\Domain\AccountIntent;
use Prospecter\Core\Accounts\Domain\MemberIntent;
use Prospecter\Core\Accounts\Domain\PersonName;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\File;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;

/**
 * My account as every use case on it presents it: me and my organization.
 */
final readonly class AccountResponse
{
    /**
     * @param list<MemberIntent>  $possibleIntents what I may change of my account
     * @param list<AccountIntent> $accountIntents  what I may change of my organization
     */
    public function __construct(
        public string $userName,
        public PersonName $name,
        public ContactDetails $contactDetails,
        public ?File $picture,
        public array $possibleIntents,
        public LegalIdentity $organizationIdentity,
        public ?File $organizationLogo,
        public array $accountIntents,
    ) {
    }
}
