<?php

declare(strict_types=1);

namespace Tests\Core\Base;

use Tests\Core\Builder\FileBuilder;
use Tests\Core\Builder\PageBuilder;
use Tests\Core\Builder\UserBuilder;
use Tests\Core\Builder\AddressBuilder;
use Tests\Core\Builder\GeoPointBuilder;
use Tests\Core\Builder\OrganizationBuilder;

function aPage()
{
    return new PageBuilder();
}

function anAddress()
{
    return new AddressBuilder();
}
function aFile()
{
    return new FileBuilder();
}
function anOrganization()
{
    return new OrganizationBuilder();
}
function aUser()
{
    return new UserBuilder();
}
function aGeoPoint()
{
    return new GeoPointBuilder();
}
