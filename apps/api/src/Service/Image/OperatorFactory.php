<?php

namespace App\Service\Image;

use Imagine\Image\ImagineInterface;

class OperatorFactory
{
    public const string IMAGINE_NAMESPACE_PATTERN = 'Imagine\%s\Imagine';
    public const string OPERATOR_NAMESPACE_PATTERN = 'App\Service\Image\%sOperator';

    public function createOperator(string $operation, string $driver = 'Gd'): Operator
    {
        $class = sprintf(self::OPERATOR_NAMESPACE_PATTERN, ucfirst($operation));

        /** @var Operator $operator */
        $operator = new $class();
        $operator->setDriver($this->getDriver($driver));

        return $operator;
    }

    private function getDriver(string $driver): ImagineInterface
    {
        $class = sprintf(self::IMAGINE_NAMESPACE_PATTERN, $driver);

        return new $class();
    }
}
