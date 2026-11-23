<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Policy;

use Prospecter\Core\Campaigns\Application\ForgetTagsOfUnprospectedOrganization\ForgetTagsOfUnprospectedOrganization;
use Prospecter\Core\Prospecting\Contract\Event\OrganizationFactsChanged;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When the account no longer prospects an organization, its terminal fact forgets its tags (ADR 0138).
 */
final readonly class ForgetTagsOfUnprospectedOrganizationPolicy implements Policy
{
    public function __invoke(OrganizationFactsChanged $event): ?ForgetTagsOfUnprospectedOrganization
    {
        return $event->present ? null : new ForgetTagsOfUnprospectedOrganization($event->accountId, $event->organizationId, $event->occurredAt);
    }
}
