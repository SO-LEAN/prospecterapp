<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization;

use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\Shared\ContactDetails;

final readonly class ChangeContactDetailsRequest
{
    public function __construct(
        public OrganizationId $id,
        public ContactDetails $contactDetails,
    ) {
    }
}
