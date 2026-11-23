<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Contract\Service\WhichRecipientsArePending;

/**
 * The service that Campaigns offers the other contexts (ADR 0045, 0089). It refuses nothing: what is unknown is absent.
 */
interface WhichRecipientsArePendingService
{
    public function __invoke(WhichRecipientsArePending $query, WhichRecipientsArePendingOutput $output): void;
}
