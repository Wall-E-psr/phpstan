<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Traits\NonCallableTypeTrait;
use PHPStan\Type\Traits\NonIterableTypeTrait;
use PHPStan\Type\Traits\NonObjectTypeTrait;
use PHPStan\Type\Traits\NonOffsetAccessibleTypeTrait;
use PHPStan\Type\Traits\UndecidedBooleanTypeTrait;

class IntegerType implements Type
{

    use JustNullableTypeTrait;
    use NonCallableTypeTrait;
    use NonIterableTypeTrait;
    use NonObjectTypeTrait;
    use NonOffsetAccessibleTypeTrait;
    use UndecidedBooleanTypeTrait;

    public function describe(VerbosityLevel $level): string
    {
        return 'int';
    }

    /**
     * @param mixed[] $properties
     * @return Type
     */
    public static function __set_state(array $properties): Type
    {
        return new self();
    }

    public function toNumber(): Type
    {
        return $this;
    }

    public function toFloat(): Type
    {
        return new FloatType();
    }

    public function toInteger(): Type
    {
        return $this;
    }

    public function toString(): Type
    {
        return new StringType();
    }

    public function toArray(): Type
    {
        return new ConstantArrayType(
            [new ConstantIntegerType(0)],
            [$this],
            1
        );
    }
}
