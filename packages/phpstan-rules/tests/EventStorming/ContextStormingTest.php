<?php

declare(strict_types=1);

namespace Tests\PHPStan\EventStorming;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Booking\Application\Appointment\ConsultAppointments\ConsultAppointmentsHandler;
use Prospecter\Core\Booking\Application\Appointment\ForgetAppointmentOfErasedExchange\ForgetAppointmentOfErasedExchange;
use Prospecter\Core\Booking\Application\Appointment\PublishSlotBooked\PublishSlotBookedHandler;
use Prospecter\Core\Booking\Application\BookingPage\ConsultMyBookingPage\ConsultMyBookingPage;
use Prospecter\Core\Booking\Application\BookingPage\ConsultMyBookingPage\ConsultMyBookingPageOutput;
use Prospecter\Core\Booking\Application\BookingPage\MyBookingPage;
use Prospecter\Core\Booking\Application\BookingPage\OpenBooking\OpenBooking;
use Prospecter\Core\Booking\Application\BookingPage\SetWeeklyWindows\SetWeeklyWindows;
use Prospecter\Core\Booking\Application\Policy\ForgetAppointmentOfErasedExchangePolicy;
use Prospecter\Core\Booking\Application\PublicLink\BookSlot\BookSlot;
use Prospecter\Core\Booking\Contract\Event as Published;
use Prospecter\Core\Booking\Contract\Service\ConsultAppointments\ConsultAppointments;
use Prospecter\Core\Booking\Contract\Service\ConsultAppointments\ConsultAppointmentsService;
use Prospecter\Core\Booking\Domain\Event\BookingOpened;
use Prospecter\Core\Booking\Domain\Event\SlotBooked;
use Prospecter\Core\Booking\Domain\Model\Appointment;
use Prospecter\Core\Booking\Domain\Model\BookingPage;
use Prospecter\Core\Booking\Domain\Port\ConnectedCalendars;
use Prospecter\Core\Booking\Domain\Port\HostAgendas;
use Prospecter\Core\Booking\Domain\Service\BusyPeriods;
use Prospecter\Core\Campaigns\Application\Campaign\ConsultCampaign\ConsultCampaign;
use Prospecter\Core\Campaigns\Application\Campaign\ConsultCampaign\ConsultCampaignHandler;
use Prospecter\Core\Campaigns\Application\Tagging\ConsultContactTags\ConsultContactTags;
use Prospecter\Core\Campaigns\Application\Tagging\ConsultContactTags\ConsultContactTagsHandler;
use Prospecter\Core\Campaigns\Application\Tagging\FactRecorder as CampaignsFactRecorder;
use Prospecter\Core\Campaigns\Application\Tagging\ForgetTagsOfForgottenContact\ForgetTagsOfForgottenContact;
use Prospecter\Core\Campaigns\Application\Tagging\ForgetTagsOfForgottenContact\ForgetTagsOfForgottenContactHandler;
use Prospecter\Core\Campaigns\Application\Tagging\RecordContactFacts\RecordContactFacts;
use Prospecter\Core\Campaigns\Application\Tagging\RecordContactFacts\RecordContactFactsHandler;
use Prospecter\Core\Campaigns\Domain\Repository\FactRepository as CampaignsFactRepository;
use Prospecter\Core\Campaigns\Domain\Repository\TaggingRepository;
use Prospecter\Core\Connectors\Application\Connection\WhichHaveAConnectedCalendar\WhichHaveAConnectedCalendarHandler;
use Prospecter\Core\Connectors\Contract\Service\WhichHaveAConnectedCalendar\WhichHaveAConnectedCalendar;
use Prospecter\Core\Connectors\Contract\Service\WhichHaveAConnectedCalendar\WhichHaveAConnectedCalendarService;
use Prospecter\Core\PersonalData\Application\Policy\IdentifyBookerPolicy;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\Prospecting\Domain\Port\BookedAppointments;
use Prospecter\Core\Reporting\Application\ConsultFigures\ConsultFigures;
use Prospecter\Core\Reporting\Application\ConsultFigures\ConsultFiguresHandler;
use Prospecter\Core\Reporting\Application\FactRecorder as ReportingFactRecorder;
use Prospecter\Core\Reporting\Application\RecordExchangeFacts\RecordExchangeFacts;
use Prospecter\Core\Reporting\Application\RecordExchangeFacts\RecordExchangeFactsHandler;
use Prospecter\Core\Reporting\Domain\Repository\FactRepository as ReportingFactRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\PHPStan\EventStorming\Argument;
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

    private const string OPEN_BOOKING_COMMAND = 'App\Booking\Command\OpenBookingCommand';

    private const string BOOKINGS_OPENER = 'App\Booking\BookingsOpener';

    private const string SCHEDULED_TASK = 'App\Shared\ScheduledTask';

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

    private const string CONNECTED_CALENDAR = WhichHaveAConnectedCalendar::class;

    private const string CONNECTED_CALENDAR_HANDLER = WhichHaveAConnectedCalendarHandler::class;

    private const string APPOINTMENTS_SERVICE = ConsultAppointmentsService::class;

    private const string BOOKED_APPOINTMENTS = BookedAppointments::class;

    private const string BOOKED_APPOINTMENTS_ADAPTER = 'App\Prospecting\AntiCorruption\BookingBookedAppointments';

    private const string BUSY_PERIODS = BusyPeriods::class;

    private const string HOST_AGENDAS = HostAgendas::class;

    private const string QUERY_HANDLER = QueryHandler::class;

    private const string CONSULT_MY_BOOKING_PAGE = ConsultMyBookingPage::class;

    private const string MY_BOOKING_PAGE = MyBookingPage::class;

    private const string POLICY = Policy::class;

    private const string FORGET_POLICY = ForgetAppointmentOfErasedExchangePolicy::class;

    private const string FORGET_APPOINTMENT = ForgetAppointmentOfErasedExchange::class;

    private const string EXCHANGE_FACTS_CHANGED = ExchangeFactsChanged::class;

    private const string SET_WEEKLY_WINDOWS = SetWeeklyWindows::class;

    private const string MY_BOOKING_PAGE_OUTPUT = ConsultMyBookingPageOutput::class;

    private const string MY_BOOKING_PAGE_PRESENTER = 'App\Booking\BookingPageResourcePresenter';

    private const string MY_BOOKING_PAGE_LINKS = 'App\Booking\Resource\BookingPageLinksResource';

    private const string SET_WINDOWS_CONTROLLER = 'App\Booking\Controller\SetWeeklyWindowsController';

    private const string SET_WINDOWS_ROUTE = 'api_booking_page_set_windows';

    private const string OPEN_ROUTE = 'api_booking_page_open';

    public function testACommandIsTheMessageOfItsHandlerInItsModule(): void
    {
        $model = self::modelOf(new Facts([self::handlerOf(self::OPEN_BOOKING)], []));

        self::assertSame(
            ['id' => 'command:'.self::OPEN_BOOKING, 'slug' => 'command:OpenBooking', 'type' => 'command', 'label' => 'Open booking', 'feature' => null, 'name' => 'OpenBooking', 'class' => self::OPEN_BOOKING, 'context' => 'Booking', 'module' => 'BookingPage', 'detail' => null, 'answeredBy' => [], 'answers' => [], 'readBy' => []],
            self::stickyOf($model, 'command:'.self::OPEN_BOOKING),
        );
    }

    public function testACommandKeepsTheTitleOfItsUseCasesFeatureApart(): void
    {
        $model = self::modelOf(new Facts([self::handlerOf(self::OPEN_BOOKING)], []), ['OpenBooking' => 'Activer et couper ma prise de rendez-vous']);
        $sticky = self::stickyOf($model, 'command:'.self::OPEN_BOOKING);

        self::assertSame(['Open booking', 'Activer et couper ma prise de rendez-vous'], [$sticky['label'] ?? null, $sticky['feature'] ?? null]);
    }

    public function testATagNamingTheContextOfAUseCaseGivesItsTitleOverATagWithoutContext(): void
    {
        $model = self::modelOf(new Facts([self::handlerOf(self::OPEN_BOOKING)], []), ['Booking/OpenBooking' => 'Ouvrir ma prise de rendez-vous', 'OpenBooking' => 'Ouvrir un compte']);

        self::assertSame('Ouvrir ma prise de rendez-vous', self::stickyOf($model, 'command:'.self::OPEN_BOOKING)['feature'] ?? null);
    }

    public function testACommandLeadsToTheEventsTheIntentionOfItsAggregateRecords(): void
    {
        $model = self::modelOf(new Facts([self::handlerOf(self::OPEN_BOOKING)], [
            new Call(self::OPEN_BOOKING.'Handler', '__invoke', self::BOOKING_PAGE, 'open'),
            new Call(self::BOOKING_PAGE, 'open', self::BOOKING_OPENED, Call::CREATION),
        ]));

        self::assertSame([
            ['from' => 'aggregate:'.self::BOOKING_PAGE, 'to' => 'event:'.self::BOOKING_OPENED, 'via' => 'open', 'certain' => true, 'when' => null],
            ['from' => 'command:'.self::OPEN_BOOKING, 'to' => 'aggregate:'.self::BOOKING_PAGE, 'via' => 'open', 'certain' => true, 'when' => null],
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
            ['from' => 'aggregate:'.self::BOOKING_PAGE, 'to' => 'event:'.self::BOOKING_OPENED, 'via' => 'open', 'certain' => false, 'when' => null],
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
            ['from' => 'aggregate:'.self::BOOKING_PAGE, 'to' => 'event:'.self::SLOT_BOOKED, 'via' => 'book', 'certain' => true, 'when' => null],
            ['from' => 'command:'.self::BOOK_SLOT, 'to' => 'aggregate:'.self::BOOKING_PAGE, 'via' => 'book', 'certain' => true, 'when' => null],
        ], self::linksOf($model));
    }

    public function testAPolicyTurnsTheEventItListensToIntoItsCommand(): void
    {
        $model = self::modelOf(new Facts([new ClassFacts(self::FORGET_POLICY, interfaces: [self::POLICY], parameters: [[self::EXCHANGE_FACTS_CHANGED]], returns: [self::FORGET_APPOINTMENT])], []));

        self::assertSame([
            ['from' => 'event:'.self::EXCHANGE_FACTS_CHANGED, 'to' => 'policy:'.self::FORGET_POLICY, 'via' => null, 'certain' => true, 'when' => null],
            ['from' => 'policy:'.self::FORGET_POLICY, 'to' => 'command:'.self::FORGET_APPOINTMENT, 'via' => null, 'certain' => true, 'when' => null],
        ], self::linksOf($model));
    }

    public function testAPolicyThatMaySendNothingSendsItsCommandUncertainly(): void
    {
        $model = self::modelOf(new Facts([new ClassFacts(self::FORGET_POLICY, interfaces: [self::POLICY], parameters: [[self::EXCHANGE_FACTS_CHANGED]], returns: [self::FORGET_APPOINTMENT], mayReturnNothing: true)], []));

        self::assertSame(
            ['from' => 'policy:'.self::FORGET_POLICY, 'to' => 'command:'.self::FORGET_APPOINTMENT, 'via' => null, 'certain' => false, 'when' => null],
            self::linksOf($model)[1] ?? null,
        );
    }

    public function testAReactionToAnEventPublishesWhatItCreates(): void
    {
        $model = self::modelOf(new Facts([new ClassFacts(self::PUBLISH_SLOT_BOOKED, interfaces: [self::EVENT_HANDLER], parameters: [[self::SLOT_BOOKED]])], [
            new Call(self::PUBLISH_SLOT_BOOKED, '__invoke', self::PUBLISHED_SLOT_BOOKED, Call::CREATION),
        ]));

        self::assertSame([
            ['from' => 'event:'.self::SLOT_BOOKED, 'to' => 'policy:'.self::PUBLISH_SLOT_BOOKED, 'via' => null, 'certain' => true, 'when' => null],
            ['from' => 'policy:'.self::PUBLISH_SLOT_BOOKED, 'to' => 'event:'.self::PUBLISHED_SLOT_BOOKED, 'via' => null, 'certain' => true, 'when' => null],
        ], self::linksOf($model));
    }

    public function testAQueryIsAReadModelNamingWhatItPresents(): void
    {
        $model = self::modelOf(new Facts([new ClassFacts(self::CONSULT_MY_BOOKING_PAGE.'Handler', interfaces: [self::QUERY_HANDLER], parameters: [[self::CONSULT_MY_BOOKING_PAGE]], presents: [self::MY_BOOKING_PAGE])], []), ['ConsultMyBookingPage' => 'Consulter ma prise de rendez-vous']);

        self::assertSame(
            ['id' => 'readModel:'.self::CONSULT_MY_BOOKING_PAGE, 'slug' => 'readModel:ConsultMyBookingPage', 'type' => 'readModel', 'label' => 'My booking page', 'feature' => 'Consulter ma prise de rendez-vous', 'name' => 'ConsultMyBookingPage', 'class' => self::CONSULT_MY_BOOKING_PAGE, 'context' => 'Booking', 'module' => 'BookingPage', 'detail' => 'MyBookingPage', 'answeredBy' => [], 'answers' => [], 'readBy' => []],
            self::stickyOf($model, 'readModel:'.self::CONSULT_MY_BOOKING_PAGE),
        );
    }

    public function testAPublishedServiceIsAReadModelOfTheModuleOfItsHandler(): void
    {
        $model = self::modelOf(new Facts([new ClassFacts(ConsultAppointmentsHandler::class, interfaces: [self::QUERY_HANDLER], parameters: [[ConsultAppointments::class]])], []));

        self::assertSame('Appointment', self::stickyOf($model, 'readModel:'.ConsultAppointments::class)['module'] ?? null);
    }

    public function testAPublishedEventSaysSo(): void
    {
        $model = self::modelOf(new Facts([new ClassFacts(self::PUBLISH_SLOT_BOOKED, interfaces: [self::EVENT_HANDLER], parameters: [[self::SLOT_BOOKED]])], [
            new Call(self::PUBLISH_SLOT_BOOKED, '__invoke', self::PUBLISHED_SLOT_BOOKED, Call::CREATION),
        ]));

        self::assertSame('Published to the other contexts', self::stickyOf($model, 'event:'.self::PUBLISHED_SLOT_BOOKED)['detail'] ?? null);
    }

    public function testAPublishedEventIsStampedInItsSlug(): void
    {
        $model = self::modelOf(new Facts([new ClassFacts(self::PUBLISH_SLOT_BOOKED, interfaces: [self::EVENT_HANDLER], parameters: [[self::SLOT_BOOKED]])], [
            new Call(self::PUBLISH_SLOT_BOOKED, '__invoke', self::PUBLISHED_SLOT_BOOKED, Call::CREATION),
        ]));

        self::assertSame(['published:SlotBooked', 'event:SlotBooked'], [self::stickyOf($model, 'event:'.self::PUBLISHED_SLOT_BOOKED)['slug'] ?? null, self::stickyOf($model, 'event:'.self::SLOT_BOOKED)['slug'] ?? null]);
    }

    public function testAnEventAndItsAggregateReadInEnglish(): void
    {
        $model = self::modelOf(new Facts([self::handlerOf(self::OPEN_BOOKING)], [
            new Call(self::OPEN_BOOKING.'Handler', '__invoke', self::BOOKING_PAGE, 'open'),
            new Call(self::BOOKING_PAGE, 'open', self::BOOKING_OPENED, Call::CREATION),
        ]));

        self::assertSame(['Booking page', 'Booking opened'], [self::stickyOf($model, 'aggregate:'.self::BOOKING_PAGE)['label'] ?? null, self::stickyOf($model, 'event:'.self::BOOKING_OPENED)['label'] ?? null]);
    }

    public function testAPortOfTheDomainIsAnExternalSystemAnsweredByWhatItsAdapterReads(): void
    {
        $model = self::modelOf(new Facts([
            self::handlerOf(self::OPEN_BOOKING, [self::CONNECTED_CALENDARS]),
            new ClassFacts(self::CALENDARS_ADAPTER, interfaces: [self::CONNECTED_CALENDARS], dependencies: [self::CONNECTED_CALENDAR_SERVICE]),
        ], []));

        self::assertSame(
            [
                ['id' => 'externalSystem:'.self::CONNECTED_CALENDARS, 'slug' => 'externalSystem:ConnectedCalendars', 'type' => 'externalSystem', 'label' => 'Connected calendars', 'feature' => null, 'name' => 'ConnectedCalendars', 'class' => self::CONNECTED_CALENDARS, 'context' => 'Booking', 'module' => null, 'detail' => 'ConnectorsConnectedCalendars', 'answeredBy' => ['Connectors'], 'answers' => [], 'readBy' => []],
                ['from' => 'command:'.self::OPEN_BOOKING, 'to' => 'externalSystem:'.self::CONNECTED_CALENDARS, 'via' => null, 'certain' => true, 'when' => null],
            ],
            [self::stickyOf($model, 'externalSystem:'.self::CONNECTED_CALENDARS), self::linksOf($model)[0] ?? null],
        );
    }

    public function testAPortIsAnsweredByThePublishedServicesItsAdapterTakes(): void
    {
        $model = self::modelOf(new Facts([
            self::handlerOf(self::OPEN_BOOKING, [self::CONNECTED_CALENDARS]),
            new ClassFacts(self::CALENDARS_ADAPTER, interfaces: [self::CONNECTED_CALENDARS], dependencies: [self::CONNECTED_CALENDAR_SERVICE]),
            new ClassFacts(self::CONNECTED_CALENDAR_HANDLER, interfaces: [self::QUERY_HANDLER, self::CONNECTED_CALENDAR_SERVICE], parameters: [[self::CONNECTED_CALENDAR]]),
        ], []));

        self::assertSame(
            [['context' => 'Connectors', 'slug' => 'readModel:WhichHaveAConnectedCalendar', 'label' => 'Which have a connected calendar']],
            self::stickyOf($model, 'externalSystem:'.self::CONNECTED_CALENDARS)['answers'] ?? null,
        );
    }

    public function testAPublishedServiceIsReadByThePortsOfTheAdaptersThatTakeIt(): void
    {
        $model = self::modelOf(new Facts([
            new ClassFacts(ConsultAppointmentsHandler::class, interfaces: [self::QUERY_HANDLER, self::APPOINTMENTS_SERVICE], parameters: [[ConsultAppointments::class]]),
            new ClassFacts(self::BOOKED_APPOINTMENTS_ADAPTER, interfaces: [self::BOOKED_APPOINTMENTS], dependencies: [self::APPOINTMENTS_SERVICE]),
        ], []));

        self::assertSame(
            [['context' => 'Prospecting', 'slug' => 'externalSystem:BookedAppointments', 'label' => 'Booked appointments']],
            self::stickyOf($model, 'readModel:'.ConsultAppointments::class)['readBy'] ?? null,
        );
    }

    public function testAPublishedServiceTakenByAClassOfNoPortIsReadByThatClass(): void
    {
        $model = self::modelOf(new Facts([
            new ClassFacts(ConsultAppointmentsHandler::class, interfaces: [self::QUERY_HANDLER, self::APPOINTMENTS_SERVICE], parameters: [[ConsultAppointments::class]]),
            new ClassFacts('App\Dashboard\AgendaReader', dependencies: [self::APPOINTMENTS_SERVICE]),
        ], []));

        self::assertSame(
            [['context' => null, 'slug' => null, 'label' => 'AgendaReader']],
            self::stickyOf($model, 'readModel:'.ConsultAppointments::class)['readBy'] ?? null,
        );
    }

    public function testTheReadersOfAPublishedServiceDependOnNoOrderOfTheCode(): void
    {
        $classes = [
            new ClassFacts(ConsultAppointmentsHandler::class, interfaces: [self::QUERY_HANDLER, self::APPOINTMENTS_SERVICE], parameters: [[ConsultAppointments::class]]),
            new ClassFacts('App\\Dashboard\\AgendaReader', dependencies: [self::APPOINTMENTS_SERVICE]),
            new ClassFacts(self::BOOKED_APPOINTMENTS_ADAPTER, interfaces: [self::BOOKED_APPOINTMENTS], dependencies: [self::APPOINTMENTS_SERVICE]),
        ];

        self::assertEquals(self::modelOf(new Facts($classes, [])), self::modelOf(new Facts(array_reverse($classes), [])));
    }

    public function testAReadModelAsksTheExternalSystemsOfTheServicesItsHandlerTakes(): void
    {
        $model = self::modelOf(new Facts([
            new ClassFacts(self::CONSULT_MY_BOOKING_PAGE.'Handler', interfaces: [self::QUERY_HANDLER], parameters: [[self::CONSULT_MY_BOOKING_PAGE]], dependencies: [self::BUSY_PERIODS]),
            new ClassFacts(self::BUSY_PERIODS, dependencies: [self::HOST_AGENDAS]),
        ], []));

        self::assertSame(
            [['from' => 'readModel:'.self::CONSULT_MY_BOOKING_PAGE, 'to' => 'externalSystem:'.self::HOST_AGENDAS, 'via' => null, 'certain' => true, 'when' => null]],
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
                ['id' => 'actor:ROLE_USER', 'slug' => 'actor:ROLE_USER', 'type' => 'actor', 'label' => 'Member', 'feature' => null, 'name' => 'ROLE_USER', 'class' => null, 'context' => 'Booking', 'module' => null, 'detail' => null, 'answeredBy' => [], 'answers' => [], 'readBy' => []],
                ['from' => 'actor:ROLE_USER', 'to' => 'command:'.self::OPEN_BOOKING, 'via' => null, 'certain' => true, 'when' => null],
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
                ['id' => 'actor:public', 'slug' => 'actor:public', 'type' => 'actor', 'label' => 'Anyone with the link', 'feature' => null, 'name' => 'public', 'class' => null, 'context' => 'Booking', 'module' => null, 'detail' => null, 'answeredBy' => [], 'answers' => [], 'readBy' => []],
                ['from' => 'actor:public', 'to' => 'command:'.self::BOOK_SLOT, 'via' => null, 'certain' => true, 'when' => null],
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

        self::assertSame([['from' => 'actor:ROLE_USER', 'to' => 'readModel:'.self::CONSULT_MY_BOOKING_PAGE, 'via' => null, 'certain' => true, 'when' => null]], self::linksOf($model));
    }

    public function testAToolOfTheMcpServerIsTheMembersAi(): void
    {
        $model = self::modelOf(new Facts([
            self::handlerOf(self::OPEN_BOOKING),
            new ClassFacts(self::OPEN_BOOKING_TOOL),
        ], [new Call(self::OPEN_BOOKING_TOOL, '__invoke', self::OPEN_BOOKING, Call::CREATION)]));

        self::assertSame(
            [
                ['id' => 'actor:mcp', 'slug' => 'actor:mcp', 'type' => 'actor', 'label' => 'Member\'s AI', 'feature' => null, 'name' => 'mcp', 'class' => null, 'context' => 'Booking', 'module' => null, 'detail' => null, 'answeredBy' => [], 'answers' => [], 'readBy' => []],
                ['from' => 'actor:mcp', 'to' => 'command:'.self::OPEN_BOOKING, 'via' => null, 'certain' => true, 'when' => null],
            ],
            [self::stickyOf($model, 'actor:mcp'), self::linksOf($model)[0] ?? null],
        );
    }

    public function testACommandOfTheConsoleIsThePlatformOperator(): void
    {
        $model = self::modelOf(new Facts([
            self::handlerOf(self::OPEN_BOOKING),
            new ClassFacts(self::OPEN_BOOKING_COMMAND, console: true),
        ], [new Call(self::OPEN_BOOKING_COMMAND, '__invoke', self::OPEN_BOOKING, Call::CREATION)]));

        self::assertSame(
            [
                ['id' => 'actor:operator', 'slug' => 'actor:operator', 'type' => 'actor', 'label' => 'Platform operator', 'feature' => null, 'name' => 'operator', 'class' => null, 'context' => 'Booking', 'module' => null, 'detail' => null, 'answeredBy' => [], 'answers' => [], 'readBy' => []],
                ['from' => 'actor:operator', 'to' => 'command:'.self::OPEN_BOOKING, 'via' => null, 'certain' => true, 'when' => null],
            ],
            [self::stickyOf($model, 'actor:operator'), self::linksOf($model)[0] ?? null],
        );
    }

    public function testATaskOfTheDailyScheduleIsTheDailyScheduleAndTheConsoleThatRunsItThePlatformOperator(): void
    {
        $model = self::modelOf(new Facts([
            self::handlerOf(self::OPEN_BOOKING),
            new ClassFacts(self::BOOKINGS_OPENER, interfaces: [self::SCHEDULED_TASK]),
            new ClassFacts(self::OPEN_BOOKING_COMMAND, console: true),
        ], [
            new Call(self::BOOKINGS_OPENER, 'open', self::OPEN_BOOKING, Call::CREATION),
            new Call(self::OPEN_BOOKING_COMMAND, '__invoke', self::BOOKINGS_OPENER, 'open'),
        ]));

        self::assertSame(
            [
                'Daily schedule',
                ['from' => 'actor:operator', 'to' => 'command:'.self::OPEN_BOOKING, 'via' => null, 'certain' => true, 'when' => null],
                ['from' => 'actor:schedule', 'to' => 'command:'.self::OPEN_BOOKING, 'via' => null, 'certain' => true, 'when' => null],
            ],
            [self::stickyOf($model, 'actor:schedule')['label'] ?? null, ...self::linksOf($model)],
        );
    }

    public function testAnEntranceSendsTheCommandANamedConstructorOfTheCommandCreates(): void
    {
        $model = self::modelOf(new Facts([
            self::handlerOf(self::OPEN_BOOKING),
            new ClassFacts(self::OPEN_BOOKING_CONTROLLER, roles: ['ROLE_USER']),
        ], [
            new Call(self::OPEN_BOOKING_CONTROLLER, '__invoke', self::OPEN_BOOKING, 'forTheHost'),
            new Call(self::OPEN_BOOKING, 'forTheHost', self::OPEN_BOOKING, Call::CREATION),
        ]));

        self::assertSame([['from' => 'actor:ROLE_USER', 'to' => 'command:'.self::OPEN_BOOKING, 'via' => null, 'certain' => true, 'when' => null]], self::linksOf($model));
    }

    public function testAnEntranceSendsTheUseCasesItCreatesAndNothingElseItCreates(): void
    {
        $model = self::modelOf(new Facts([
            self::handlerOf(self::OPEN_BOOKING),
            new ClassFacts(self::OPEN_BOOKING_CONTROLLER, roles: ['ROLE_USER']),
        ], [
            new Call(self::OPEN_BOOKING_CONTROLLER, '__invoke', self::OPEN_BOOKING, Call::CREATION),
            new Call(self::OPEN_BOOKING_CONTROLLER, '__invoke', self::MY_BOOKING_PAGE_LINKS, Call::CREATION),
        ]));

        self::assertSame([['from' => 'actor:ROLE_USER', 'to' => 'command:'.self::OPEN_BOOKING, 'via' => null, 'certain' => true, 'when' => null]], self::linksOf($model));
    }

    public function testAnEntranceFollowsNoClassOfTheCoreButAUseCasesMessageAndEachMethodOnce(): void
    {
        $model = self::modelOf(new Facts([
            self::handlerOf(self::OPEN_BOOKING),
            self::handlerOf(self::BOOK_SLOT),
            new ClassFacts(self::OPEN_BOOKING_CONTROLLER, roles: ['ROLE_USER']),
        ], [
            new Call(self::OPEN_BOOKING_CONTROLLER, '__invoke', self::MY_BOOKING_PAGE_READER, 'read'),
            new Call(self::MY_BOOKING_PAGE_READER, 'read', self::MY_BOOKING_PAGE_READER, 'read'),
            new Call(self::MY_BOOKING_PAGE_READER, 'read', self::OPEN_BOOKING, Call::CREATION),
            new Call(self::OPEN_BOOKING_CONTROLLER, '__invoke', self::BOOKING_PAGE, 'open'),
            new Call(self::BOOKING_PAGE, 'open', self::BOOK_SLOT, Call::CREATION),
        ]));

        self::assertSame([['from' => 'actor:ROLE_USER', 'to' => 'command:'.self::OPEN_BOOKING, 'via' => null, 'certain' => true, 'when' => null]], self::linksOf($model));
    }

    public function testAProjectionLeadsToTheViewItKeeps(): void
    {
        $model = self::modelOf(new Facts([
            new ClassFacts(RecordExchangeFactsHandler::class, interfaces: [self::COMMAND_HANDLER], parameters: [[RecordExchangeFacts::class]]),
            new ClassFacts(ConsultFiguresHandler::class, interfaces: [self::QUERY_HANDLER], parameters: [[ConsultFigures::class]]),
        ], [
            new Call(RecordExchangeFactsHandler::class, '__invoke', ReportingFactRecorder::class, 'record'),
            new Call(ReportingFactRecorder::class, 'record', ReportingFactRepository::class, 'record'),
        ]), context: 'Reporting');

        self::assertSame([['from' => 'command:'.RecordExchangeFacts::class, 'to' => 'readModel:'.ConsultFigures::class, 'via' => null, 'certain' => true, 'when' => null]], self::linksOf($model));
    }

    public function testACommandThatKeepsNoFactsLeadsToNoView(): void
    {
        $model = self::modelOf(new Facts([
            new ClassFacts(ForgetTagsOfForgottenContactHandler::class, interfaces: [self::COMMAND_HANDLER], parameters: [[ForgetTagsOfForgottenContact::class]]),
            new ClassFacts(ConsultContactTagsHandler::class, interfaces: [self::QUERY_HANDLER], parameters: [[ConsultContactTags::class]]),
        ], [
            new Call(ForgetTagsOfForgottenContactHandler::class, '__invoke', TaggingRepository::class, 'forget'),
            new Call(ForgetTagsOfForgottenContactHandler::class, '__invoke', ForgetTagsOfForgottenContactHandler::class, '__invoke'),
            new Call(ForgetTagsOfForgottenContactHandler::class, '__invoke', ReportingFactRecorder::class, 'record'),
            new Call(ReportingFactRecorder::class, 'record', ReportingFactRepository::class, 'record'),
        ]), context: 'Campaigns');

        self::assertSame([], self::linksOf($model));
    }

    public function testAProjectionLeadsToNoViewOfAnotherModule(): void
    {
        $model = self::modelOf(new Facts([
            new ClassFacts(RecordContactFactsHandler::class, interfaces: [self::COMMAND_HANDLER], parameters: [[RecordContactFacts::class]]),
            new ClassFacts(ConsultContactTagsHandler::class, interfaces: [self::QUERY_HANDLER], parameters: [[ConsultContactTags::class]]),
            new ClassFacts(ConsultCampaignHandler::class, interfaces: [self::QUERY_HANDLER], parameters: [[ConsultCampaign::class]]),
        ], [
            new Call(RecordContactFactsHandler::class, '__invoke', CampaignsFactRecorder::class, 'record'),
            new Call(CampaignsFactRecorder::class, 'record', CampaignsFactRecorder::class, 'keep'),
            new Call(CampaignsFactRecorder::class, 'keep', CampaignsFactRepository::class, 'record'),
        ]), context: 'Campaigns');

        self::assertSame([['from' => 'command:'.RecordContactFacts::class, 'to' => 'readModel:'.ConsultContactTags::class, 'via' => null, 'certain' => true, 'when' => null]], self::linksOf($model));
    }

    public function testAViewOffersTheCommandsItsPresenterLinksTo(): void
    {
        $model = self::modelOf(self::myBookingPageOffering(['setWindows' => self::SET_WINDOWS_ROUTE]));

        self::assertContains(
            ['from' => 'readModel:'.self::CONSULT_MY_BOOKING_PAGE, 'to' => 'command:'.self::SET_WEEKLY_WINDOWS, 'via' => 'setWindows', 'certain' => true, 'when' => null],
            self::linksOf($model),
        );
    }

    public function testAViewOffersACommandOnlyWhileItsLinksResourceSaysSo(): void
    {
        $model = self::modelOf(self::myBookingPageOffering(['open' => self::OPEN_ROUTE], ['open' => 'Open the page. Offered only while the page is closed']));

        self::assertContains(
            ['from' => 'readModel:'.self::CONSULT_MY_BOOKING_PAGE, 'to' => 'command:'.self::OPEN_BOOKING, 'via' => 'open', 'certain' => false, 'when' => 'while the page is closed'],
            self::linksOf($model),
        );
    }

    public function testAConditionTheLinksResourceDoesNotWriteIsUnknown(): void
    {
        $model = self::modelOf(self::myBookingPageOffering(['open' => self::OPEN_ROUTE], ['open' => 'Open the page, while it is closed']));

        self::assertContains(
            ['from' => 'readModel:'.self::CONSULT_MY_BOOKING_PAGE, 'to' => 'command:'.self::OPEN_BOOKING, 'via' => 'open', 'certain' => false, 'when' => null],
            self::linksOf($model),
        );
    }

    public function testATextThatNamesNoRouteOffersNothing(): void
    {
        $model = self::modelOf(self::myBookingPageOffering(['setWindows' => 'a week']));

        self::assertSame([], array_values(array_filter(self::linksOf($model), static fn (array $link): bool => str_starts_with($link['from'], 'readModel:'))));
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
                ['id' => 'policy:'.self::IDENTIFY_BOOKER, 'slug' => 'PersonalData/policy:IdentifyBookerPolicy', 'type' => 'policy', 'label' => 'Identify booker', 'feature' => null, 'name' => 'IdentifyBookerPolicy', 'class' => self::IDENTIFY_BOOKER, 'context' => 'PersonalData', 'module' => null, 'detail' => null, 'answeredBy' => [], 'answers' => [], 'readBy' => []],
                ['from' => 'event:'.self::PUBLISHED_SLOT_BOOKED, 'to' => 'policy:'.self::IDENTIFY_BOOKER, 'via' => null, 'certain' => true, 'when' => null],
            ],
            [self::stickyOf($model, 'policy:'.self::IDENTIFY_BOOKER), self::linksOf($model)[0] ?? null],
        );
    }

    public function testTheSameCodeReadInAnotherOrderGivesTheSameModel(): void
    {
        $classes = [self::handlerOf(self::OPEN_BOOKING), self::handlerOf(self::BOOK_SLOT)];
        $calls = [
            new Call(self::OPEN_BOOKING.'Handler', '__invoke', self::BOOKING_PAGE, 'open'),
            new Call(self::BOOKING_PAGE, 'open', self::BOOKING_OPENED, Call::CREATION),
            new Call(self::BOOK_SLOT.'Handler', '__invoke', self::BOOKING_PAGE, 'book'),
            new Call(self::BOOKING_PAGE, 'book', self::SLOT_BOOKED, Call::CREATION),
        ];

        self::assertEquals(self::modelOf(new Facts($classes, $calls)), self::modelOf(new Facts(array_reverse($classes), array_reverse($calls))));
    }

    public function testTwoGenerationsOfTheSameCodeWriteTheSameModel(): void
    {
        $facts = new Facts([self::handlerOf(self::OPEN_BOOKING), self::handlerOf(self::BOOK_SLOT)], [
            new Call(self::OPEN_BOOKING.'Handler', '__invoke', self::BOOKING_PAGE, 'open'),
            new Call(self::BOOKING_PAGE, 'open', self::BOOKING_OPENED, Call::CREATION),
        ]);

        self::assertSame(json_encode(self::modelOf($facts)), json_encode(self::modelOf($facts)));
    }

    public function testTheModulesAreThoseOfTheUseCasesOfTheContext(): void
    {
        $model = self::modelOf(new Facts([self::handlerOf(self::BOOK_SLOT), self::handlerOf(self::OPEN_BOOKING), self::handlerOf(self::CONSULT_MY_BOOKING_PAGE)], []));

        self::assertSame(['BookingPage', 'PublicLink'], $model->modules);
    }

    /**
     * The member's booking page, its presenter offering a link on each parameter of its links resource to a route,
     * and the controller of each route sending its command.
     *
     * @param array<string, string> $routes   the route of each link, by the links resource's parameter
     * @param array<string, string> $optional the description of each link the resource may go without
     */
    private static function myBookingPageOffering(array $routes, array $optional = []): Facts
    {
        $controllers = [self::SET_WINDOWS_ROUTE => [self::SET_WINDOWS_CONTROLLER, self::SET_WEEKLY_WINDOWS], self::OPEN_ROUTE => [self::OPEN_BOOKING_CONTROLLER, self::OPEN_BOOKING]];
        $classes = [
            new ClassFacts(self::CONSULT_MY_BOOKING_PAGE.'Handler', interfaces: [self::QUERY_HANDLER], parameters: [[self::CONSULT_MY_BOOKING_PAGE], [self::MY_BOOKING_PAGE_OUTPUT]], outputPorts: [self::MY_BOOKING_PAGE_OUTPUT]),
            new ClassFacts(self::MY_BOOKING_PAGE_PRESENTER, interfaces: [self::MY_BOOKING_PAGE_OUTPUT]),
            new ClassFacts(self::MY_BOOKING_PAGE_LINKS, optionalParameters: $optional),
            self::handlerOf(self::SET_WEEKLY_WINDOWS),
            self::handlerOf(self::OPEN_BOOKING),
            ...array_map(static fn (string $route, array $controller): ClassFacts => new ClassFacts($controller[0], roles: ['ROLE_USER'], routes: [$route]), array_keys($controllers), $controllers),
        ];
        $calls = array_map(static fn (array $controller): Call => new Call($controller[0], '__invoke', $controller[1], Call::CREATION), array_values($controllers));
        $arguments = array_map(static fn (string $parameter, string $route): Argument => new Argument(self::MY_BOOKING_PAGE_PRESENTER, self::MY_BOOKING_PAGE_LINKS, $parameter, [$route]), array_keys($routes), $routes);

        return new Facts($classes, $calls, $arguments);
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
    private static function modelOf(Facts $facts, array $titles = [], array $subscriptions = [], string $context = 'Booking'): Model
    {
        return new ContextStorming($facts, $titles, $subscriptions)->model($context);
    }

    /**
     * @return array<mixed>|null the post-it as the model's document writes it
     */
    private static function stickyOf(Model $model, string $id): ?array
    {
        $sticky = array_find($model->stickies, static fn (Sticky $sticky): bool => $id === $sticky->id);

        return null === $sticky ? null : (array) json_decode((string) json_encode($sticky), true, flags: \JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<array{from: string, to: string, via: string|null, certain: bool, when: string|null}>
     */
    private static function linksOf(Model $model): array
    {
        return array_map(static fn (Link $link): array => $link->jsonSerialize(), $model->links);
    }
}
