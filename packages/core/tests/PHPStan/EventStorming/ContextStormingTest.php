<?php

declare(strict_types=1);

namespace Tests\Core\PHPStan\EventStorming;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Booking\Application\Appointment\ForgetAppointmentOfErasedExchange\ForgetAppointmentOfErasedExchange;
use Prospecter\Core\Booking\Application\Appointment\PublishSlotBooked\PublishSlotBookedHandler;
use Prospecter\Core\Booking\Application\BookingPage\ConsultMyBookingPage\ConsultMyBookingPage;
use Prospecter\Core\Booking\Application\BookingPage\MyBookingPage;
use Prospecter\Core\Booking\Application\BookingPage\OpenBooking\OpenBooking;
use Prospecter\Core\Booking\Application\Policy\ForgetAppointmentOfErasedExchangePolicy;
use Prospecter\Core\Booking\Application\PublicLink\BookSlot\BookSlot;
use Prospecter\Core\Booking\Contract\Event as Published;
use Prospecter\Core\Booking\Domain\Event\BookingOpened;
use Prospecter\Core\Booking\Domain\Event\SlotBooked;
use Prospecter\Core\Booking\Domain\Model\Appointment;
use Prospecter\Core\Booking\Domain\Model\BookingPage;
use Prospecter\Core\Booking\Domain\Port\ConnectedCalendars;
use Prospecter\Core\Booking\Domain\Port\HostAgendas;
use Prospecter\Core\Booking\Domain\Service\BusyPeriods;
use Prospecter\Core\Connectors\Contract\Service\WhichHaveAConnectedCalendar\WhichHaveAConnectedCalendarService;
use Prospecter\Core\PersonalData\Application\Policy\IdentifyBookerPolicy;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\PHPStan\EventStorming\Call;
use Prospecter\PHPStan\EventStorming\ClassFacts;
use Prospecter\PHPStan\EventStorming\ContextStorming;
use Prospecter\PHPStan\EventStorming\Facts;
use Prospecter\PHPStan\EventStorming\Link;
use Prospecter\PHPStan\EventStorming\Model;
use Prospecter\PHPStan\EventStorming\Sticky;

final class ContextStormingTest extends TestCase
{
    private const string COMMAND_HANDLER = CommandHandler::class;

    private const string OPEN_BOOKING = OpenBooking::class;

    private const string OPEN_BOOKING_CONTROLLER = 'App\Booking\Controller\OpenBookingController';

    private const string BOOK_SLOT_CONTROLLER = 'App\Booking\Controller\BookSlotController';

    private const string MY_BOOKING_PAGE_READER = 'App\Booking\MyBookingPageReader';

    private const string OPEN_BOOKING_TOOL = 'App\Booking\Mcp\OpenBookingTool';

    private const string IDENTIFY_BOOKER = IdentifyBookerPolicy::class;

    private const string BOOKING_PAGE = BookingPage::class;

    private const string BOOKING_OPENED = BookingOpened::class;

    private const string BOOK_SLOT = BookSlot::class;

    private const string APPOINTMENT = Appointment::class;

    private const string SLOT_BOOKED = SlotBooked::class;

    private const string EVENT_HANDLER = EventHandler::class;

    private const string PUBLISH_SLOT_BOOKED = PublishSlotBookedHandler::class;

    private const string PUBLISHED_SLOT_BOOKED = Published\SlotBooked::class;

    private const string CONNECTED_CALENDARS = ConnectedCalendars::class;

    private const string CALENDARS_ADAPTER = 'App\Booking\AntiCorruption\ConnectorsConnectedCalendars';

    private const string CONNECTED_CALENDAR_SERVICE = WhichHaveAConnectedCalendarService::class;

    private const string BUSY_PERIODS = BusyPeriods::class;

    private const string HOST_AGENDAS = HostAgendas::class;

    private const string QUERY_HANDLER = QueryHandler::class;

    private const string CONSULT_MY_BOOKING_PAGE = ConsultMyBookingPage::class;

    private const string MY_BOOKING_PAGE = MyBookingPage::class;

    private const string POLICY = Policy::class;

    private const string FORGET_POLICY = ForgetAppointmentOfErasedExchangePolicy::class;

    private const string FORGET_APPOINTMENT = ForgetAppointmentOfErasedExchange::class;

    private const string EXCHANGE_FACTS_CHANGED = ExchangeFactsChanged::class;

    public function testACommandIsTheMessageOfItsHandlerInItsModule(): void
    {
        $model = self::modelOf(new Facts([self::handlerOf(self::OPEN_BOOKING)], []));

        self::assertSame(
            ['id' => 'command:'.self::OPEN_BOOKING, 'type' => 'command', 'label' => 'OpenBooking', 'name' => 'OpenBooking', 'class' => self::OPEN_BOOKING, 'context' => 'Booking', 'module' => 'BookingPage', 'detail' => null],
            self::stickyOf($model, 'command:'.self::OPEN_BOOKING),
        );
    }

    public function testACommandIsLabelledByTheTitleOfTheFeatureOfItsUseCase(): void
    {
        $model = self::modelOf(new Facts([self::handlerOf(self::OPEN_BOOKING)], []), ['OpenBooking' => 'Activer et couper ma prise de rendez-vous']);

        self::assertSame('Activer et couper ma prise de rendez-vous', self::stickyOf($model, 'command:'.self::OPEN_BOOKING)['label'] ?? null);
    }

    public function testACommandLeadsToTheEventsTheIntentionOfItsAggregateRecords(): void
    {
        $model = self::modelOf(new Facts([self::handlerOf(self::OPEN_BOOKING)], [
            new Call(self::OPEN_BOOKING.'Handler', '__invoke', self::BOOKING_PAGE, 'open'),
            new Call(self::BOOKING_PAGE, 'open', self::BOOKING_OPENED, Call::CREATION),
        ]));

        self::assertSame([
            ['from' => 'aggregate:'.self::BOOKING_PAGE, 'to' => 'event:'.self::BOOKING_OPENED, 'via' => 'open', 'certain' => true],
            ['from' => 'command:'.self::OPEN_BOOKING, 'to' => 'aggregate:'.self::BOOKING_PAGE, 'via' => 'open', 'certain' => true],
        ], self::linksOf($model));
    }

    public function testAMethodOfAnAggregateThatRecordsNoEventIsNoIntention(): void
    {
        $model = self::modelOf(new Facts([self::handlerOf(self::OPEN_BOOKING)], [
            new Call(self::OPEN_BOOKING.'Handler', '__invoke', self::BOOKING_PAGE, 'host'),
        ]));

        self::assertSame([], self::linksOf($model));
    }

    public function testAnEventRecordedUnderAConditionIsAnUncertainLink(): void
    {
        $model = self::modelOf(new Facts([self::handlerOf(self::OPEN_BOOKING)], [
            new Call(self::OPEN_BOOKING.'Handler', '__invoke', self::BOOKING_PAGE, 'open'),
            new Call(self::BOOKING_PAGE, 'open', self::BOOKING_OPENED, Call::CREATION, conditional: true),
        ]));

        self::assertSame(
            ['from' => 'aggregate:'.self::BOOKING_PAGE, 'to' => 'event:'.self::BOOKING_OPENED, 'via' => 'open', 'certain' => false],
            self::linksOf($model)[0] ?? null,
        );
    }

    public function testTheEventsAnIntentionRecordsThroughAnotherAggregateAreItsOwn(): void
    {
        $model = self::modelOf(new Facts([self::handlerOf(self::BOOK_SLOT)], [
            new Call(self::BOOK_SLOT.'Handler', '__invoke', self::BOOK_SLOT.'Handler', 'bookablePage'),
            new Call(self::BOOK_SLOT.'Handler', 'bookablePage', self::BOOKING_PAGE, 'book'),
            new Call(self::BOOKING_PAGE, 'book', self::APPOINTMENT, 'booked'),
            new Call(self::APPOINTMENT, 'booked', self::SLOT_BOOKED, Call::CREATION),
        ]));

        self::assertSame([
            ['from' => 'aggregate:'.self::BOOKING_PAGE, 'to' => 'event:'.self::SLOT_BOOKED, 'via' => 'book', 'certain' => true],
            ['from' => 'command:'.self::BOOK_SLOT, 'to' => 'aggregate:'.self::BOOKING_PAGE, 'via' => 'book', 'certain' => true],
        ], self::linksOf($model));
    }

    public function testAPolicyTurnsTheEventItListensToIntoItsCommand(): void
    {
        $model = self::modelOf(new Facts([new ClassFacts(self::FORGET_POLICY, interfaces: [self::POLICY], parameters: [[self::EXCHANGE_FACTS_CHANGED]], returns: [self::FORGET_APPOINTMENT])], []));

        self::assertSame([
            ['from' => 'event:'.self::EXCHANGE_FACTS_CHANGED, 'to' => 'policy:'.self::FORGET_POLICY, 'via' => null, 'certain' => true],
            ['from' => 'policy:'.self::FORGET_POLICY, 'to' => 'command:'.self::FORGET_APPOINTMENT, 'via' => null, 'certain' => true],
        ], self::linksOf($model));
    }

    public function testAPolicyThatMaySendNothingSendsItsCommandUncertainly(): void
    {
        $model = self::modelOf(new Facts([new ClassFacts(self::FORGET_POLICY, interfaces: [self::POLICY], parameters: [[self::EXCHANGE_FACTS_CHANGED]], returns: [self::FORGET_APPOINTMENT], mayReturnNothing: true)], []));

        self::assertSame(
            ['from' => 'policy:'.self::FORGET_POLICY, 'to' => 'command:'.self::FORGET_APPOINTMENT, 'via' => null, 'certain' => false],
            self::linksOf($model)[1] ?? null,
        );
    }

    public function testAReactionToAnEventPublishesWhatItCreates(): void
    {
        $model = self::modelOf(new Facts([new ClassFacts(self::PUBLISH_SLOT_BOOKED, interfaces: [self::EVENT_HANDLER], parameters: [[self::SLOT_BOOKED]])], [
            new Call(self::PUBLISH_SLOT_BOOKED, '__invoke', self::PUBLISHED_SLOT_BOOKED, Call::CREATION),
        ]));

        self::assertSame([
            ['from' => 'event:'.self::SLOT_BOOKED, 'to' => 'policy:'.self::PUBLISH_SLOT_BOOKED, 'via' => null, 'certain' => true],
            ['from' => 'policy:'.self::PUBLISH_SLOT_BOOKED, 'to' => 'event:'.self::PUBLISHED_SLOT_BOOKED, 'via' => null, 'certain' => true],
        ], self::linksOf($model));
    }

    public function testAQueryIsAReadModelNamingWhatItPresents(): void
    {
        $model = self::modelOf(new Facts([new ClassFacts(self::CONSULT_MY_BOOKING_PAGE.'Handler', interfaces: [self::QUERY_HANDLER], parameters: [[self::CONSULT_MY_BOOKING_PAGE]], presents: [self::MY_BOOKING_PAGE])], []), ['ConsultMyBookingPage' => 'Consulter ma prise de rendez-vous']);

        self::assertSame(
            ['id' => 'readModel:'.self::CONSULT_MY_BOOKING_PAGE, 'type' => 'readModel', 'label' => 'Consulter ma prise de rendez-vous', 'name' => 'ConsultMyBookingPage', 'class' => self::CONSULT_MY_BOOKING_PAGE, 'context' => 'Booking', 'module' => 'BookingPage', 'detail' => 'MyBookingPage'],
            self::stickyOf($model, 'readModel:'.self::CONSULT_MY_BOOKING_PAGE),
        );
    }

    public function testAPortOfTheDomainIsAnExternalSystemNamedAfterWhatItsAdapterReads(): void
    {
        $model = self::modelOf(new Facts([
            self::handlerOf(self::OPEN_BOOKING, [self::CONNECTED_CALENDARS]),
            new ClassFacts(self::CALENDARS_ADAPTER, interfaces: [self::CONNECTED_CALENDARS], dependencies: [self::CONNECTED_CALENDAR_SERVICE]),
        ], []));

        self::assertSame(
            [
                ['id' => 'externalSystem:'.self::CONNECTED_CALENDARS, 'type' => 'externalSystem', 'label' => 'Connectors', 'name' => 'ConnectedCalendars', 'class' => self::CONNECTED_CALENDARS, 'context' => 'Booking', 'module' => null, 'detail' => 'ConnectorsConnectedCalendars'],
                ['from' => 'command:'.self::OPEN_BOOKING, 'to' => 'externalSystem:'.self::CONNECTED_CALENDARS, 'via' => null, 'certain' => true],
            ],
            [self::stickyOf($model, 'externalSystem:'.self::CONNECTED_CALENDARS), self::linksOf($model)[0] ?? null],
        );
    }

    public function testAReadModelAsksTheExternalSystemsOfTheServicesItsHandlerTakes(): void
    {
        $model = self::modelOf(new Facts([
            new ClassFacts(self::CONSULT_MY_BOOKING_PAGE.'Handler', interfaces: [self::QUERY_HANDLER], parameters: [[self::CONSULT_MY_BOOKING_PAGE]], dependencies: [self::BUSY_PERIODS]),
            new ClassFacts(self::BUSY_PERIODS, dependencies: [self::HOST_AGENDAS]),
        ], []));

        self::assertSame(
            [['from' => 'readModel:'.self::CONSULT_MY_BOOKING_PAGE, 'to' => 'externalSystem:'.self::HOST_AGENDAS, 'via' => null, 'certain' => true]],
            self::linksOf($model),
        );
    }

    public function testTheRoleAControllerRequiresIsTheActorOfTheCommandItSends(): void
    {
        $model = self::modelOf(new Facts([
            self::handlerOf(self::OPEN_BOOKING),
            new ClassFacts(self::OPEN_BOOKING_CONTROLLER, roles: ['ROLE_USER']),
        ], [new Call(self::OPEN_BOOKING_CONTROLLER, '__invoke', self::OPEN_BOOKING, Call::CREATION)]));

        self::assertSame(
            [
                ['id' => 'actor:ROLE_USER', 'type' => 'actor', 'label' => 'Membre', 'name' => 'ROLE_USER', 'class' => null, 'context' => 'Booking', 'module' => null, 'detail' => null],
                ['from' => 'actor:ROLE_USER', 'to' => 'command:'.self::OPEN_BOOKING, 'via' => null, 'certain' => true],
            ],
            [self::stickyOf($model, 'actor:ROLE_USER'), self::linksOf($model)[0] ?? null],
        );
    }

    public function testAControllerRequiringNoRoleIsOpenToAnyoneHoldingTheLink(): void
    {
        $model = self::modelOf(new Facts([
            self::handlerOf(self::BOOK_SLOT),
            new ClassFacts(self::BOOK_SLOT_CONTROLLER),
        ], [new Call(self::BOOK_SLOT_CONTROLLER, '__invoke', self::BOOK_SLOT, Call::CREATION, conditional: true)]));

        self::assertSame(
            [
                ['id' => 'actor:public', 'type' => 'actor', 'label' => 'Quiconque a le lien', 'name' => 'public', 'class' => null, 'context' => 'Booking', 'module' => null, 'detail' => null],
                ['from' => 'actor:public', 'to' => 'command:'.self::BOOK_SLOT, 'via' => null, 'certain' => true],
            ],
            [self::stickyOf($model, 'actor:public'), self::linksOf($model)[0] ?? null],
        );
    }

    public function testAControllerSendsTheUseCasesOfTheDeliveryClassesItCalls(): void
    {
        $model = self::modelOf(new Facts([
            new ClassFacts(self::CONSULT_MY_BOOKING_PAGE.'Handler', interfaces: [self::QUERY_HANDLER], parameters: [[self::CONSULT_MY_BOOKING_PAGE]]),
            new ClassFacts(self::OPEN_BOOKING_CONTROLLER, roles: ['ROLE_USER']),
        ], [
            new Call(self::OPEN_BOOKING_CONTROLLER, '__invoke', self::MY_BOOKING_PAGE_READER, 'read'),
            new Call(self::MY_BOOKING_PAGE_READER, 'read', self::CONSULT_MY_BOOKING_PAGE, Call::CREATION),
        ]));

        self::assertSame([['from' => 'actor:ROLE_USER', 'to' => 'readModel:'.self::CONSULT_MY_BOOKING_PAGE, 'via' => null, 'certain' => true]], self::linksOf($model));
    }

    public function testAToolOfTheMcpServerIsTheMembersAi(): void
    {
        $model = self::modelOf(new Facts([
            self::handlerOf(self::OPEN_BOOKING),
            new ClassFacts(self::OPEN_BOOKING_TOOL),
        ], [new Call(self::OPEN_BOOKING_TOOL, '__invoke', self::OPEN_BOOKING, Call::CREATION)]));

        self::assertSame(
            [
                ['id' => 'actor:mcp', 'type' => 'actor', 'label' => 'L\'IA du membre', 'name' => 'mcp', 'class' => null, 'context' => 'Booking', 'module' => null, 'detail' => null],
                ['from' => 'actor:mcp', 'to' => 'command:'.self::OPEN_BOOKING, 'via' => null, 'certain' => true],
            ],
            [self::stickyOf($model, 'actor:mcp'), self::linksOf($model)[0] ?? null],
        );
    }

    public function testTheOtherContextsReactToAPublishedEventByTheirSubscriptions(): void
    {
        $facts = new Facts([new ClassFacts(self::PUBLISH_SLOT_BOOKED, interfaces: [self::EVENT_HANDLER], parameters: [[self::SLOT_BOOKED]])], [
            new Call(self::PUBLISH_SLOT_BOOKED, '__invoke', self::PUBLISHED_SLOT_BOOKED, Call::CREATION),
        ]);
        $subscriptions = [
            'personal-data-identify-booker' => ['sources' => ['prospecter.booking'], 'events' => ['SlotBooked.v1'], 'policy' => self::IDENTIFY_BOOKER],
            'booking-internal' => ['sources' => ['prospecter.booking.internal'], 'events' => ['SlotBooked.v1'], 'handler' => self::PUBLISH_SLOT_BOOKED],
        ];

        $model = self::modelOf($facts, subscriptions: $subscriptions);

        self::assertSame(
            [
                ['id' => 'policy:'.self::IDENTIFY_BOOKER, 'type' => 'policy', 'label' => 'IdentifyBookerPolicy', 'name' => 'IdentifyBookerPolicy', 'class' => self::IDENTIFY_BOOKER, 'context' => 'PersonalData', 'module' => null, 'detail' => null],
                ['from' => 'event:'.self::PUBLISHED_SLOT_BOOKED, 'to' => 'policy:'.self::IDENTIFY_BOOKER, 'via' => null, 'certain' => true],
            ],
            [self::stickyOf($model, 'policy:'.self::IDENTIFY_BOOKER), self::linksOf($model)[0] ?? null],
        );
    }

    public function testTheModulesAreThoseOfTheUseCasesOfTheContext(): void
    {
        $model = self::modelOf(new Facts([self::handlerOf(self::BOOK_SLOT), self::handlerOf(self::OPEN_BOOKING), self::handlerOf(self::CONSULT_MY_BOOKING_PAGE)], []));

        self::assertSame(['BookingPage', 'PublicLink'], $model->modules);
    }

    /**
     * @param list<string> $dependencies
     */
    private static function handlerOf(string $command, array $dependencies = []): ClassFacts
    {
        return new ClassFacts($command.'Handler', interfaces: [self::COMMAND_HANDLER], parameters: [[$command]], dependencies: $dependencies);
    }

    /**
     * @param array<string, string>               $titles
     * @param array<string, array<string, mixed>> $subscriptions
     */
    private static function modelOf(Facts $facts, array $titles = [], array $subscriptions = []): Model
    {
        return new ContextStorming($facts, $titles, $subscriptions)->model('Booking');
    }

    /**
     * @return array<string, string|null>|null
     */
    private static function stickyOf(Model $model, string $id): ?array
    {
        return array_find($model->stickies, static fn (Sticky $sticky): bool => $id === $sticky->id)?->jsonSerialize();
    }

    /**
     * @return list<array{from: string, to: string, via: string|null, certain: bool}>
     */
    private static function linksOf(Model $model): array
    {
        return array_map(static fn (Link $link): array => $link->jsonSerialize(), $model->links);
    }
}
