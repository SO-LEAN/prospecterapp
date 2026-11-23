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
use Prospecter\Core\Booking\Application\BookingPage\RemoveInactivePeriod\RemoveInactivePeriod;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Booking')]
#[IsGranted('ROLE_USER')]
final readonly class RemoveInactivePeriodController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private MyBookingPageReader $myBookingPageReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/me/booking-page/inactive-periods/{id}', name: 'api_booking_page_remove_inactive_period', requirements: ['id' => Requirement::UUID], methods: ['DELETE'])]
    #[OA\Delete(summary: 'Remove one of my inactive periods. Its days are bookable again, as my windows say')]
    #[OA\Response(response: 200, description: 'My booking page', content: new Model(type: BookingPageResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 404, description: 'I have no inactive period with this id', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new RemoveInactivePeriod(memberId: $user->memberId(), periodId: $id, executedAt: $this->clock->now()));

        return $this->jsonResponses->of($this->myBookingPageReader->read($user->memberId()));
    }
}
