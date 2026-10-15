<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account;

use Prospecter\Core\Domain\Shared\ContactDetails;

final readonly class ChangeMyContactDetailsRequest
{
    public function __construct(public ContactDetails $contactDetails)
    {
    }
}
