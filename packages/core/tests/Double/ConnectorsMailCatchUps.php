<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Contract\Service\WhichMailCatchUpIntents\KeptMailCatchUpIntentsOfMany;
use Prospecter\Core\Connectors\Contract\Service\WhichMailCatchUpIntents\WhichMailCatchUpIntents;
use Prospecter\Core\Connectors\Contract\Service\WhichMailCatchUpIntents\WhichMailCatchUpIntentsService;
use Prospecter\Core\Prospecting\Application\Port\MailCatchUpRetriever;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * The API's anticorruption layer, in the scenarios (ADR 0045, 0089, 0217). Connectors publishes what a member may do
 * with the e-mails exchanged with an organization, asked about a set of one.
 */
final readonly class ConnectorsMailCatchUps implements MailCatchUpRetriever
{
    public function __construct(private WhichMailCatchUpIntentsService $whichMailCatchUpIntentsService)
    {
    }

    public function intentsOn(MemberId $reader, OrganizationId $organization): array
    {
        $kept = new KeptMailCatchUpIntentsOfMany();
        ($this->whichMailCatchUpIntentsService)(new WhichMailCatchUpIntents($reader->value, [], [$organization->value]), $kept);

        return $kept->organizations[0]->possibleIntents ?? [];
    }
}
