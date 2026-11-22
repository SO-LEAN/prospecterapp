<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A colleague an administrator registers: the member they become, the profile they are given and the roles they
 * work with.
 */
final readonly class Newcomer
{
    /**
     * @param list<Role> $roles
     */
    public function __construct(
        public MemberId $id,
        public PersonId $profile,
        public array $roles,
    ) {
    }
}
