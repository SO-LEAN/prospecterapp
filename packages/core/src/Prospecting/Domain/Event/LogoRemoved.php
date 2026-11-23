<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * The organization's logo was removed. Its file, its URL in the storage, remains to be discarded.
 */
final readonly class LogoRemoved extends OrganizationEvent
{
    public function __construct(AccountId $accountId, MemberId $by, OrganizationId $organization, public string $logo, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $organization, $occurredAt);
    }
}
