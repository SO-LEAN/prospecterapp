<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Booking\Application\Port\PublicBookingPageRetriever;
use Prospecter\Core\Booking\Application\PublicBookingPage;
use Prospecter\Core\Booking\Domain\Model\Channel;
use Prospecter\Core\Booking\Domain\Model\OfferedChannels;

/**
 * The DynamoDB retriever in the scenarios: the page the code it has now leads to, read without its getters.
 */
final readonly class InMemoryPublicBookingPageRetriever implements PublicBookingPageRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function byCode(string $code): ?PublicBookingPage
    {
        $page = $this->database->bookingPageAt($code);
        if (null === $page) {
            return null;
        }
        $state = StateOf::the($page);
        /** @var OfferedChannels $channels */
        $channels = $state['channels'];

        return new PublicBookingPage((bool) $state['open'], array_map(static fn (Channel $channel): string => $channel->value, $channels->channels));
    }
}
