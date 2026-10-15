<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization\StartProspecting;

use Prospecter\Core\Domain\Organization\Address;
use Prospecter\Core\Domain\Organization\LegalIdentity;
use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\Shared\ContactDetails;
use Prospecter\Core\Domain\User\UserId;

final readonly class StartProspecting
{
    public function __construct(
        public UserId $prospectorId,
        public OrganizationId $id,
        public LegalIdentity $legalIdentity,
        public ContactDetails $contactDetails,
        public ?Address $address = null,
        public ?string $observations = null,
        public ?OrganizationId $holdingId = null,
    ) {
    }
}
