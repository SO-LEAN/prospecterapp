<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\DescribePersons;

interface DescribePersonsOutput
{
    /**
     * @param list<PersonDescriptionResponse> $persons the persons the reader reaches; none for an unknown reader
     */
    public function present(array $persons): void;
}
