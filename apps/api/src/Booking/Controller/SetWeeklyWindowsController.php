<?php

declare(strict_types=1);

namespace App\Booking\Controller;

use App\Booking\Input\WindowsInput;
use App\Booking\MyBookingPageReader;
use App\Booking\Resource\BookingPageResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Booking\Application\BookingPage\SetWeeklyWindows\SetWeeklyWindows;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Booking')]
#[IsGranted('ROLE_USER')]
final readonly class SetWeeklyWindowsController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private MyBookingPageReader $myBookingPageReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/me/booking-page/windows', name: 'api_booking_page_set_windows', methods: ['PATCH'])]
    #[OA\Patch(summary: 'Set my weekly windows in my clock\'s time zone. They replace the former ones. A day holds one window at most. A window starts and ends on a quarter hour and lasts at least 15 minutes')]
    #[OA\Response(response: 200, description: 'My booking page', content: new Model(type: BookingPageResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 422, description: 'A window too short or off the quarter hour, two windows on one day, or an unknown day or time zone', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] WindowsInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new SetWeeklyWindows(
            memberId: $user->memberId(),
            windows: $input->windows ?? throw new \LogicException('Validated windows are given'),
            timeZone: $input->timeZone ?? throw new \LogicException('Validated windows have their time zone'),
            executedAt: $this->clock->now(),
        ));

        return $this->jsonResponses->of($this->myBookingPageReader->read($user->memberId(), $input->timeZone));
    }
}
