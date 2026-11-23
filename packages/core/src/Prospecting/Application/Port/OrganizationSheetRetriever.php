<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Port;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Contact\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Reads the organizations' sheets without restoring the organizations (ADR 0088). Each sheet says what the
 * organization's state currently offers, as `OrganizationPossibilities` decides it.
 */
interface OrganizationSheetRetriever
{
    /**
     * @throws UnknownOrganization
     */
    public function get(OrganizationId $id): OrganizationSheet;

    /**
     * Returns the organizations the account prospects that have this phone number.
     *
     * @return list<OrganizationSheet>
     */
    public function withPhoneNumber(AccountId $accountId, PhoneNumber $phoneNumber): array;
}
