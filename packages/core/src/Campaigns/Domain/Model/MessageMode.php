<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\Campaigns\Domain\Exception\UnknownMessageMode;

/**
 * How an AI running the campaign treats its message for each person. The AI sends it as rendered for them, or adapts it
 * to them and keeps the message's idea.
 */
enum MessageMode: string
{
    case AsIs = 'asIs';
    case Adapted = 'adapted';

    /**
     * As is when the member does not choose.
     *
     * @throws UnknownMessageMode
     */
    public static function named(?string $name): self
    {
        return null === $name ? self::AsIs : self::tryFrom($name) ?? throw UnknownMessageMode::named($name);
    }
}
