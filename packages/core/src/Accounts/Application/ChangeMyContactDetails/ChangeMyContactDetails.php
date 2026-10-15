<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ChangeMyContactDetails;

use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ChangeMyContactDetails
{
    public function __construct(public MemberId $userId, public ContactDetails $contactDetails)
    {
    }
}
