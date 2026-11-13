<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ProposeMeetingPlace;

use Prospecter\Core\SharedKernel\Application\OutputPort;

interface ProposeMeetingPlaceOutput extends OutputPort
{
    /**
     * @param MeetingPlace|null $place none when no organization of the contact has an address
     */
    public function present(?MeetingPlace $place): void;
}
