<?php

namespace Tests\App\Service\Image;

use Tests\App\Base\TestCase;
use App\Service\Image\SecretCipherHandler;

/**
 * @group unit
 */
class SecretCipherHandlerTest extends TestCase
{
    public function target() : SecretCipherHandler
    {
        return parent::target();
    }

    public function setupArgs() : array
    {
        return [
            's€crEt',
        ];
    }

    public function testCipher()
    {
        $input = 'Qu\'on voit danser le long des golfes clairs';

        $encoded = $this->target()->encode($input);
        $decoded = $this->target()->decode($encoded);

        $this->assertEquals($input, $decoded);
    }
}
