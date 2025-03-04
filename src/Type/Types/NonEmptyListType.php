<?php

declare(strict_types=1);

namespace CuyZ\Valinor\Type\Types;

use CuyZ\Valinor\Type\CompositeTraversableType;
use CuyZ\Valinor\Type\Type;

use function count;
use function is_array;

/** @api */
final class NonEmptyListType implements CompositeTraversableType
{
    private static self $native;

    private Type $subType;

    private string $signature;

    public function __construct(Type $subType)
    {
        $this->subType = $subType;
        $this->signature = "non-empty-list<$this->subType>";
    }

    /**
     * @codeCoverageIgnore
     * @infection-ignore-all
     */
    public static function native(): self
    {
        if (! isset(self::$native)) {
            self::$native = new self(MixedType::get());
            self::$native->signature = 'non-empty-list';
        }

        return self::$native;
    }

    public function accepts($value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        if (count($value) === 0) {
            return false;
        }

        $i = 0;

        foreach ($value as $key => $item) {
            if ($key !== $i++) {
                return false;
            }

            if (! $this->subType->accepts($item)) {
                return false;
            }
        }

        return true;
    }

    public function matches(Type $other): bool
    {
        if ($other instanceof MixedType) {
            return true;
        }

        if ($other instanceof UnionType) {
            return $other->isMatchedBy($this);
        }

        if ($other instanceof ArrayType
            || $other instanceof NonEmptyArrayType
            || $other instanceof IterableType
        ) {
            return $other->keyType() !== ArrayKeyType::string()
                && $this->subType->matches($other->subType());
        }

        if ($other instanceof self || $other instanceof ListType) {
            return $this->subType->matches($other->subType());
        }

        return false;
    }

    public function keyType(): ArrayKeyType
    {
        return ArrayKeyType::integer();
    }

    public function subType(): Type
    {
        return $this->subType;
    }

    public function __toString(): string
    {
        return $this->signature;
    }
}
