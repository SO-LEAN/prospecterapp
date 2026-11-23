<?php

declare(strict_types=1);

namespace App\Booking\Controller;

use App\Booking\Input\BookingInput;
use App\Booking\PublicBookingRoutes;
use App\Booking\Resource\BookingResource;
use App\Booking\Robots;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Booking\Application\BookSlot\BookSlot;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Anyone with the link can book without signing in (ADR 0153, 0164). The firewall lets every call through. The link's
 * code limits the calls.
 */
#[OA\Tag('Booking')]
final class BookSlotController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly Identities $identities,
    ) {
    }

    #[Route('/api/booking-pages/{code}/appointments', name: 'api_public_booking_book', defaults: [PublicBookingRoutes::LIMITED_BY_ITS_CODE => true], methods: ['POST'])]
    #[OA\Post(summary: 'Book a free slot on the page that a public link leads to, without signing in. The slot becomes an exchange that the host planned with the booker. The booker is a contact of the account, or a contact to attach. A form that a robot filled books nothing, and the answer does not say so', security: [])]
    #[OA\Response(response: 201, description: 'What the booker booked', content: new Model(type: BookingResource::class))]
    #[OA\Response(response: 422, description: 'A link that offers nothing (`bookingUnavailable`), a channel that is not offered (`channelNotOffered`), a slot taken in the meantime or never offered (`slotNotFree`), another upcoming appointment with this e-mail (`alreadyBooked`, with its time in the `appointment` field of its violation\'s context), a booker who left out anything but the subject (`incompleteBooker`), or an invalid phone or e-mail', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 429, description: 'This link was used too often in the last minute: `tooManyRequests`, try again after `Retry-After` seconds', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $code, #[MapRequestPayload] BookingInput $input): JsonResponse
    {
        $now = $this->clock->now();
        if (!Robots::filled($input, $now)) {
            $this->commandBus->dispatch(new BookSlot(
                code: $code,
                appointmentId: $this->identities->next(),
                bookerId: $this->identities->next(),
                channel: $input->channel ?? throw new \LogicException('A validated booking has its channel'),
                slot: new \DateTimeImmutable($input->slot ?? throw new \LogicException('A validated booking has its slot')),
                firstName: $input->firstName,
                lastName: $input->lastName,
                phone: $input->phone,
                email: $input->email,
                subject: $input->subject,
                executedAt: $now,
            ));
        }

        return $this->json(new BookingResource((string) $input->slot, (string) $input->channel, $input->email), Response::HTTP_CREATED);
    }
}
