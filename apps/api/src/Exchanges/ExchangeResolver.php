<?php

declare(strict_types=1);

namespace App\Exchanges;

use App\Prospecting\Persistence\ExchangeRevisions;
use App\Security\User;
use Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange\ConsultExchange;
use Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange\KeptExchange;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Resolves the exchange that a route names by its `id`, as the signed-in member reads it. `ExchangeVoter` decides
 * on it before the controller runs (ADR 0081). The core refuses an unknown exchange (404).
 */
final readonly class ExchangeResolver implements ValueResolverInterface
{
    public function __construct(private QueryHandler $consultExchange, private Security $security, private ClockInterface $clock, private ExchangeRevisions $revisions)
    {
    }

    /**
     * @return list<ExchangeDetail|KeptExchange>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $reader = $this->security->getUser();

        if (!$reader instanceof User) {
            return [];
        }

        return match ($argument->getType()) {
            ExchangeDetail::class => [$this->read($reader, $request->attributes->getString('id'))],
            KeptExchange::class => [$this->kept($reader, $request->attributes->getString('id'))],
            default => [],
        };
    }

    /**
     * Reads the exchange with this id for the voter, whatever its account. The core refuses an unknown exchange.
     */
    public function read(User $reader, string $id): ExchangeDetail
    {
        return $this->kept($reader, $id)->exchange;
    }

    /**
     * The same, with the organization itself that its detail shows, so that the detail is composed without a second read.
     */
    public function kept(User $reader, string $id): KeptExchange
    {
        $kept = new KeptExchange();
        $this->revisions->watch($id);
        $this->consultExchange->__invoke(new ConsultExchange($reader->memberId(), $id, $this->clock->now()), $kept);

        return $kept;
    }
}
