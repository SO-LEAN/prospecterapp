<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * The organization itself, such as its switchboard or its generic e-mail. The exchange or the to-do is kept for it
 * alone.
 */
final readonly class OrganizationCorrespondent extends Correspondent
{
    public function __construct(OrganizationId $organization)
    {
        parent::__construct($organization, null, [$organization]);
    }
}
