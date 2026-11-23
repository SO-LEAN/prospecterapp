<?php

declare(strict_types=1);

namespace App\Booking\Controller;

use App\Booking\MyBookingPageReader;
use App\Booking\Resource\BookingPageResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Booking\Application\BookingPage\CloseBooking\CloseBooking;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Booking')]
#[IsGranted('ROLE_USER')]
final readonly class CloseBookingController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private MyBookingPageReader $myBookingPageReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/me/booking-page/opening', name: 'api_booking_page_close', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Close my booking page at once. Its link offers nothing. The page keeps my settings and my link')]
    #[OA\Response(response: 200, description: 'My booking page', content: new Model(type: BookingPageResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 409, description: 'The page is already closed', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new CloseBooking(memberId: $user->memberId(), executedAt: $this->clock->now()));

        return $this->jsonResponses->of($this->myBookingPageReader->read($user->memberId()));
    }
}
