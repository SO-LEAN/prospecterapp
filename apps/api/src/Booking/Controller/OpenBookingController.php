<?php

declare(strict_types=1);

namespace App\Booking\Controller;

use App\Booking\BookingCodes;
use App\Booking\MyBookingPageReader;
use App\Booking\Resource\BookingPageResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Booking\Application\OpenBooking\OpenBooking;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Booking')]
#[IsGranted('ROLE_USER')]
final class OpenBookingController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly BookingCodes $codes,
        private readonly MyBookingPageReader $myBookingPageReader,
    ) {
    }

    #[Route('/api/me/booking-page/opening', name: 'api_booking_page_open', methods: ['POST'])]
    #[OA\Post(summary: 'Open my booking page. My calendar must be connected and the page must offer a channel. The first opening draws the page\'s link, which then offers my slots')]
    #[OA\Response(response: 200, description: 'My booking page', content: new Model(type: BookingPageResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 409, description: 'The page is already open', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'No connected calendar, or no offered channel', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new OpenBooking(memberId: $user->memberId(), drawnCode: $this->codes->draw(), executedAt: $this->clock->now()));

        return $this->json($this->myBookingPageReader->read($user->memberId()));
    }
}
