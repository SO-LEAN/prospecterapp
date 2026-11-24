<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * An arrow between two post-its, through a method when one carries it. An uncertain link happens only under a
 * condition, or could not be read for sure.
 */
final readonly class Link implements \JsonSerializable
{
    public function __construct(public string $from, public string $to, public ?string $via = null, public bool $certain = true)
    {
    }

    /**
     * @return array{from: string, to: string, via: string|null, certain: bool}
     */
    public function jsonSerialize(): array
    {
        return ['from' => $this->from, 'to' => $this->to, 'via' => $this->via, 'certain' => $this->certain];
    }
}
