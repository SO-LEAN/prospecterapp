<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account;

use Prospecter\Core\Domain\Organization\LegalIdentity;
use Prospecter\Core\Domain\Organization\OrganizationIntent;
use Prospecter\Core\Domain\Shared\ContactDetails;
use Prospecter\Core\Domain\Shared\File;
use Prospecter\Core\Domain\User\PersonName;
use Prospecter\Core\Domain\User\UserIntent;

/**
 * My account as every use case on it presents it: me and my organization.
 */
final readonly class AccountResponse
{
    /**
     * @param list<UserIntent>         $possibleIntents     what I may change of my account
     * @param list<OrganizationIntent> $organizationIntents what I may change of my organization
     */
    public function __construct(
        public string $userName,
        public PersonName $name,
        public ContactDetails $contactDetails,
        public ?File $picture,
        public array $possibleIntents,
        public LegalIdentity $organizationIdentity,
        public ?File $organizationLogo,
        public array $organizationIntents,
    ) {
    }
}
