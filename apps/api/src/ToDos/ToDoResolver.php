<?php

namespace App\ToDos;

use Prospecter\Core\Prospecting\Application\ConsultToDo\ConsultToDo;
use Prospecter\Core\Prospecting\Application\ConsultToDo\KeptToDo;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * The to-do of the route, read in Prospecting, for its voter to decide whether the member reaches it (ADR 0081).
 */
final readonly class ToDoResolver implements ValueResolverInterface
{
    public function __construct(private QueryHandler $consultToDo)
    {
    }

    /**
     * @return list<ToDoInList>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (ToDoInList::class !== $argument->getType()) {
            return [];
        }

        $kept = new KeptToDo();
        ($this->consultToDo)(new ConsultToDo($request->attributes->getString('id')), $kept);

        return [$kept->toDo];
    }
}
