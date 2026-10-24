<?php

namespace App\Exchanges;

use App\Security\User;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ConsultExchange;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetailResponse;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * The exchange a route names by its `id`, as the signed-in member reads it: the subject `ExchangeVoter` decides
 * on before the controller runs (ADR 0081). An unknown one is refused by the core (404).
 */
final readonly class ExchangeResolver implements ValueResolverInterface
{
    public function __construct(private RefusalTranslatingHandler $consultExchange, private Security $security)
    {
    }

    /**
     * @return list<ExchangeDetailResponse>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $reader = $this->security->getUser();

        if (ExchangeDetailResponse::class !== $argument->getType() || !$reader instanceof User) {
            return [];
        }

        return [($this->consultExchange)(new ConsultExchange($reader->memberId(), $request->attributes->getString('id')))];
    }
}
