<?php

declare(strict_types=1);

namespace Tests\App\Architecture;

use App\Contacts\Resource\ContactLinksResource;
use App\Contacts\Resource\ContactMailLinksResource;
use App\Contacts\Resource\ContactProfileLinksResource;
use App\Shared\Resource\Offers;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * A composition copies each piece's links into the contract's links resource that mixes several contexts' links (ADR
 * 0217). The walls read the piece's declarations, and the API tests check the contract's, which they serialize. So
 * each property of a piece declares the routes of the contract's property of the same name.
 */
#[Group('unit')]
final class PiecesOffersMatchTheirContractsTest extends TestCase
{
    /**
     * Each piece's links resource, with the contract's links resource the composition copies it into.
     */
    private const array PIECES = [
        ContactProfileLinksResource::class => ContactLinksResource::class,
        ContactMailLinksResource::class => ContactLinksResource::class,
    ];

    /**
     * @param class-string $piece
     * @param class-string $contract
     */
    #[DataProvider('pieces')]
    public function testEachPropertyOfAPieceOffersWhatItsContractOffers(string $piece, string $contract): void
    {
        $this->assertSame([], self::mismatches($piece, $contract));
    }

    /**
     * @return iterable<string, array{class-string, class-string}>
     */
    public static function pieces(): iterable
    {
        foreach (self::PIECES as $piece => $contract) {
            yield $piece => [$piece, $contract];
        }
    }

    /**
     * @param class-string $piece
     * @param class-string $contract
     *
     * @return list<string> "piece::property declares [routes], contract::property [routes]" for each mismatch
     */
    private static function mismatches(string $piece, string $contract): array
    {
        $contractClass = new \ReflectionClass($contract);
        $mismatches = [];

        foreach (new \ReflectionClass($piece)->getProperties() as $property) {
            $name = $property->getName();
            $declared = self::offersOf($property);
            $copiedInto = $contractClass->hasProperty($name) ? self::offersOf($contractClass->getProperty($name)) : null;

            if ($declared !== $copiedInto) {
                $mismatches[] = sprintf('%s::%s declares [%s], %s::%s [%s]', $piece, $name, implode(', ', $declared), $contract, $name, null === $copiedInto ? 'no such property' : implode(', ', $copiedInto));
            }
        }

        return $mismatches;
    }

    /**
     * @return list<string>
     */
    private static function offersOf(\ReflectionProperty $property): array
    {
        $offers = $property->getAttributes(Offers::class)[0] ?? null;

        return $offers?->newInstance()->routes ?? [];
    }
}
