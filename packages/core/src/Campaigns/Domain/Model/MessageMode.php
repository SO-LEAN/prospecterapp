<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\Campaigns\Domain\Exception\UnknownMessageMode;

/**
 * How an AI leading the campaign treats its message for each person: sent as it is rendered for them, or adapted to
 * them, the idea of the message kept.
 */
enum MessageMode: string
{
    case AsIs = 'asIs';
    case Adapted = 'adapted';

    /**
     * As is when the member does not say.
     *
     * @throws UnknownMessageMode
     */
    public static function named(?string $name): self
    {
        return null === $name ? self::AsIs : self::tryFrom($name) ?? throw UnknownMessageMode::named($name);
    }
}
