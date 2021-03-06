<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Traits\MaybeIterableTypeTrait;
use PHPStan\Type\Traits\MaybeObjectTypeTrait;
use PHPStan\Type\Traits\MaybeOffsetAccessibleTypeTrait;
use PHPStan\Type\Traits\TruthyBooleanTypeTrait;

class CallableType implements CompoundType
{

    use MaybeIterableTypeTrait;
    use MaybeObjectTypeTrait;
    use MaybeOffsetAccessibleTypeTrait;
    use TruthyBooleanTypeTrait;

    /**
     * @return string[]
     */
    public function getReferencedClasses(): array
    {
        return [];
    }

    public function accepts(Type $type, bool $strictTypes): TrinaryLogic
    {
        if ($type instanceof CompoundType) {
            return CompoundTypeHelper::accepts($type, $this, $strictTypes);
        }

        return $type->isCallable();
    }

    public function isSuperTypeOf(Type $type): TrinaryLogic
    {
        return $type->isCallable();
    }

    public function isSubTypeOf(Type $otherType): TrinaryLogic
    {
        if ($otherType instanceof IntersectionType || $otherType instanceof UnionType) {
            return $otherType->isSuperTypeOf($this);
        }

        return $otherType->isCallable()
            ->and($otherType instanceof self ? TrinaryLogic::createYes() : TrinaryLogic::createMaybe());
    }

    public function equals(Type $type): bool
    {
        return $type instanceof self;
    }

    public function describe(VerbosityLevel $level): string
    {
        return 'callable';
    }

    public function isCallable(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }

    /**
     * @param \PHPStan\Analyser\Scope $scope
     * @return \PHPStan\Reflection\ParametersAcceptor[]
     */
    public function getCallableParametersAcceptors(Scope $scope): array
    {
        return [new TrivialParametersAcceptor()];
    }

    public function toNumber(): Type
    {
        return new ErrorType();
    }

    public function toString(): Type
    {
        return new ErrorType();
    }

    public function toInteger(): Type
    {
        return new ErrorType();
    }

    public function toFloat(): Type
    {
        return new ErrorType();
    }

    public function toArray(): Type
    {
        return new ArrayType(new MixedType(), new MixedType());
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
