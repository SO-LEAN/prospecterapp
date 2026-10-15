<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account\ChangeMyContactDetails;

use Prospecter\Core\Domain\Shared\ContactDetails;
use Prospecter\Core\Domain\User\UserId;

final readonly class ChangeMyContactDetailsRequest
{
    public function __construct(public UserId $userId, public ContactDetails $contactDetails)
    {
    }
}
