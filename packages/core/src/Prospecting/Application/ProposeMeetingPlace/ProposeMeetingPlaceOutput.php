<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ProposeMeetingPlace;

use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ProposeMeetingPlaceOutput extends OutputPort
{
    /**
     * @param MeetingPlace|null $place none when none of the contact's organizations has an address
     */
    public function present(?MeetingPlace $place): void;
}
