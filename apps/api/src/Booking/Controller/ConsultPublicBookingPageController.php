<?php

declare(strict_types=1);

namespace App\Booking\Controller;

use App\Booking\PublicBookingPageResourcePresenter;
use App\Booking\PublicBookingRoutes;
use App\Booking\Resource\PublicBookingPageResource;
use App\Shared\ImageResourceFactory;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Booking\Application\ConsultPublicBookingPage\ConsultPublicBookingPage;
use Prospecter\Core\Booking\Application\PublicBookingPage;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Anyone with the link can read the page without signing in (ADR 0151, 0153). The firewall lets every call through.
 */
#[OA\Tag('Booking')]
final readonly class ConsultPublicBookingPageController
{
    /**
     * Short, so that a closed link or a taken slot shows up within this delay (ADR 0153).
     */
    private const int KEPT_SECONDS = 30;

    public function __construct(
        private QueryHandler $consultPublicBookingPage,
        private ClockInterface $clock,
        private ImageResourceFactory $imageResourceFactory,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/booking-pages/{code}', name: 'api_public_booking_page', defaults: [PublicBookingRoutes::LIMITED_BY_ITS_CODE => true], methods: ['GET'])]
    #[OA\Get(summary: 'Get the booking page that a public link leads to, without signing in: its free slots for 14 days from tomorrow, and how its host meets bookers. A closed, changed or unknown link, an oversized code, or a host without a connected calendar shows nothing, and the answer does not say why', security: [])]
    #[OA\Response(response: 200, description: 'The booking page, kept 30 seconds', content: new Model(type: PublicBookingPageResource::class))]
    #[OA\Response(response: 429, description: 'This link was opened too often in the last minute: `tooManyRequests`, try again after `Retry-After` seconds', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $code): JsonResponse
    {
        $presenter = new PublicBookingPageResourcePresenter($this->imageResourceFactory);
        if (PublicBookingRoutes::isOversized($code)) {
            $presenter->present(PublicBookingPage::unavailable());
        } else {
            $this->consultPublicBookingPage->__invoke(new ConsultPublicBookingPage($code, $this->clock->now()), $presenter);
        }

        return $this->jsonResponses->of($presenter->resource)->setPublic()->setMaxAge(self::KEPT_SECONDS);
    }
}
