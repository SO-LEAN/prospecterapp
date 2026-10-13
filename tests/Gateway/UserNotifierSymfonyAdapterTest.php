<?php

namespace Tests\App\Gateway;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Tests\App\Base\TestCase;
use App\Gateway\UserNotifierSymfonyAdapter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

#[Group('unit')]
class UserNotifierSymfonyAdapterTest extends TestCase
{
    public function target() : UserNotifierSymfonyAdapter
    {
        return parent::target();
    }

    public function setupArgs() : array
    {
        $this->prophesy(Session::class)->getFlashBag()->willReturn($this->prophesy(FlashBag::class)->reveal());
        $this->prophesy(RequestStack::class)->getSession()->willReturn($this->prophesy(Session::class)->reveal());

        return [
            $this->prophesy(RequestStack::class)->reveal(),
        ];
    }

    #[DataProvider('providerAdd')]
    public function testAdd($type, $method, $msg)
    {
        $this->prophesy(FlashBag::class)->add($type, $msg)->shouldBeCalled();
        $this->target()->$method($msg);
    }

    /**
     * @return array
     */
    public static function providerAdd()
    {
        return [
            '- success' => ['success', 'addSuccess', 'action ok'],
            '- warning' => ['warning', 'addWarning', 'action warning'],
            '- error' => ['error', 'addError', 'action error'],
            '- info' => ['info', 'addInfo', 'action info'],
        ];
    }
}
