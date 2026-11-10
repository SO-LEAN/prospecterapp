<?php

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
use Prospecter\Core\PersonalData\Domain\Event\ContactCollected;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\Uuid;
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

    #[DataProvider('events')]
    public function testAnEventSurvivesARoundTrip(string $class): void
    {
        $event = $this->sampleOf($class);

        $this->assertEquals($event, $this->codec()->decode($this->transported($this->codec()->encode($event, new Cause(self::ID, null, 0)))));
    }

    #[DataProvider('events')]
    public function testAnEventIsListedUnderTheSourceOfItsContext(string $class): void
    {
        $context = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', explode('\\', $class)[2]));
        $internal = str_contains($class, '\\Domain\\Event\\') ? '.internal' : '';

        $transported = $this->codec()->encode($this->sampleOf($class), new Cause(self::ID, null, 0));

        $this->assertSame('prospecter.'.$context.$internal, $transported->source);
        $this->assertSame(new \ReflectionClass($class)->getShortName().'.v1', $transported->detailType);
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
        $event = new ContactCollected(new AccountId(self::ID), new MemberId(self::ID), new PersonId(self::ID), [new OrganizationId(self::ID)], new \DateTimeImmutable('2026-10-15 11:00:00.5 Europe/Paris'));

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

            if (!new \ReflectionClass($class)->isAbstract()) {
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
