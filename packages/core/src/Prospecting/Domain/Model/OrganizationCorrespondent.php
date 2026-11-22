<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * The organization itself (its switchboard, its generic e-mail), the exchange or the to-do kept for it alone.
 */
final readonly class OrganizationCorrespondent extends Correspondent
{
    public function __construct(OrganizationId $organization)
    {
        parent::__construct($organization, null, [$organization]);
    }
}
