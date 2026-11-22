<?php

declare(strict_types=1);

namespace App\Booking\Controller;

use App\Booking\PublicBookingPageResourcePresenter;
use App\Booking\PublicBookingRoutes;
use App\Booking\Resource\PublicBookingPageResource;
use App\Shared\ImageResourceFactory;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Booking\Application\ConsultPublicBookingPage\ConsultPublicBookingPage;
use Prospecter\Core\Booking\Application\PublicBookingPage;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Read without signing in, by whomever holds the link (ADR 0151, 0153): its firewall lets every call through.
 */
#[OA\Tag('Booking')]
final class ConsultPublicBookingPageController extends AbstractController
{
    /**
     * Short: a link closed or a slot taken shows within it (ADR 0153).
     */
    private const int KEPT_SECONDS = 30;

    public function __construct(
        private readonly QueryHandler $consultPublicBookingPage,
        private readonly ClockInterface $clock,
        private readonly ImageResourceFactory $imageResourceFactory,
    ) {
    }

    #[Route('/api/booking-pages/{code}', name: 'api_public_booking_page', defaults: [PublicBookingRoutes::LIMITED_BY_ITS_CODE => true], methods: ['GET'])]
    #[OA\Get(summary: 'Consult the booking page a public link leads to, without signing in: its free slots from tomorrow for 14 days and how its host meets the bookers; a link closed, changed, unknown, of a code oversized, or whose host has no connected calendar shows nothing, without saying why', security: [])]
    #[OA\Response(response: 200, description: 'The booking page, kept 30 seconds', content: new Model(type: PublicBookingPageResource::class))]
    #[OA\Response(response: 429, description: 'This link was opened too often in the last minute: `tooManyRequests`, try again after `Retry-After` seconds', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $code): JsonResponse
    {
        $presenter = new PublicBookingPageResourcePresenter($this->imageResourceFactory);
        if (PublicBookingRoutes::isOversized($code)) {
            $presenter->present(PublicBookingPage::unavailable());
        } else {
            ($this->consultPublicBookingPage)(new ConsultPublicBookingPage($code, $this->clock->now()), $presenter);
        }

        return $this->json($presenter->resource)->setPublic()->setMaxAge(self::KEPT_SECONDS);
    }
}
