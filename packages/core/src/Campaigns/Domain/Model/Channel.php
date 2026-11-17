<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\Campaigns\Domain\Exception\UnknownChannel;

/**
 * How the member contacts each person of a campaign themselves: each channel needs the means its automatic tag says
 * the contact has.
 */
enum Channel: string
{
    case Email = 'email';
    case Call = 'call';
    case Sms = 'sms';
    case LinkedIn = 'linkedIn';

    /**
     * @throws UnknownChannel
     */
    public static function named(string $name): self
    {
        return self::tryFrom($name) ?? throw UnknownChannel::named($name);
    }

    #[\NoDiscard]
    public function tag(): Tag
    {
        return Tag::written(match ($this) {
            self::Email => '#EMAIL',
            self::Call => '#PHONE',
            self::Sms => '#SMS',
            self::LinkedIn => '#LINKEDIN',
        });
    }

    /**
     * The means a contact needs to be reached this way, in words.
     */
    #[\NoDiscard]
    public function means(): string
    {
        return match ($this) {
            self::Email => 'email',
            self::Call => 'phone',
            self::Sms => 'SMS',
            self::LinkedIn => 'LinkedIn',
        };
    }
}
