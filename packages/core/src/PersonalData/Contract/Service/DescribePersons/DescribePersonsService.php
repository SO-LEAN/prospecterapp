<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\DescribePersons;

/**
 * The service PersonalData offers the other contexts (ADR 0045, 0089). It refuses nothing. Its answer leaves out a
 * person who is unknown, forgotten or in another account.
 */
interface DescribePersonsService
{
    public function __invoke(DescribePersons $query, DescribePersonsOutput $output): void;
}
