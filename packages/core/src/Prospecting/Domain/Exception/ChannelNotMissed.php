<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * Only a call, a meeting or a video call is missed, when the contact did not answer or come: a message is sent.
 */
final class ChannelNotMissed extends BusinessRuleViolation
{
}
