<?php

declare(strict_types=1);

namespace App\Booking\Resource;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * My booking page (ADR 0148). It stays closed and empty until I set it up.
 */
final readonly class BookingPageResource
{
    /**
     * @param list<WindowResource>         $windows
     * @param list<InactivePeriodResource> $inactivePeriods
     * @param list<string>                 $channels
     */
    public function __construct(
        #[OA\Property(description: 'Whether the page\'s public link offers slots')]
        public bool $open,
        #[OA\Property(type: 'array', items: new OA\Items(ref: new Model(type: WindowResource::class)), description: 'One a day at most, Monday first')]
        public array $windows,
        #[OA\Property(description: 'The windows\' time zone, by its name in the time zone database. Null until the windows are set', example: 'Europe/Paris')]
        public ?string $timeZone,
        #[OA\Property(type: 'array', items: new OA\Items(ref: new Model(type: InactivePeriodResource::class)), description: 'The periods that had not ended before the reading\'s day, the soonest first')]
        public array $inactivePeriods,
        #[OA\Property(type: 'array', items: new OA\Items(type: 'string', enum: ['call', 'videoCall']))]
        public array $channels,
        #[OA\Property(description: 'The public link\'s code, 22 base-62 characters. Null until the first opening', example: 'AbCdEfGhIjKlMnOpQrStUv')]
        public ?string $code,
        #[OA\Property(description: 'Whether my calendar is connected. The calendar invites the bookers, so the page does not open without it')]
        public bool $calendarConnected,
        #[OA\Property(description: 'Whether the page is open but offers nothing, because my calendar is not connected. The members are told so')]
        public bool $suspended,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public BookingPageLinksResource $links,
    ) {
    }
}
