<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class SignatureMapParser
{

    /** @var \PHPStan\PhpDoc\TypeStringResolver */
    private $typeStringResolver;

    public function __construct(
        TypeStringResolver $typeNodeResolver
    ) {
        $this->typeStringResolver = $typeNodeResolver;
    }

    /**
     * @param mixed[] $map
     * @param string|null $className
     * @return \PHPStan\Reflection\SignatureMap\FunctionSignature
     */
    public function getFunctionSignature(array $map, ?string $className): FunctionSignature
    {
        $parameterSignatures = $this->getParameters(array_slice($map, 1));
        $hasVariadic = false;
        foreach ($parameterSignatures as $parameterSignature) {
            if ($parameterSignature->isVariadic()) {
                $hasVariadic = true;
                break;
            }
        }
        return new FunctionSignature(
            $parameterSignatures,
            $this->getTypeFromString($map[0], $className),
            $hasVariadic
        );
    }

    private function getTypeFromString(string $typeString, ?string $className): Type
    {
        if ($typeString === '') {
            return new MixedType(true);
        }
        $parts = explode('|', $typeString);
        $types = [];
        foreach ($parts as $part) {
            $isNullable = false;
            if (substr($part, 0, 1) === '?') {
                $isNullable = true;
                $part = substr($part, 1);
            }

            $type = $this->typeStringResolver->resolve($part, new NameScope(null, [], $className));
            if ($isNullable) {
                $type = TypeCombinator::addNull($type);
            }

            $types[] = $type;
        }

        return TypeCombinator::union(...$types);
    }

    /**
     * @param array<string, string> $parameterMap
     * @return array<int, \PHPStan\Reflection\SignatureMap\ParameterSignature>
     */
    private function getParameters(array $parameterMap): array
    {
        $parameterSignatures = [];
        foreach ($parameterMap as $parameterName => $typeString) {
            [$name, $isOptional, $passedByReference, $isVariadic] = $this->getParameterInfoFromName($parameterName);
            $parameterSignatures[] = new ParameterSignature(
                $name,
                $isOptional,
                $this->getTypeFromString($typeString, null),
                $passedByReference,
                $isVariadic
            );
        }

        return $parameterSignatures;
    }

    /**
     * @param string $parameterNameString
     * @return mixed[]
     */
    private function getParameterInfoFromName(string $parameterNameString): array
    {
        $matches = \Nette\Utils\Strings::match(
            $parameterNameString,
            '#^(?P<reference>&(?:\.\.\.)?r?w?_?)?(?P<variadic>\.\.\.)?(?P<name>[^=]+)?(?P<optional>=)?($)#'
        );
        if ($matches === null || !isset($matches['optional'])) {
            throw new \PHPStan\ShouldNotHappenException();
        }

        $isVariadic = $matches['variadic'] !== '';

        $reference = $matches['reference'];
        if (strpos($reference, '&...') === 0) {
            $reference = '&' . substr($reference, 4);
            $isVariadic = true;
        }
        if (strpos($reference, '&rw') === 0) {
            $passedByReference = PassedByReference::createReadsArgument();
        } elseif (strpos($reference, '&w') === 0) {
            $passedByReference = PassedByReference::createCreatesNewVariable();
        } else {
            $passedByReference = PassedByReference::createNo();
        }

        $isOptional = $isVariadic || $matches['optional'] !== '';

        $name = $matches['name'] !== '' ? $matches['name'] : '...';

        return [$name, $isOptional, $passedByReference, $isVariadic];
    }
}
