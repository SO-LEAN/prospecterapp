<?php

namespace Tests\App\Service\Image;

use App\Service\Image\SecretCipherHandler;
use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\TestCase;

#[Group('unit')]
class SecretCipherHandlerTest extends TestCase
{
    public function target(): SecretCipherHandler
    {
        return parent::target();
    }

    public function setupArgs(): array
    {
        return [
            's€crEt',
        ];
    }

    public function testCipher(): void
    {
        $input = 'Qu\'on voit danser le long des golfes clairs';

        $encoded = $this->target()->encode($input);
        $decoded = $this->target()->decode($encoded);

        $this->assertEquals($input, $decoded);
    }
}
