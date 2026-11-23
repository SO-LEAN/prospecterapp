<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultContactToAttach;

use Prospecter\Core\Prospecting\Application\ContactToAttachInList;
use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ConsultContactToAttachOutput extends OutputPort
{
    /**
     * @param ContactToAttachInList|null $contactToAttach none when the person does not wait for the prospector to attach them
     */
    public function present(?ContactToAttachInList $contactToAttach): void;
}
