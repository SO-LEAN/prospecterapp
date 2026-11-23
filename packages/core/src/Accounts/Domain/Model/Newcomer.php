<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A colleague that an administrator registers. It holds the colleague's member id, their profile and the roles they
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
