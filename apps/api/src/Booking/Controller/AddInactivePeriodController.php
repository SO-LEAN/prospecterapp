<?php

declare(strict_types=1);

namespace App\Booking\Controller;

use App\Booking\Input\InactivePeriodInput;
use App\Booking\MyBookingPageReader;
use App\Booking\Resource\BookingPageResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Booking\Application\BookingPage\AddInactivePeriod\AddInactivePeriod;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Booking')]
#[IsGranted('ROLE_USER')]
final readonly class AddInactivePeriodController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private Identities $identities,
        private MyBookingPageReader $myBookingPageReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/me/booking-page/inactive-periods', name: 'api_booking_page_add_inactive_period', methods: ['POST'])]
    #[OA\Post(summary: 'Add days when nobody can book me, whatever my windows say. Both bounds are included')]
    #[OA\Response(response: 201, description: 'My booking page', content: new Model(type: BookingPageResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 422, description: 'A period that ends before it starts, or a day not written YYYY-MM-DD', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] InactivePeriodInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new AddInactivePeriod(
            memberId: $user->memberId(),
            periodId: $this->identities->next(),
            firstDay: $input->firstDay ?? throw new \LogicException('A validated period has its first day'),
            lastDay: $input->lastDay ?? throw new \LogicException('A validated period has its last day'),
            executedAt: $this->clock->now(),
        ));

        return $this->jsonResponses->of($this->myBookingPageReader->read($user->memberId()), Response::HTTP_CREATED);
    }
}
