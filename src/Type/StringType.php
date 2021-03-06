<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Broker\Broker;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Traits\MaybeCallableTypeTrait;
use PHPStan\Type\Traits\NonIterableTypeTrait;
use PHPStan\Type\Traits\NonObjectTypeTrait;
use PHPStan\Type\Traits\UndecidedBooleanTypeTrait;

class StringType implements Type
{

    use JustNullableTypeTrait;
    use MaybeCallableTypeTrait;
    use NonIterableTypeTrait;
    use NonObjectTypeTrait;
    use UndecidedBooleanTypeTrait;

    public function describe(VerbosityLevel $level): string
    {
        return 'string';
    }

    public function isOffsetAccessible(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }

    public function getOffsetValueType(Type $offsetType): Type
    {
        return new StringType();
    }

    public function setOffsetValueType(?Type $offsetType, Type $valueType): Type
    {
        return $this;
    }

    public function accepts(Type $type, bool $strictTypes): TrinaryLogic
    {
        if ($type instanceof static) {
            return TrinaryLogic::createYes();
        }

        if ($type instanceof CompoundType) {
            return CompoundTypeHelper::accepts($type, $this, $strictTypes);
        }

        if ($type instanceof TypeWithClassName && !$strictTypes) {
            $broker = Broker::getInstance();
            if (!$broker->hasClass($type->getClassName())) {
                return TrinaryLogic::createNo();
            }

            $typeClass = $broker->getClass($type->getClassName());
            return TrinaryLogic::createFromBoolean(
                $typeClass->hasNativeMethod('__toString')
            );
        }

        return TrinaryLogic::createNo();
    }

    public function toNumber(): Type
    {
        return new ErrorType();
    }

    public function toInteger(): Type
    {
        return new IntegerType();
    }

    public function toFloat(): Type
    {
        return new FloatType();
    }

    public function toString(): Type
    {
        return $this;
    }

    public function toArray(): Type
    {
        return new ConstantArrayType(
            [new ConstantIntegerType(0)],
            [$this],
            1
        );
    }

    /**
     * @param mixed[] $properties
     * @return Type
     */
    public static function __set_state(array $properties): Type
    {
        return new self();
    }
}
