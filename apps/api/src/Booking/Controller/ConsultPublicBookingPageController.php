<?php

namespace App\Booking\Controller;

use App\Booking\PublicBookingPageResourcePresenter;
use App\Booking\Resource\PublicBookingPageResource;
use App\Shared\ImageResourceFactory;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Booking\Application\ConsultPublicBookingPage\ConsultPublicBookingPage;
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
    public function __construct(
        private readonly QueryHandler $consultPublicBookingPage,
        private readonly ClockInterface $clock,
        private readonly ImageResourceFactory $imageResourceFactory,
    ) {
    }

    #[Route('/api/booking-pages/{code}', name: 'api_public_booking_page', requirements: ['code' => '[0-9A-Za-z]{22}'], methods: ['GET'])]
    #[OA\Get(summary: 'Consult the booking page a public link leads to, without signing in: its free slots from tomorrow for 14 days and how its host meets the bookers; a link closed, changed, unknown or whose host has no connected calendar shows nothing, without saying why', security: [])]
    #[OA\Response(response: 200, description: 'The booking page', content: new Model(type: PublicBookingPageResource::class))]
    public function __invoke(string $code): JsonResponse
    {
        $presenter = new PublicBookingPageResourcePresenter($this->imageResourceFactory);
        ($this->consultPublicBookingPage)(new ConsultPublicBookingPage($code, $this->clock->now()), $presenter);

        return $this->json($presenter->resource);
    }
}
