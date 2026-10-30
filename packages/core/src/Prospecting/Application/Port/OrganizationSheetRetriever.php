<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

/**
 * Reads the sheets of the organizations, without restoring them (ADR 0088); what their state offers is left to
 * the query.
 */
interface OrganizationSheetRetriever
{
    /**
     * @throws UnknownOrganization
     */
    public function get(OrganizationId $id): OrganizationSheet;

    /**
     * The organizations the account prospects that have this phone number.
     *
     * @return list<OrganizationSheet>
     */
    public function withPhoneNumber(AccountId $accountId, PhoneNumber $phoneNumber): array;
}
