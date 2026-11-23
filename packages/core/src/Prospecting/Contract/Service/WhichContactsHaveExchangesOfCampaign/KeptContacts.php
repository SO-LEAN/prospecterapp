<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\WhichContactsHaveExchangesOfCampaign;

/**
 * Keeps the answer as it is, for a client that reads it instead of showing it (ADR 0085).
 */
final class KeptContacts implements WhichContactsHaveExchangesOfCampaignOutput
{
    /**
     * @var list<string>
     */
    public private(set) array $contactIds = [];

    /**
     * @param list<string> $contactIds
     */
    public function present(array $contactIds): void
    {
        $this->contactIds = $contactIds;
    }
}
