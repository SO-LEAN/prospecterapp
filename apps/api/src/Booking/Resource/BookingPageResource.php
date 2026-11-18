<?php

namespace App\Booking\Resource;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * My booking page (ADR 0148): closed and empty until I set it.
 */
final readonly class BookingPageResource
{
    /**
     * @param list<WindowResource>         $windows
     * @param list<InactivePeriodResource> $inactivePeriods
     * @param list<string>                 $channels
     */
    public function __construct(
        #[OA\Property(description: 'Whether its public link offers slots')]
        public bool $open,
        #[OA\Property(type: 'array', items: new OA\Items(ref: new Model(type: WindowResource::class)), description: 'One a day at most, Monday first')]
        public array $windows,
        #[OA\Property(description: 'The time zone of the windows, by its name in the database of the time zones; none before they are set', example: 'Europe/Paris')]
        public ?string $timeZone,
        #[OA\Property(type: 'array', items: new OA\Items(ref: new Model(type: InactivePeriodResource::class)), description: 'Those not ended before the day of the reading, the soonest first')]
        public array $inactivePeriods,
        #[OA\Property(type: 'array', items: new OA\Items(type: 'string', enum: ['call', 'videoCall']))]
        public array $channels,
        #[OA\Property(description: 'The code of its public link, 22 characters of base 62; none before its first opening', example: 'AbCdEfGhIjKlMnOpQrStUv')]
        public ?string $code,
        #[OA\Property(description: 'Whether my calendar is connected, which invites the bookers: without it, the page does not open')]
        public bool $calendarConnected,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public BookingPageLinksResource $links,
    ) {
    }
}
