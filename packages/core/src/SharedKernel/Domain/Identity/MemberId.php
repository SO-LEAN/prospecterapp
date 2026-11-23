<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Identity;

/**
 * An account member's id, published to the other contexts. A prospector is a member.
 */
final readonly class MemberId extends Uuid
{
}
