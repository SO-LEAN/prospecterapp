<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Reads what Connectors lets a member do with the e-mails already exchanged with an organization itself (ADR 0217).
 */
interface MailCatchUpRetriever
{
    /**
     * @return list<string> `CatchUpGmail`, or nothing
     */
    public function intentsOn(MemberId $reader, OrganizationId $organization): array;
}
