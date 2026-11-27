<?php

declare(strict_types=1);

namespace App\Exchanges;

use App\Contacts\PersonsOfAView;
use App\Exchanges\Resource\NotingResource;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;

/**
 * What a tool that logs an exchange answers the AI. It answers the logged exchange. When the core refuses the
 * exchange as logged before, it answers why the exchange is not logged again, with who erased it named by Accounts and
 * PersonalData (ADR 0217). That answer is a result, not a tool error (ADR 0166).
 */
final readonly class NotedOnceAnswers
{
    private const string ALREADY_NOTED = 'exchangeAlreadyNoted';
    private const string ERASED_BEFORE = 'exchangeErasedBefore';
    private const string NOTED_MEANWHILE = 'exchangeNotedMeanwhile';

    public function __construct(private ExchangeComposition $exchangeComposition, private PersonsOfAView $personsOfAView)
    {
    }

    /**
     * @param \Closure(): void $note logs the exchange as the member
     */
    public function of(string $member, string $exchange, \Closure $note): NotingResource
    {
        return $this->ofNoted($member, static fn (): string => $exchange, $note);
    }

    /**
     * The same, for a sending logged for a campaign's recipient. The exchange may be one logged earlier that joins the
     * campaign (ADR 0195). So its id is known only once the logging is done.
     *
     * @param \Closure(): string $exchange gives the logged exchange's id, called once the exchange is logged
     * @param \Closure(): void   $note     logs the exchange as the member
     */
    public function ofNoted(string $member, \Closure $exchange, \Closure $note): NotingResource
    {
        try {
            $note();
        } catch (UseCaseRefused $refusal) {
            return $this->notNotedAgain($member, $refusal);
        }

        return NotingResource::noted($this->exchangeComposition->read($member, $exchange()));
    }

    private function notNotedAgain(string $member, UseCaseRefused $refusal): NotingResource
    {
        return match ($refusal->reason) {
            self::ALREADY_NOTED => NotingResource::alreadyNoted($this->exchangeComposition->read($member, $refusal->context['exchange'])),
            self::ERASED_BEFORE => NotingResource::erasedBefore($this->personsOfAView->shownTo($member, [], [$refusal->context['erasedBy']])->author($refusal->context['erasedBy']), $refusal->context['erasedAt']),
            self::NOTED_MEANWHILE => NotingResource::alreadyNoted(null),
            default => throw $refusal,
        };
    }
}
