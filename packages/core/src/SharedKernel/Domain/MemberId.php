<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * The identity of a member of an account, published to the other contexts (a prospector is a member).
 */
final readonly class MemberId extends Uuid
{
}
