<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * An organization that an account prospects, whether or not it is placed on the map.
 */
final readonly class OrganizationFact implements Fact
{
    public function __construct(
        public AccountId $accountId,
        public OrganizationId $id,
        public int $revision,
        public bool $present,
        public bool $located,
    ) {
    }

    public function contribution(): FiguresChange
    {
        if (!$this->present) {
            return FiguresChange::none();
        }

        return new FiguresChange(activeOrganizations: 1, locatedOrganizations: $this->located ? 1 : 0);
    }
}
