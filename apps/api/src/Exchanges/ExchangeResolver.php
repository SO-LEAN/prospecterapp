<?php

declare(strict_types=1);

namespace App\Exchanges;

use App\Prospecting\Persistence\ExchangeRevisions;
use App\Security\User;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ConsultExchange;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\ConsultExchange\KeptExchange;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * The exchange a route names by its `id`, as the signed-in member reads it: the subject `ExchangeVoter` decides
 * on before the controller runs (ADR 0081). An unknown one is refused by the core (404).
 */
final readonly class ExchangeResolver implements ValueResolverInterface
{
    public function __construct(private QueryHandler $consultExchange, private Security $security, private ClockInterface $clock, private ExchangeRevisions $revisions)
    {
    }

    /**
     * @return list<ExchangeDetail>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $reader = $this->security->getUser();

        if (ExchangeDetail::class !== $argument->getType() || !$reader instanceof User) {
            return [];
        }

        return [$this->read($reader, $request->attributes->getString('id'))];
    }

    /**
     * The exchange of this id as its reader reads it, for the voter, whatever its account; an unknown one refused by
     * the core.
     */
    public function read(User $reader, string $id): ExchangeDetail
    {
        $kept = new KeptExchange();
        $this->revisions->watch($id);
        ($this->consultExchange)(new ConsultExchange($reader->memberId(), $id, $this->clock->now()), $kept);

        return $kept->exchange;
    }
}
