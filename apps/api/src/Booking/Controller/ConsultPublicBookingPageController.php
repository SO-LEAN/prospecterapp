<?php

namespace App\Booking\Controller;

use App\Booking\PublicBookingPageResourcePresenter;
use App\Booking\Resource\PublicBookingPageResource;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Booking\Application\ConsultPublicBookingPage\ConsultPublicBookingPage;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Read without signing in, by whomever holds the link (ADR 0151): its firewall lets every call through.
 */
#[OA\Tag('Booking')]
final class ConsultPublicBookingPageController extends AbstractController
{
    public function __construct(
        private readonly QueryHandler $consultPublicBookingPage,
    ) {
    }

    #[Route('/api/booking-pages/{code}', name: 'api_public_booking_page', requirements: ['code' => '[0-9A-Za-z]{22}'], methods: ['GET'])]
    #[OA\Get(summary: 'Consult the booking page a public link leads to, without signing in: whether it offers slots, and how its host meets the bookers', security: [])]
    #[OA\Response(response: 200, description: 'The booking page', content: new Model(type: PublicBookingPageResource::class))]
    #[OA\Response(response: 404, description: 'No page at this code: a former link, or one nobody drew', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $code): JsonResponse
    {
        $presenter = new PublicBookingPageResourcePresenter();
        ($this->consultPublicBookingPage)(new ConsultPublicBookingPage($code), $presenter);

        return $this->json($presenter->resource);
    }
}
