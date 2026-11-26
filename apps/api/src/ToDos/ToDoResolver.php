<?php

declare(strict_types=1);

namespace App\ToDos;

use App\Security\User;
use Prospecter\Core\Prospecting\Application\ToDo\ConsultToDo\ConsultToDo;
use Prospecter\Core\Prospecting\Application\ToDo\ConsultToDo\KeptToDo;
use Prospecter\Core\Prospecting\Application\ToDoInList;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Reads the route's to-do in Prospecting, so that its voter decides whether the member reaches it (ADR 0081).
 */
final readonly class ToDoResolver implements ValueResolverInterface
{
    public function __construct(private QueryHandler $consultToDo, private Security $security)
    {
    }

    /**
     * @return list<ToDoInList>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $member = $this->security->getUser();

        if (ToDoInList::class !== $argument->getType() || !$member instanceof User) {
            return [];
        }

        return [$this->read($member->memberId(), $request->attributes->getString('id'))];
    }

    /**
     * Reads the to-do with this id for the voter, whatever its account. The core refuses an unknown to-do.
     */
    public function read(string $reader, string $id): ToDoInList
    {
        $kept = new KeptToDo();
        $this->consultToDo->__invoke(new ConsultToDo($reader, $id), $kept);

        return $kept->toDo;
    }
}
