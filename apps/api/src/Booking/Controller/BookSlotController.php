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
 * Booked without signing in, by whomever holds the link (ADR 0153, 0164): its firewall lets every call through, the
 * code of the link limits them.
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
    #[OA\Post(summary: 'Book a free slot of the page a public link leads to, without signing in: it becomes an exchange its host planned with the booker, a contact of the account or one to attach; a form a robot filled books nothing, without saying so', security: [])]
    #[OA\Response(response: 201, description: 'What was booked', content: new Model(type: BookingResource::class))]
    #[OA\Response(response: 422, description: 'A link offering nothing (`bookingUnavailable`), a channel not offered (`channelNotOffered`), a slot taken meanwhile or never offered (`slotNotFree`), another appointment to come with this e-mail (`alreadyBooked`, its moment in the `appointment` of the context of its violation), a booker leaving out anything but the subject (`incompleteBooker`), a phone or an e-mail invalid', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 429, description: 'This link was used too often in the last minute: `tooManyRequests`, try again after `Retry-After` seconds', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $code, #[MapRequestPayload] BookingInput $input): JsonResponse
    {
        $now = $this->clock->now();
        if (!Robots::filled($input, $now)) {
            $this->commandBus->dispatch(new BookSlot(
                code: $code,
                appointmentId: $this->identities->next(),
                bookerId: $this->identities->next(),
                channel: $input->channel ?? throw new \LogicException('A booking validated has its channel'),
                slot: new \DateTimeImmutable($input->slot ?? throw new \LogicException('A booking validated has its slot')),
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
