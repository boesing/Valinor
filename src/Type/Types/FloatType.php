<?php

declare(strict_types=1);

namespace CuyZ\Valinor\Type\Types;

use CuyZ\Valinor\Type\ScalarType;
use CuyZ\Valinor\Type\Type;
use CuyZ\Valinor\Type\Types\Exception\CannotCastValue;
use CuyZ\Valinor\Utility\IsSingleton;

use function is_float;
use function is_numeric;

/** @api */
final class FloatType implements ScalarType
{
    use IsSingleton;

    public function accepts($value): bool
    {
        return is_float($value);
    }

    public function matches(Type $other): bool
    {
        if ($other instanceof UnionType) {
            return $other->isMatchedBy($this);
        }

        return $other instanceof self
            || $other instanceof MixedType;
    }

    public function canCast($value): bool
    {
        return is_numeric($value);
    }

    public function cast($value): float
    {
        if (! $this->canCast($value)) {
            throw new CannotCastValue($value, $this);
        }

        return (float)$value; // @phpstan-ignore-line
    }

    public function __toString(): string
    {
        return 'float';
    }
}
