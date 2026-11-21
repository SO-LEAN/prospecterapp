<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;

class PersonNameTest extends TestCase
{
    public function testTheSpacesAtTheEndsAreRemoved(): void
    {
        $this->assertEquals(new PersonName('Ada', 'Lovelace'), new PersonName('  Ada  ', "\tLovelace \n"));
    }

    public function testTheSpacesRepeatedBetweenTheWordsMakeOnlyOne(): void
    {
        $this->assertEquals(new PersonName('Jean Pierre', 'De la Tour'), new PersonName('Jean   Pierre', "De \t la Tour"));
    }

    public function testANameMadeOfSpacesOnlyIsAbsent(): void
    {
        $this->assertNull(new PersonName('   ', 'Lovelace')->firstName);
    }
}
