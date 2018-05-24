<?php

namespace App\Service;


use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;

class PaginatorFactory
{
    /**
     * @param Query $query
     * @return Paginator
     */
    public function Create(Query $query): Paginator
    {
        return new Paginator($query);
    }

}
