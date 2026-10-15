<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account;

use Prospecter\Core\Domain\Organization\LegalIdentity;
use Prospecter\Core\Domain\Shared\ContactDetails;
use Prospecter\Core\Domain\Shared\File;
use Prospecter\Core\Domain\User\PersonName;

/**
 * My account as every use case on it presents it: me and my organization.
 */
final readonly class AccountResponse
{
    public function __construct(
        public string $userName,
        public PersonName $name,
        public ContactDetails $contactDetails,
        public ?File $picture,
        public LegalIdentity $organizationIdentity,
        public ?File $organizationLogo,
    ) {
    }
}
