<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Policy\PublicMailDomains;
use Prospecter\Core\SharedKernel\Domain\Designated;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\Siret;

/**
 * The clues that designate one organization in its account: its SIRET, its links, its website's domain, its emails,
 * and the domains of its staff's emails. A clue that is no key of its kind designates nothing.
 */
final readonly class OrganizationKeys
{
    /**
     * @param list<ExternalReference> $references
     * @param list<Email>             $emails
     */
    private function __construct(
        public ?Siret $siret,
        public array $references,
        public ?Website $website,
        public array $emails,
    ) {
    }

    /**
     * @param list<string> $links
     * @param list<string> $emails
     */
    public static function among(?string $siret, array $links, ?string $website, array $emails): self
    {
        return new self(
            self::orNone(static fn (): ?Siret => Siret::orNone($siret)),
            self::each($links, static fn (string $link): ExternalReference => ExternalReference::designating($link, Designated::Organization)),
            self::orNone(static fn (): ?Website => Website::orNone($website)),
            self::each($emails, static fn (string $email): Email => new Email($email)),
        );
    }

    /**
     * The emails' domains, except those of public mailboxes, which designate no organization.
     *
     * @return list<string>
     */
    public function emailDomains(): array
    {
        $private = array_filter($this->emails, static fn (Email $email): bool => !PublicMailDomains::includes($email));

        return array_values(array_map(static fn (Email $email): string => $email->domain(), $private));
    }

    /**
     * @template T of object
     *
     * @param \Closure(): ?T $key
     *
     * @return T|null
     */
    private static function orNone(\Closure $key): ?object
    {
        try {
            return $key();
        } catch (BusinessRuleViolation) {
            return null;
        }
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
        return array_values(array_filter(array_map(static fn (string $clue): ?object => self::orNone(static fn (): object => $key($clue)), $clues)));
    }
}
