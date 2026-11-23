<?php

declare(strict_types=1);

namespace App\Booking\Controller;

use App\Booking\BookingCodes;
use App\Booking\MyBookingPageReader;
use App\Booking\Resource\BookingPageResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Booking\Application\ChangeBookingLink\ChangeBookingLink;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Booking')]
#[IsGranted('ROLE_USER')]
final readonly class ChangeBookingLinkController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private BookingCodes $codes,
        private MyBookingPageReader $myBookingPageReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/me/booking-page/link', name: 'api_booking_page_change_link', methods: ['POST'])]
    #[OA\Post(summary: 'Draw a new code for my booking page\'s link. The former link leads nowhere')]
    #[OA\Response(response: 200, description: 'My booking page', content: new Model(type: BookingPageResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 409, description: 'The page has no link yet', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new ChangeBookingLink(memberId: $user->memberId(), drawnCode: $this->codes->draw(), executedAt: $this->clock->now()));

        return $this->jsonResponses->of($this->myBookingPageReader->read($user->memberId()));
    }
}
