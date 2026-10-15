<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\StartProspecting;

use Prospecter\Core\Prospecting\Domain\Address;
use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class StartProspecting
{
    public function __construct(
        public MemberId $prospectorId,
        public OrganizationId $id,
        public LegalIdentity $legalIdentity,
        public ContactDetails $contactDetails,
        public ?Address $address = null,
        public ?string $observations = null,
        public ?OrganizationId $holdingId = null,
    ) {
    }
}
