<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Who an exchange is with, and the organizations that keep it: a contact (`ContactCorrespondent`), a recipient of a
 * campaign (`RecipientCorrespondent`), or the organization itself (`OrganizationCorrespondent`).
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
     * Only a recipient of a campaign has a campaign to check.
     */
    public function ensureToHandle(): void
    {
    }

    public function campaign(): ?CampaignId
    {
        return null;
    }
}
