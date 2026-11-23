<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\OfferChannels;

use Prospecter\Core\Booking\Application\BookingPages;
use Prospecter\Core\Booking\Domain\Model\Channel;
use Prospecter\Core\Booking\Domain\Model\OfferedChannels;
use Prospecter\Core\Booking\Domain\Repository\BookingPageRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\PublishesEvents;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class OfferChannelsHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private BookingPages $bookingPages,
        private BookingPageRepository $pages,
        private Events $events,
    ) {
    }

    public function __invoke(OfferChannels $command): void
    {
        $page = $this->bookingPages->of(new MemberId($command->memberId), $command->executedAt);
        $page->offer(new OfferedChannels(...array_map(self::channel(...), $command->channels)), $command->executedAt);
        $this->pages->save($page);
        $this->publishWhatHappenedTo($page);
    }

    private static function channel(string $channel): Channel
    {
        return Channel::tryFrom($channel) ?? throw BusinessRuleViolation::because('A booking page can only offer a call or a video call', 'channels', context: ['channel' => $channel]);
    }
}
