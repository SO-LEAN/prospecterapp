<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\Campaigns\Domain\Model\Channel;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A recipient has the means the channel of the campaign needs: an e-mail, a phone, a mobile or a LinkedIn profile.
 */
final class UnreachableContact extends BusinessRuleViolation
{
    public static function by(string $contact, Channel $channel): self
    {
        return self::because(sprintf('%s cannot be reached by %s', $contact, $channel->means()), 'contactId', context: ['contact' => $contact, 'channel' => $channel->value]);
    }
}
