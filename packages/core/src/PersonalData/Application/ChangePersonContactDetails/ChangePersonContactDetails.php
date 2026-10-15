<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ChangePersonContactDetails;

use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class ChangePersonContactDetails
{
    public function __construct(
        public MemberId $readerId,
        public PersonId $id,
        public ContactDetails $contactDetails,
    ) {
    }
}
