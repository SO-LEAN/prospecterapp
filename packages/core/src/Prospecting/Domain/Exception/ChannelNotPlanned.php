<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * Only a call, a meeting or a video call is planned: a message is just sent.
 */
final class ChannelNotPlanned extends BusinessRuleViolation
{
}
