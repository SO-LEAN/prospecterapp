<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

/**
 * The channels the host offers the bookers, each once, in the order of `Channel`.
 */
final readonly class OfferedChannels
{
    /**
     * @var list<Channel>
     */
    public array $channels;

    public function __construct(Channel ...$channels)
    {
        $this->channels = array_values(array_filter(Channel::cases(), static fn (Channel $channel): bool => in_array($channel, $channels, true)));
    }

    public function offerSome(): bool
    {
        return [] !== $this->channels;
    }
}
