<?php

namespace App\Exchanges;

use App\Exchanges\Resource\NotingResource;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;

/**
 * What a tool noting an exchange answers the AI: the exchange noted, or, refused as noted before, why it is not noted
 * again, as a result and not as the error of the tool (ADR 0166).
 */
final readonly class NotedOnceAnswers
{
    private const string ALREADY_NOTED = 'exchangeAlreadyNoted';
    private const string ERASED_BEFORE = 'exchangeErasedBefore';
    private const string NOTED_MEANWHILE = 'exchangeNotedMeanwhile';

    public function __construct(private ExchangeReader $exchangeReader)
    {
    }

    /**
     * @param \Closure(): void $note the noting of the exchange by the member
     */
    public function of(string $member, string $exchange, \Closure $note): NotingResource
    {
        try {
            $note();
        } catch (UseCaseRefused $refusal) {
            return $this->notNotedAgain($member, $refusal);
        }

        return NotingResource::noted($this->exchangeReader->read($member, $exchange));
    }

    private function notNotedAgain(string $member, UseCaseRefused $refusal): NotingResource
    {
        return match ($refusal->reason) {
            self::ALREADY_NOTED => NotingResource::alreadyNoted($this->exchangeReader->read($member, $refusal->context['exchange'])),
            self::ERASED_BEFORE => NotingResource::erasedBefore($this->exchangeReader->memberNamed($member, $refusal->context['erasedBy']), $refusal->context['erasedAt']),
            self::NOTED_MEANWHILE => NotingResource::alreadyNoted(null),
            default => throw $refusal,
        };
    }
}
