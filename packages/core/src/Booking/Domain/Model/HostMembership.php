<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

/**
 * What Accounts knows of a host: the person they are, and the name of the organization whose account they belong to
 * when it has one.
 */
final readonly class HostMembership
{
    public function __construct(
        public string $personId,
        public ?string $accountName,
    ) {
    }
}
