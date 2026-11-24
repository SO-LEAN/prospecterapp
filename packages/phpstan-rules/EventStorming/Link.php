<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * An arrow between two post-its, through a method or a link when one carries it. An uncertain link happens only
 * under a condition, or could not be read for sure. Its condition is in English when the code writes it.
 */
final readonly class Link implements \JsonSerializable
{
    public function __construct(public string $from, public string $to, public ?string $via = null, public bool $certain = true, public ?string $when = null)
    {
    }

    /**
     * @return array{from: string, to: string, via: string|null, certain: bool, when: string|null}
     */
    public function jsonSerialize(): array
    {
        return ['from' => $this->from, 'to' => $this->to, 'via' => $this->via, 'certain' => $this->certain, 'when' => $this->when];
    }
}
