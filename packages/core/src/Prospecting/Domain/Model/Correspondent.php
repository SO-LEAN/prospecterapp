<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Who an exchange or a to-do is with, and the organizations that keep it. It is a contact (`ContactCorrespondent`),
 * a campaign's recipient (`RecipientCorrespondent`, for an exchange only), or the organization itself
 * (`OrganizationCorrespondent`).
 */
abstract readonly class Correspondent
{
    /**
     * @param list<OrganizationId> $keptFor
     */
    protected function __construct(
        public PersonId|OrganizationId $identity,
        public ?PersonId $contact,
        public array $keptFor,
    ) {
    }

    /**
     * Only a campaign's recipient has a campaign to check.
     */
    public function ensureToHandle(): void
    {
    }

    public function campaign(): ?CampaignId
    {
        return null;
    }
}
