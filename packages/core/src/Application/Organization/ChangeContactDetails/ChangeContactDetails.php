<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Organization\ChangeContactDetails;

use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\Shared\ContactDetails;
use Prospecter\Core\Domain\User\UserId;

final readonly class ChangeContactDetails
{
    public function __construct(
        public UserId $prospectorId,
        public OrganizationId $id,
        public ContactDetails $contactDetails,
    ) {
    }
}
