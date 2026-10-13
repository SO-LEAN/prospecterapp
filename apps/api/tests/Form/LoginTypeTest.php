<?php

namespace Tests\App\Form;

use App\Form\LoginType;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

#[Group('unit')]
#[AllowMockObjectsWithoutExpectations]
class LoginTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
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

    protected function getExtensions(): array
    {
        return [new ValidatorExtension(Validation::createValidator())];
    }
}
