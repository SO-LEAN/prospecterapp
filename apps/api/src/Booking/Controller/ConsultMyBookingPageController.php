<?php

declare(strict_types=1);

namespace App\Booking\Controller;

use App\Booking\MyBookingPageReader;
use App\Booking\Resource\BookingPageResource;
use App\Security\User;
use App\Shared\Input\MyClockQuery;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Booking')]
#[IsGranted('ROLE_USER')]
final readonly class ConsultMyBookingPageController
{
    public function __construct(
        private MyBookingPageReader $myBookingPageReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/me/booking-page', name: 'api_booking_page', methods: ['GET'])]
    #[OA\Get(summary: 'Get my booking page: my windows, my inactive periods that have not ended on my clock, my channels, whether it is open, its link, and whether my calendar is connected')]
    #[OA\Response(response: 200, description: 'My booking page, closed and empty until I set it', content: new Model(type: BookingPageResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 422, description: 'An unknown time zone', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user, #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY, mapWhenEmpty: true)] MyClockQuery $clock): JsonResponse
    {
        return $this->jsonResponses->of($this->myBookingPageReader->read($user->memberId(), $clock->timeZone));
    }
}
