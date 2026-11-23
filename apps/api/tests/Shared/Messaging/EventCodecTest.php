<?php

declare(strict_types=1);

namespace Tests\App\Shared\Messaging;

use App\Shared\Identities;
use App\Shared\Messaging\Cause;
use App\Shared\Messaging\DomainEventTypes;
use App\Shared\Messaging\EventCodec;
use App\Shared\Messaging\IntegrationEventTypes;
use App\Shared\Messaging\Payloads;
use App\Shared\Messaging\TransportedEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Accounts\Contract\Event\AccountOpened;
use Prospecter\Core\Connectors\Contract\Event\EmailExchanged;
use Prospecter\Core\PersonalData\Contract\Event\ContactFactsChanged;
use Prospecter\Core\PersonalData\Domain\Event\ContactCollected;
use Prospecter\Core\PersonalData\Domain\Model\OrganizationId as PersonalDataOrganizationId;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\Prospecting\Contract\Event\OrganizationFactsChanged;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeErased;
use Prospecter\Core\Prospecting\Domain\Model\CampaignId;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Prospecter\Core\SharedKernel\Domain\Identity\Uuid;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;

/**
 * Every event travels as an event of EventBridge: a domain event from `prospecter.<context>.internal`, an
 * integration event from `prospecter.<context>`, its detail-type its short name and the version of its contract,
 * and comes back equal to what was published.
 */
class EventCodecTest extends TestCase
{
    private const string MOMENT = '2026-10-15 09:00:00.123456 UTC';
    private const string ID = '0199f1d2-6a3b-7c4d-8e5f-0123456789ab';
    private const int REVISION = 3;
    private const string MOMENT_OF_THE_BUS = '2026-10-15T09:00:00.123456Z';

    #[DataProvider('events')]
    public function testAnEventSurvivesARoundTrip(string $class): void
    {
        $event = $this->sampleOf($class);

        $this->assertEquals($event, $this->codec()->decode($this->transported($this->codec()->encode($event, new Cause(self::ID, null, 0)))));
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('events')]
    public function testAnEventIsListedUnderTheSourceOfItsContext(string $class): void
    {
        $context = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', explode('\\', $class)[2]));
        $internal = str_contains($class, '\\Domain\\Event\\') ? '.internal' : '';

        $transported = $this->codec()->encode($this->sampleOf($class), new Cause(self::ID, null, 0));

        $this->assertSame('prospecter.'.$context.$internal, $transported->source);
        $this->assertMatchesRegularExpression('/^'.new \ReflectionClass($class)->getShortName().'\.v\d+$/', $transported->detailType);
    }

    public function testTheFormerVersionOfAContractIsStillRead(): void
    {
        $former = new TransportedEvent('prospecter.prospecting', 'ExchangeFactsChanged.v1', self::ID, self::MOMENT_OF_THE_BUS, self::ID, null, 0, [
            'accountId' => self::ID, 'exchangeId' => self::ID, 'revision' => self::REVISION, 'present' => true, 'notedAt' => self::MOMENT_OF_THE_BUS, 'occurredAt' => self::MOMENT_OF_THE_BUS,
        ]);

        $this->assertEquals(
            new ExchangeFactsChanged(self::ID, self::ID, self::REVISION, true, null, new \DateTimeImmutable(self::MOMENT_OF_THE_BUS), new \DateTimeImmutable(self::MOMENT_OF_THE_BUS)),
            $this->codec()->decode($former),
        );
    }

    public function testTheCurrentVersionOfAContractIsWritten(): void
    {
        $facts = new ExchangeFactsChanged(self::ID, self::ID, self::REVISION, true, 'planned', new \DateTimeImmutable(self::MOMENT), new \DateTimeImmutable(self::MOMENT));

        $this->assertSame('ExchangeFactsChanged.v4', $this->codec()->encode($facts, new Cause(self::ID, null, 0))->detailType);
    }

    public function testTheFactsOfAnExchangePublishedBeforeTheySaidItWentWithItsOrganizationAreReadAsErasedByAMember(): void
    {
        $former = new TransportedEvent('prospecter.prospecting', 'ExchangeFactsChanged.v3', self::ID, self::MOMENT_OF_THE_BUS, self::ID, null, 0, [
            'accountId' => self::ID, 'exchangeId' => self::ID, 'revision' => 0, 'present' => false, 'status' => null, 'notedAt' => null, 'occurredAt' => self::MOMENT_OF_THE_BUS, 'contactId' => self::ID, 'campaignId' => self::ID,
        ]);

        $this->assertEquals(
            new ExchangeFactsChanged(self::ID, self::ID, 0, false, null, null, new \DateTimeImmutable(self::MOMENT_OF_THE_BUS), self::ID, self::ID),
            $this->codec()->decode($former),
        );
    }

    public function testTheFactsOfAnExchangePublishedBeforeTheyNamedItsContactAndItsCampaignAreReadWithoutThem(): void
    {
        $former = new TransportedEvent('prospecter.prospecting', 'ExchangeFactsChanged.v2', self::ID, self::MOMENT_OF_THE_BUS, self::ID, null, 0, [
            'accountId' => self::ID, 'exchangeId' => self::ID, 'revision' => self::REVISION, 'present' => true, 'status' => 'done', 'notedAt' => self::MOMENT_OF_THE_BUS, 'occurredAt' => self::MOMENT_OF_THE_BUS,
        ]);

        $this->assertEquals(
            new ExchangeFactsChanged(self::ID, self::ID, self::REVISION, true, 'done', new \DateTimeImmutable(self::MOMENT_OF_THE_BUS), new \DateTimeImmutable(self::MOMENT_OF_THE_BUS)),
            $this->codec()->decode($former),
        );
    }

    public function testTheFactsPublishedBeforeTheyToldTheContactsAndTheReachAreReadWithoutThem(): void
    {
        $organization = new TransportedEvent('prospecter.prospecting', 'OrganizationFactsChanged.v1', self::ID, self::MOMENT_OF_THE_BUS, self::ID, null, 0, [
            'accountId' => self::ID, 'organizationId' => self::ID, 'revision' => self::REVISION, 'present' => true, 'located' => false, 'occurredAt' => self::MOMENT_OF_THE_BUS,
        ]);
        $contact = new TransportedEvent('prospecter.personal-data', 'ContactFactsChanged.v1', self::ID, self::MOMENT_OF_THE_BUS, self::ID, null, 0, [
            'accountId' => self::ID, 'personId' => self::ID, 'revision' => self::REVISION, 'present' => true, 'occurredAt' => self::MOMENT_OF_THE_BUS,
        ]);

        $this->assertEquals(new OrganizationFactsChanged(self::ID, self::ID, self::REVISION, true, false, null, null, null, new \DateTimeImmutable(self::MOMENT_OF_THE_BUS)), $this->codec()->decode($organization));
        $this->assertEquals(new ContactFactsChanged(self::ID, self::ID, self::REVISION, true, null, null, null, null, new \DateTimeImmutable(self::MOMENT_OF_THE_BUS)), $this->codec()->decode($contact));
    }

    public function testTheFactsOfAnOrganizationPublishedBeforeTheyToldItsMunicipalityAreReadWithoutIt(): void
    {
        $organization = new TransportedEvent('prospecter.prospecting', 'OrganizationFactsChanged.v2', self::ID, self::MOMENT_OF_THE_BUS, self::ID, null, 0, [
            'accountId' => self::ID, 'organizationId' => self::ID, 'revision' => self::REVISION, 'present' => true, 'located' => true, 'contacts' => [self::ID], 'occurredAt' => self::MOMENT_OF_THE_BUS,
        ]);

        $this->assertEquals(new OrganizationFactsChanged(self::ID, self::ID, self::REVISION, true, true, [self::ID], null, null, new \DateTimeImmutable(self::MOMENT_OF_THE_BUS)), $this->codec()->decode($organization));
    }

    public function testTheFactsAreWrittenWithTheContactsTheMunicipalityAndTheReach(): void
    {
        $organization = new OrganizationFactsChanged(self::ID, self::ID, self::REVISION, true, true, [self::ID], '67482', 'FR', new \DateTimeImmutable(self::MOMENT));
        $contact = new ContactFactsChanged(self::ID, self::ID, self::REVISION, true, true, true, false, true, new \DateTimeImmutable(self::MOMENT));

        $this->assertSame('OrganizationFactsChanged.v3', $this->codec()->encode($organization, new Cause(self::ID, null, 0))->detailType);
        $this->assertSame('ContactFactsChanged.v2', $this->codec()->encode($contact, new Cause(self::ID, null, 0))->detailType);
    }

    public function testAnEMailExchangedIsWrittenWithItsAttachments(): void
    {
        $exchanged = new EmailExchanged(self::ID, self::ID, 'google', 'f00d', 'inbound', self::ID, null, 'Votre devis', 'Bonjour', new \DateTimeImmutable(self::MOMENT), new \DateTimeImmutable(self::MOMENT), '18c2f4a9e0b1d3c7', [['upload' => '0a', 'name' => 'devis.pdf', 'size' => 307200]], [['name' => 'video.mp4', 'size' => 26214400]]);

        $this->assertSame('EmailExchanged.v3', $this->codec()->encode($exchanged, new Cause(self::ID, null, 0))->detailType);
    }

    public function testTheAttachmentsOfAnEMailExchangedAreReadBack(): void
    {
        $exchanged = new EmailExchanged(self::ID, self::ID, 'google', 'f00d', 'inbound', self::ID, null, 'Votre devis', 'Bonjour', new \DateTimeImmutable(self::MOMENT_OF_THE_BUS), new \DateTimeImmutable(self::MOMENT_OF_THE_BUS), '18c2f4a9e0b1d3c7', [['upload' => '0a', 'name' => 'devis.pdf', 'size' => 307200]], [['name' => 'video.mp4', 'size' => 26214400]]);

        $this->assertEquals($exchanged, $this->codec()->decode($this->codec()->encode($exchanged, new Cause(self::ID, null, 0))));
    }

    public function testAnEMailExchangedPublishedBeforeItNamedItsAttachmentsIsReadWithoutThem(): void
    {
        $former = new TransportedEvent('prospecter.connectors', 'EmailExchanged.v2', self::ID, self::MOMENT_OF_THE_BUS, self::ID, null, 0, [
            'accountId' => self::ID, 'memberId' => self::ID, 'provider' => 'google', 'emailId' => 'f00d', 'direction' => 'inbound', 'contactId' => self::ID, 'organizationId' => null, 'subject' => 'Votre devis', 'text' => 'Bonjour', 'exchangedAt' => self::MOMENT_OF_THE_BUS, 'occurredAt' => self::MOMENT_OF_THE_BUS, 'providerMessageId' => '18c2f4a9e0b1d3c7',
        ]);

        $this->assertEquals(new EmailExchanged(self::ID, self::ID, 'google', 'f00d', 'inbound', self::ID, null, 'Votre devis', 'Bonjour', new \DateTimeImmutable(self::MOMENT_OF_THE_BUS), new \DateTimeImmutable(self::MOMENT_OF_THE_BUS), '18c2f4a9e0b1d3c7'), $this->codec()->decode($former));
    }

    public function testAnEMailExchangedPublishedBeforeItToldTheIdOfItsMessageIsReadWithoutIt(): void
    {
        $former = new TransportedEvent('prospecter.connectors', 'EmailExchanged.v1', self::ID, self::MOMENT_OF_THE_BUS, self::ID, null, 0, [
            'accountId' => self::ID, 'memberId' => self::ID, 'provider' => 'google', 'emailId' => 'f00d', 'direction' => 'inbound', 'contactId' => self::ID, 'organizationId' => null, 'subject' => 'Votre devis', 'text' => 'Bonjour', 'exchangedAt' => self::MOMENT_OF_THE_BUS, 'occurredAt' => self::MOMENT_OF_THE_BUS,
        ]);

        $this->assertEquals(new EmailExchanged(self::ID, self::ID, 'google', 'f00d', 'inbound', self::ID, null, 'Votre devis', 'Bonjour', new \DateTimeImmutable(self::MOMENT_OF_THE_BUS), new \DateTimeImmutable(self::MOMENT_OF_THE_BUS)), $this->codec()->decode($former));
    }

    public function testTheErasureOfAnExchangeIsWrittenWithItsContactItsCampaignAndWhetherItWentWithItsOrganization(): void
    {
        $erased = new ExchangeErased(new AccountId(self::ID), new MemberId(self::ID), new ExchangeId(self::ID), [], new \DateTimeImmutable(self::MOMENT), new ContactId(self::ID), new CampaignId(self::ID), withItsOrganization: true);

        $this->assertSame('ExchangeErased.v3', $this->codec()->encode($erased, new Cause(self::ID, null, 0))->detailType);
    }

    public function testTheErasureOfAnExchangePublishedBeforeItNamedItsContactAndItsCampaignIsReadWithoutThem(): void
    {
        $former = new TransportedEvent('prospecter.prospecting.internal', 'ExchangeErased.v1', self::ID, self::MOMENT_OF_THE_BUS, self::ID, null, 0, [
            'accountId' => self::ID, 'by' => self::ID, 'exchange' => self::ID, 'files' => [], 'occurredAt' => self::MOMENT_OF_THE_BUS,
        ]);

        $this->assertEquals(new ExchangeErased(new AccountId(self::ID), new MemberId(self::ID), new ExchangeId(self::ID), [], new \DateTimeImmutable(self::MOMENT_OF_THE_BUS)), $this->codec()->decode($former));
    }

    public function testTheErasureOfAnExchangePublishedBeforeItSaidItWentWithItsOrganizationIsReadAsByAMember(): void
    {
        $former = new TransportedEvent('prospecter.prospecting.internal', 'ExchangeErased.v2', self::ID, self::MOMENT_OF_THE_BUS, self::ID, null, 0, [
            'accountId' => self::ID, 'by' => self::ID, 'exchange' => self::ID, 'files' => [], 'occurredAt' => self::MOMENT_OF_THE_BUS, 'contact' => self::ID, 'campaign' => self::ID,
        ]);

        $this->assertEquals(new ExchangeErased(new AccountId(self::ID), new MemberId(self::ID), new ExchangeId(self::ID), [], new \DateTimeImmutable(self::MOMENT_OF_THE_BUS), new ContactId(self::ID), new CampaignId(self::ID)), $this->codec()->decode($former));
    }

    public function testADomainEventAndAnIntegrationEventOfTheSameNameAreToldApartByTheirSource(): void
    {
        $published = new AccountOpened(self::ID, self::ID, self::ID, new \DateTimeImmutable(self::MOMENT));
        $transported = $this->codec()->encode($published, new Cause(self::ID, null, 0));

        $this->assertSame('prospecter.accounts', $transported->source);
        $this->assertEquals($published, $this->codec()->decode($transported));
    }

    public function testTheDetailCarriesTheIdentityTheMomentAndTheCause(): void
    {
        $event = new ContactCollected(new AccountId(self::ID), new MemberId(self::ID), new PersonId(self::ID), [new PersonalDataOrganizationId(self::ID)], new \DateTimeImmutable('2026-10-15 11:00:00.5 Europe/Paris'));

        $detail = $this->codec()->encode($event, new Cause('0199f1d2-0000-7000-8000-000000000001', '0199f1d2-0000-7000-8000-000000000002', 3))->toEventBridge()['detail'];

        $this->assertTrue(Uuid::isValid($detail['eventId']));
        $this->assertSame('2026-10-15T09:00:00.500000Z', $detail['occurredAt']);
        $this->assertSame('0199f1d2-0000-7000-8000-000000000001', $detail['correlationId']);
        $this->assertSame('0199f1d2-0000-7000-8000-000000000002', $detail['causationId']);
        $this->assertSame(3, $detail['depth']);
        $this->assertSame([self::ID], $detail['payload']['organizations']);
    }

    /**
     * Every class of `<Context>/Contract/Event` and `<Context>/Domain/Event` of the core.
     *
     * @return iterable<string, array{class-string}>
     */
    public static function events(): iterable
    {
        $root = dirname(__DIR__, 3).'/vendor/prospecter/core/src/';

        foreach (glob($root.'*/{Contract,Domain}/Event/*.php', \GLOB_BRACE) ?: [] as $file) {
            $class = 'Prospecter\\Core\\'.str_replace(['/', '.php'], ['\\', ''], substr($file, strlen($root)));

            if (class_exists($class) && !new \ReflectionClass($class)->isAbstract()) {
                yield $class => [$class];
            }
        }
    }

    /**
     * Through JSON, as on the bus.
     */
    private function transported(TransportedEvent $event): TransportedEvent
    {
        return TransportedEvent::fromEventBridge(json_decode(json_encode($event->toEventBridge(), \JSON_THROW_ON_ERROR), true, flags: \JSON_THROW_ON_ERROR));
    }

    private function codec(): EventCodec
    {
        return new EventCodec(new DomainEventTypes(), new IntegrationEventTypes(), new Payloads(TypeResolver::create()), new Identities());
    }

    private function sampleOf(string $class): object
    {
        $arguments = [];

        foreach (new \ReflectionMethod($class, '__construct')->getParameters() as $parameter) {
            $arguments[$parameter->name] = $this->sampleValue(TypeResolver::create()->resolve($parameter));
        }

        return new $class(...$arguments);
    }

    private function sampleValue(Type $type): mixed
    {
        return match (true) {
            $type instanceof NullableType => $this->sampleValue($type->getWrappedType()),
            $type instanceof CollectionType => [$this->sampleValue($type->getCollectionValueType()), $this->sampleValue($type->getCollectionValueType())],
            $type instanceof ObjectType && \DateTimeImmutable::class === $type->getClassName() => new \DateTimeImmutable(self::MOMENT),
            $type instanceof ObjectType && is_a($type->getClassName(), Uuid::class, true) => new ($type->getClassName())(self::ID),
            $type->isIdentifiedBy(TypeIdentifier::INT) => self::REVISION,
            $type->isIdentifiedBy(TypeIdentifier::BOOL) => true,
            default => self::ID,
        };
    }
}
