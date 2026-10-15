<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ChangeContactDetails;

use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ChangeContactDetails
{
    public function __construct(
        public MemberId $prospectorId,
        public OrganizationId $id,
        public ContactDetails $contactDetails,
    ) {
    }
}
