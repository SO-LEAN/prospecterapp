<?php

namespace App\Service\Image;

use Imagine\Image\ImagineInterface;

class OperatorFactory
{
    const IMAGINE_NAMESPACE_PATTERN = 'Imagine\%s\Imagine';
    const OPERATOR_NAMESPACE_PATTERN = 'App\Service\Image\%sOperator';

    /**
     * @param string $operation
     * @param string $driver
     *
     * @return mixed
     */
    public function createOperator($operation, $driver = 'Gd'): Operator
    {
        $class = sprintf(self::OPERATOR_NAMESPACE_PATTERN, ucfirst($operation));

        /**
         * @var Operator
         */
        $operator = new $class();
        $operator->setDriver($this->getDriver($driver));

        return $operator;
    }

    /**
     * @param string $driver
     *
     * @return ImagineInterface
     */
    private function getDriver($driver = 'Gd'): ImagineInterface
    {
        $class = sprintf(self::IMAGINE_NAMESPACE_PATTERN, $driver);

        return new $class();
    }
}
