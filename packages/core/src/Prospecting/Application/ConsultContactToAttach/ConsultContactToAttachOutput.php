<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultContactToAttach;

use Prospecter\Core\Prospecting\Application\ContactToAttachInList;
use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ConsultContactToAttachOutput extends OutputPort
{
    /**
     * @param ContactToAttachInList|null $contactToAttach none when the person waits for no attachment by the prospector
     */
    public function present(?ContactToAttachInList $contactToAttach): void;
}
