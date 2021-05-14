<?php

namespace Tests\App\Form\UseCaseType;

use App\Form\UseCaseType\LoginType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;

/**
 * @group unit
 */
class LoginTypeTest extends TypeTestCase
{
    use ValidatorExtensionTrait;

    /**
     *
     */
    public function testSubmitValidData()
    {
        $expected = $formData = [
            'userName' => 'login',
            'password' => 'password',
        ];

        $form = $this->factory->create(LoginType::class);

        $form->submit($formData);
        $view = $form->createView();
        $children = $view->children;

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $form->getData());

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }
}
