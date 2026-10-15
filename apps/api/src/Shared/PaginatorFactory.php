<?php

namespace App\Shared;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;

class PaginatorFactory
{
    public function Create(Query $query): Paginator
    {
        return new Paginator($query);
    }
}
