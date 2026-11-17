<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\Campaigns\Domain\Exception\InvalidTag;
use Prospecter\Core\Campaigns\Domain\Policy\AutomaticTags;

/**
 * A tag, written one way whatever a member typed (ADR 0138): a `#` then 1 to 30 capital letters without accents,
 * digits and underscores, the accents removed and every other run of characters an underscore (`éditeur logiciel`
 * is #EDITEUR_LOGICIEL).
 */
final readonly class Tag implements \Stringable
{
    public const string MARK = '#';
    private const string ACCENTS = '/\p{M}+/u';
    private const string SEPARATORS = '/[^A-Z0-9]+/';
    private const string SHAPE = '/^[A-Z0-9_]{1,30}$/';

    private function __construct(public string $name)
    {
    }

    /**
     * @throws InvalidTag
     */
    public static function written(string $typed): self
    {
        $name = $typed
            |> (static fn (string $text): string => (string) \Normalizer::normalize($text, \Normalizer::FORM_D))
            |> (static fn (string $decomposed): string => (string) preg_replace(self::ACCENTS, '', $decomposed))
            |> mb_strtoupper(...)
            |> (static fn (string $upper): string => (string) preg_replace(self::SEPARATORS, '_', $upper))
            |> (static fn (string $separated): string => trim($separated, '_'));

        return 1 === preg_match(self::SHAPE, $name) ? new self($name) : throw InvalidTag::written($typed);
    }

    public function isAutomatic(): bool
    {
        return AutomaticTags::reserve($this->name);
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name;
    }

    public function __toString(): string
    {
        return self::MARK.$this->name;
    }
}
