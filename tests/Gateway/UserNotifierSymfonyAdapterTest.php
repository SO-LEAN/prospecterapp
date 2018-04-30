<?php

namespace Tests\App\Gateway;


use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Tests\App\Base\TestCase;
use App\Gateway\UserNotifierSymfonyAdapter;
use Symfony\Component\HttpFoundation\Session\Session;

class UserNotifierSymfonyAdapterTest extends TestCase
{
    public function target() : UserNotifierSymfonyAdapter
    {
        return parent::target();
    }

    public function setupArgs() : array
    {
        $this->prophesy(Session::class)->getFlashBag()->willReturn($this->prophesy(FlashBag::class)->reveal());

        return [
            $this->prophesy(Session::class)->reveal(),
        ];
    }

    /**
     * @param string $type
     * @param string $method
     * @param string $msg
     *
     * @dataProvider providerAdd
     */
    public function testAdd($type, $method, $msg)
    {
        $this->prophesy(FlashBag::class)->add($type, $msg)->shouldBeCalled();
        $this->target()->$method($msg);
    }

    /**
     * @return array
     */
    public function providerAdd()
    {
        return [
            '- success' => ['success', 'addSuccess', 'action ok'],
            '- warning' => ['warning', 'addWarning', 'action warning'],
            '- error' => ['error', 'addError', 'action error'],
            '- info' => ['info', 'addInfo', 'action info'],
        ];
    }
}
