<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\PersonalData\Domain\Model\PersonResemblanceKey;

class PersonResemblanceKeyTest extends TestCase
{
    public function testTheNamesAreComparedWithoutCaseNorAccentsNorSpaces(): void
    {
        $this->assertEquals(PersonResemblanceKey::of(new PersonName('Hélène', 'De La Tour')), PersonResemblanceKey::of(new PersonName('helene', 'DELATOUR')));
    }

    public function testWithoutAFirstNameThereIsNoKey(): void
    {
        $this->assertNull(PersonResemblanceKey::of(new PersonName(null, 'Lovelace')));
    }

    public function testWithoutALastNameThereIsNoKey(): void
    {
        $this->assertNull(PersonResemblanceKey::of(new PersonName('Ada', ' ')));
    }
}
