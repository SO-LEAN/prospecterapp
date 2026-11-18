<?php

namespace App\Booking\Controller;

use App\Booking\Input\ChannelsInput;
use App\Booking\MyBookingPageReader;
use App\Booking\Resource\BookingPageResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Booking\Application\OfferChannels\OfferChannels;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Booking')]
#[IsGranted('ROLE_USER')]
final class OfferChannelsController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly MyBookingPageReader $myBookingPageReader,
    ) {
    }

    #[Route('/api/me/booking-page/channels', name: 'api_booking_page_offer_channels', methods: ['PATCH'])]
    #[OA\Patch(summary: 'Choose how the bookers meet me, a call, a video call or both; an open page offers one at least')]
    #[OA\Response(response: 200, description: 'My booking page', content: new Model(type: BookingPageResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 422, description: 'No channel while the page is open, an unknown channel', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] ChannelsInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new OfferChannels(
            memberId: $user->memberId(),
            channels: $input->channels ?? throw new \LogicException('Channels validated are given'),
            executedAt: $this->clock->now(),
        ));

        return $this->json($this->myBookingPageReader->read($user->memberId()));
    }
}
