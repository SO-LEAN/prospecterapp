<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Designated;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

/**
 * What designates one contact in their account, among the clues given of them: their links, their emails and their
 * mobiles, a landline being shared by a whole organization; a clue that is no key of its kind designates nobody.
 */
final readonly class PersonKeys
{
    /**
     * @param list<ExternalReference> $references
     * @param list<Email>             $emails
     * @param list<PhoneNumber>       $phoneNumbers the mobiles
     */
    private function __construct(
        public array $references,
        public array $emails,
        public array $phoneNumbers,
    ) {
    }

    /**
     * @param list<string> $links
     * @param list<string> $emails
     * @param list<string> $phoneNumbers
     */
    public static function among(array $links, array $emails, array $phoneNumbers): self
    {
        return new self(
            self::each($links, static fn (string $link): ExternalReference => ExternalReference::designating($link, Designated::Person)),
            self::each($emails, static fn (string $email): Email => new Email($email)),
            array_values(array_filter(self::each($phoneNumbers, PhoneNumber::of(...)), static fn (PhoneNumber $phoneNumber): bool => $phoneNumber->isMobile())),
        );
    }

    /**
     * @template T of object
     *
     * @param list<string>        $clues
     * @param \Closure(string): T $key
     *
     * @return list<T>
     */
    private static function each(array $clues, \Closure $key): array
    {
        return array_values(array_filter(array_map(static fn (string $clue): ?object => self::orNone($key, $clue), $clues)));
    }

    /**
     * @template T of object
     *
     * @param \Closure(string): T $key
     *
     * @return T|null
     */
    private static function orNone(\Closure $key, string $clue): ?object
    {
        try {
            return $key($clue);
        } catch (BusinessRuleViolation) {
            return null;
        }
    }
}
